import { Router } from 'express';
import { db, playersTable, tokenTransactionsTable, matchesTable } from '@workspace/db';
import { eq, desc, sql } from 'drizzle-orm';
import { WIN_REWARD_AMOUNT as WIN_REWARD } from '../config.js';

const tokensRouter = Router();

function isUniqueViolation(err: unknown): boolean {
  return (
    typeof err === 'object' &&
    err !== null &&
    'code' in err &&
    (err as { code: string }).code === '23505'
  );
}

tokensRouter.post('/tokens/award-win', async (req, res) => {
  const { playerId, matchId } = req.body as {
    playerId?: string;
    matchId?: string;
  };

  if (!playerId || !matchId) {
    res.status(400).json({ error: 'playerId and matchId are required' });
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

    if (!match.gameResult) {
      res.status(400).json({ error: 'Match result has not been recorded yet' });
      return;
    }

    if (match.tokensAwarded) {
      const player = await db
        .select({ tokenBalance: playersTable.tokenBalance })
        .from(playersTable)
        .where(eq(playersTable.id, playerId))
        .limit(1);
      res.json({
        ok: true,
        tokenBalance: player[0]?.tokenBalance ?? 0,
        awarded: 0,
        alreadyAwarded: true,
      });
      return;
    }

    const playerSymbol = match.playerSymbol;
    const iWon = playerSymbol !== null && match.gameResult === `${playerSymbol}_WIN`;

    const { tokenBalance, awarded } = await db.transaction(async (tx) => {
      await tx
        .update(matchesTable)
        .set({ endedAt: now, tokensAwarded: true })
        .where(eq(matchesTable.id, matchId));

      if (!iWon) {
        const player = await tx
          .select({ tokenBalance: playersTable.tokenBalance })
          .from(playersTable)
          .where(eq(playersTable.id, playerId))
          .limit(1);
        return { tokenBalance: player[0]?.tokenBalance ?? 0, awarded: 0 };
      }

      const updated = await tx
        .update(playersTable)
        .set({ tokenBalance: sql`${playersTable.tokenBalance} + ${WIN_REWARD}` })
        .where(eq(playersTable.id, playerId))
        .returning({ tokenBalance: playersTable.tokenBalance });

      if (updated.length === 0) throw new Error('PLAYER_NOT_FOUND');

      await tx.insert(tokenTransactionsTable).values({
        playerId,
        amount: WIN_REWARD,
        type: 'match_win_reward',
        description: 'Won a match',
        relatedMatchId: matchId,
      });

      return { tokenBalance: updated[0].tokenBalance, awarded: WIN_REWARD };
    });

    res.json({
      ok: true,
      tokenBalance,
      awarded,
      result: match.gameResult,
    });
  } catch (err) {
    if (err instanceof Error && err.message === 'PLAYER_NOT_FOUND') {
      res.status(404).json({ error: 'Player not found' });
      return;
    }
    if (isUniqueViolation(err)) {
      const player = await db
        .select({ tokenBalance: playersTable.tokenBalance })
        .from(playersTable)
        .where(eq(playersTable.id, playerId))
        .limit(1);
      res.json({
        ok: true,
        tokenBalance: player[0]?.tokenBalance ?? 0,
        awarded: 0,
        alreadyAwarded: true,
      });
      return;
    }
    res.status(500).json({ error: 'Failed to award tokens' });
  }
});

tokensRouter.get('/tokens/balance/:playerId', async (req, res) => {
  const { playerId } = req.params;
  try {
    const rows = await db
      .select({ tokenBalance: playersTable.tokenBalance })
      .from(playersTable)
      .where(eq(playersTable.id, playerId))
      .limit(1);
    if (rows.length === 0) {
      res.status(404).json({ error: 'Player not found' });
      return;
    }
    res.json({ tokenBalance: rows[0].tokenBalance });
  } catch {
    res.status(500).json({ error: 'Failed to fetch balance' });
  }
});

tokensRouter.get('/tokens/history/:playerId', async (req, res) => {
  const { playerId } = req.params;
  const limitParam = Number(req.query['limit'] ?? 30);
  const limit = Math.min(Math.max(1, limitParam), 100);
  try {
    const rows = await db
      .select()
      .from(tokenTransactionsTable)
      .where(eq(tokenTransactionsTable.playerId, playerId))
      .orderBy(desc(tokenTransactionsTable.createdAt))
      .limit(limit);
    res.json(rows);
  } catch {
    res.status(500).json({ error: 'Failed to fetch history' });
  }
});

export default tokensRouter;
