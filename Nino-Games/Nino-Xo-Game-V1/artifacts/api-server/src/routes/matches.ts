import { Router } from 'express';
import { db, playersTable, matchesTable } from '@workspace/db';
import { eq, desc, and, isNotNull, or, sql } from 'drizzle-orm';
import { requireAuth } from '../middleware/auth.js';

const matchesRouter = Router();

type Cell = 'X' | 'O' | null;

const WIN_LINES = [
  [0, 1, 2], [3, 4, 5], [6, 7, 8],
  [0, 3, 6], [1, 4, 7], [2, 5, 8],
  [0, 4, 8], [2, 4, 6],
];

function computeGameResult(board: Cell[]): 'X_WIN' | 'O_WIN' | 'DRAW' | null {
  if (!Array.isArray(board) || board.length !== 9) return null;
  for (const [a, b, c] of WIN_LINES) {
    if (board[a] && board[a] === board[b] && board[a] === board[c]) {
      return board[a] === 'X' ? 'X_WIN' : 'O_WIN';
    }
  }
  if (board.every((cell) => cell !== null)) return 'DRAW';
  return null;
}

matchesRouter.post('/matches/start', async (req, res) => {
  const { playerId, mode, playerSymbol, opponentName, opponentCharacterId } = req.body as {
    playerId?: string;
    mode?: string;
    playerSymbol?: string;
    opponentName?: string;
    opponentCharacterId?: string;
  };

  if (!playerId || !mode) {
    res.status(400).json({ error: 'playerId and mode are required' });
    return;
  }

  const validModes = ['bot', 'quick', 'online', 'friend'];
  if (!validModes.includes(mode)) {
    res.status(400).json({ error: 'Invalid mode' });
    return;
  }

  if (playerSymbol && playerSymbol !== 'X' && playerSymbol !== 'O') {
    res.status(400).json({ error: 'playerSymbol must be X or O' });
    return;
  }

  try {
    const player = await db
      .select({ id: playersTable.id })
      .from(playersTable)
      .where(eq(playersTable.id, playerId))
      .limit(1);

    if (player.length === 0) {
      res.status(404).json({ error: 'Player not found' });
      return;
    }

    const [match] = await db
      .insert(matchesTable)
      .values({
        playerId,
        mode,
        playerSymbol: playerSymbol ?? null,
        opponentName: opponentName?.trim() || null,
        opponentCharacterId: opponentCharacterId?.trim() || null,
      })
      .returning({ id: matchesTable.id });

    res.json({ matchId: match.id });
  } catch {
    res.status(500).json({ error: 'Failed to start match' });
  }
});

matchesRouter.post('/matches/record-result', async (req, res) => {
  const { matchId, playerId, board } = req.body as {
    matchId?: string;
    playerId?: string;
    board?: unknown[];
  };

  if (!matchId || !playerId || !Array.isArray(board) || board.length !== 9) {
    res.status(400).json({ error: 'matchId, playerId and board (9 cells) are required' });
    return;
  }

  const boardCells = board as Cell[];
  const gameResult = computeGameResult(boardCells);

  if (gameResult === null) {
    res.status(400).json({ error: 'Board does not show a completed game' });
    return;
  }

  try {
    const now = new Date();

    const [match] = await db
      .select()
      .from(matchesTable)
      .where(eq(matchesTable.id, matchId))
      .limit(1);

    if (!match) {
      res.status(404).json({ error: 'Match not found' });
      return;
    }

    if (match.playerId !== playerId) {
      res.status(403).json({ error: 'Match does not belong to this player' });
      return;
    }

    if (match.expiresAt < now) {
      res.status(410).json({ error: 'Match record has expired' });
      return;
    }

    if (match.gameResult !== null) {
      res.json({ ok: true, result: match.gameResult });
      return;
    }

    await db
      .update(matchesTable)
      .set({ board: JSON.stringify(boardCells), gameResult })
      .where(eq(matchesTable.id, matchId));

    res.json({ ok: true, result: gameResult });
  } catch {
    res.status(500).json({ error: 'Failed to record match result' });
  }
});

// ── GET /matches/history ─────────────────────────────────────────────────────
// Returns up to `limit` completed matches for the authenticated player, newest first.
// Optional `result` filter: win | loss | draw (applied at SQL level)
matchesRouter.get('/matches/history', requireAuth, async (req, res) => {
  const { limit: limitRaw, result: resultFilter } = req.query as {
    limit?: string;
    result?: string;
  };

  // Only serve the authenticated player's own history
  const playerId = req.player!.id;

  const limit = Math.min(Math.max(1, parseInt(limitRaw ?? '30') || 30), 100);
  const validFilters = ['win', 'loss', 'draw'] as const;
  type PlayerResult = 'win' | 'loss' | 'draw';
  const filterResult: PlayerResult | null =
    resultFilter && (validFilters as readonly string[]).includes(resultFilter)
      ? (resultFilter as PlayerResult)
      : null;

  // Build the SQL result-filter condition at the query level
  // win:  (X_WIN AND symbol=X) OR (O_WIN AND symbol=O)
  // loss: (X_WIN AND symbol=O) OR (O_WIN AND symbol=X)
  // draw: DRAW
  function resultCondition(r: PlayerResult) {
    if (r === 'win') {
      return or(
        and(sql`${matchesTable.gameResult} = 'X_WIN'`, sql`${matchesTable.playerSymbol} = 'X'`),
        and(sql`${matchesTable.gameResult} = 'O_WIN'`, sql`${matchesTable.playerSymbol} = 'O'`),
      );
    }
    if (r === 'loss') {
      return or(
        and(sql`${matchesTable.gameResult} = 'X_WIN'`, sql`${matchesTable.playerSymbol} = 'O'`),
        and(sql`${matchesTable.gameResult} = 'O_WIN'`, sql`${matchesTable.playerSymbol} = 'X'`),
      );
    }
    return sql`${matchesTable.gameResult} = 'DRAW'`;
  }

  const whereClause = filterResult
    ? and(eq(matchesTable.playerId, playerId), isNotNull(matchesTable.gameResult), resultCondition(filterResult))
    : and(eq(matchesTable.playerId, playerId), isNotNull(matchesTable.gameResult));

  try {
    const rows = await db
      .select({
        id: matchesTable.id,
        mode: matchesTable.mode,
        playerSymbol: matchesTable.playerSymbol,
        gameResult: matchesTable.gameResult,
        tokensAwarded: matchesTable.tokensAwarded,
        opponentName: matchesTable.opponentName,
        opponentCharacterId: matchesTable.opponentCharacterId,
        createdAt: matchesTable.createdAt,
        endedAt: matchesTable.endedAt,
      })
      .from(matchesTable)
      .where(whereClause)
      .orderBy(desc(matchesTable.createdAt))
      .limit(limit);

    function toPlayerResult(gameResult: string | null, symbol: string | null): PlayerResult | null {
      if (!gameResult) return null;
      if (gameResult === 'DRAW') return 'draw';
      const isX = symbol === 'X';
      if (gameResult === 'X_WIN') return isX ? 'win' : 'loss';
      if (gameResult === 'O_WIN') return isX ? 'loss' : 'win';
      return null;
    }

    function formatDuration(createdAt: Date | null, endedAt: Date | null): string | null {
      if (!createdAt || !endedAt) return null;
      const seconds = Math.max(0, Math.round((endedAt.getTime() - createdAt.getTime()) / 1000));
      const m = Math.floor(seconds / 60);
      const s = seconds % 60;
      return `${m}:${s.toString().padStart(2, '0')}`;
    }

    function fallbackOpponentName(mode: string): string {
      switch (mode) {
        case 'bot': return 'Bot';
        case 'quick': return 'Opponent';
        case 'friend': return 'Friend';
        default: return 'Opponent';
      }
    }

    const mapped = rows.flatMap((row) => {
      const result = toPlayerResult(row.gameResult, row.playerSymbol);
      if (!result) return [];
      return [{
        id: row.id,
        result,
        opponentName: row.opponentName ?? fallbackOpponentName(row.mode ?? 'bot'),
        opponentCharacterId: row.opponentCharacterId ?? (row.mode === 'bot' ? 'bot' : null),
        mode: row.mode ?? 'bot',
        duration: formatDuration(row.createdAt, row.endedAt),
        date: row.createdAt ? row.createdAt.toISOString() : new Date().toISOString(),
        tokensAwarded: row.tokensAwarded,
      }];
    });

    res.json({ matches: mapped });
  } catch (err) {
    console.error('[matches/history]', err);
    res.status(500).json({ error: 'Failed to fetch match history' });
  }
});

export default matchesRouter;
