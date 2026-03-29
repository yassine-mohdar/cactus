import { Router } from 'express';
import { randomBytes, createHmac, timingSafeEqual } from 'crypto';
import { db, playersTable, tokenTransactionsTable } from '@workspace/db';
import { requireAdmin } from '../middleware/auth.js';
import {
  challengesTable,
  challengeParticipantsTable,
  challengeMatchesTable,
  challengeRewardLogTable,
  challengeMatchTokensTable,
} from '@workspace/db';
import { eq, sql, desc, and, asc, inArray, lte, isNull } from 'drizzle-orm';
import { MATCH_TOKEN_SECRET } from '../config.js';

const challengesRouter = Router();

// ── Match token helpers ───────────────────────────────────────────────────────

const MATCH_TOKEN_TTL_MS = 10 * 60 * 1000; // 10 minutes max for a single game

function signTokenId(tokenId: string): string {
  return createHmac('sha256', MATCH_TOKEN_SECRET).update(tokenId).digest('hex');
}

function makeMatchToken(challengeId: string, playerId: string): { tokenId: string; signature: string; expiresAt: Date } {
  const tokenId = `${randomBytes(16).toString('hex')}.${challengeId}.${playerId}`;
  const signature = signTokenId(tokenId);
  const expiresAt = new Date(Date.now() + MATCH_TOKEN_TTL_MS);
  return { tokenId, signature, expiresAt };
}

function verifyMatchTokenSignature(tokenId: string, signature: string): boolean {
  try {
    const expected = signTokenId(tokenId);
    return timingSafeEqual(Buffer.from(expected, 'hex'), Buffer.from(signature, 'hex'));
  } catch {
    return false;
  }
}

// ── Completion helper (shared by manual + auto-complete) ─────────────────────

async function completeChallengeById(id: string): Promise<{ ok: boolean; rewards: { playerId: string; name: string; rank: number; amount: number }[]; alreadyDone?: boolean }> {
  const [challenge] = await db
    .select()
    .from(challengesTable)
    .where(eq(challengesTable.id, id))
    .limit(1);

  if (!challenge) return { ok: false, rewards: [] };
  if (challenge.rewardsDistributed) return { ok: true, rewards: [], alreadyDone: true };

  if (challenge.prizePool <= 0 && !challenge.rank1Reward) {
    await db
      .update(challengesTable)
      .set({ status: 'completed', rewardsDistributed: true })
      .where(eq(challengesTable.id, id));
    return { ok: true, rewards: [] };
  }

  const leaderboard = await buildLeaderboard(id);
  const winners = leaderboard.slice(0, 3);

  const rewards: { playerId: string; name: string; rank: number; amount: number }[] = [];

  for (let i = 0; i < winners.length; i++) {
    const winner = winners[i];
    let amount = 0;

    if (i === 0 && challenge.rank1Reward) {
      amount = challenge.rank1Reward;
    } else if (i === 1 && challenge.rank2Reward) {
      amount = challenge.rank2Reward;
    } else if (i === 2 && challenge.rank3Reward) {
      amount = challenge.rank3Reward;
    } else {
      const splits = [0.5, 0.3, 0.2];
      const totalSplit = splits.slice(0, winners.length).reduce((a, b) => a + b, 0);
      amount = Math.round((challenge.prizePool * splits[i]) / totalSplit);
    }

    if (amount > 0) {
      rewards.push({ playerId: winner.playerId, name: winner.name, rank: i + 1, amount });
    }
  }

  await db.transaction(async (tx) => {
    // Lock challenge row: only complete if not yet distributed (double-complete guard)
    const [locked] = await tx
      .select({ rewardsDistributed: challengesTable.rewardsDistributed })
      .from(challengesTable)
      .where(and(eq(challengesTable.id, id), eq(challengesTable.rewardsDistributed, false)))
      .limit(1);

    if (!locked) return; // Already completed by concurrent request

    await tx
      .update(challengesTable)
      .set({ status: 'completed', rewardsDistributed: true })
      .where(eq(challengesTable.id, id));

    for (const r of rewards) {
      // Only credit if the reward log row is newly inserted (idempotent via unique constraint)
      const inserted = await tx
        .insert(challengeRewardLogTable)
        .values({ challengeId: id, playerId: r.playerId, amount: r.amount, rank: r.rank })
        .onConflictDoNothing()
        .returning({ id: challengeRewardLogTable.id });

      if (inserted.length > 0) {
        // Row was new — safe to credit tokens
        await tx
          .update(playersTable)
          .set({ tokenBalance: sql`${playersTable.tokenBalance} + ${r.amount}` })
          .where(eq(playersTable.id, r.playerId));

        await tx.insert(tokenTransactionsTable).values({
          playerId: r.playerId,
          amount: r.amount,
          type: 'challenge_reward',
          description: `Challenge prize: ${challenge.title} (Rank #${r.rank})`,
        });
      }
    }
  });

  return { ok: true, rewards };
}

// ── Auto-complete scheduler ───────────────────────────────────────────────────

async function autoCompleteExpiredChallenges() {
  try {
    const now = new Date();
    const expired = await db
      .select({ id: challengesTable.id })
      .from(challengesTable)
      .where(
        and(
          eq(challengesTable.status, 'active'),
          eq(challengesTable.rewardsDistributed, false),
          lte(challengesTable.endAt, now),
        ),
      );

    for (const { id } of expired) {
      await completeChallengeById(id).catch(() => {});
    }
  } catch {
    // Non-fatal — will retry on next interval
  }
}

// Run on startup and then every 2 minutes
autoCompleteExpiredChallenges();
const _autoCompleteTimer = setInterval(autoCompleteExpiredChallenges, 2 * 60 * 1000);

// ── Helpers ──────────────────────────────────────────────────────────────────

async function buildLeaderboard(challengeId: string) {
  const rows = await db
    .select({
      playerId: challengeParticipantsTable.playerId,
      wins: challengeParticipantsTable.wins,
      losses: challengeParticipantsTable.losses,
      matches: sql<number>`cast(${challengeParticipantsTable.wins} + ${challengeParticipantsTable.losses} as int)`,
      joinedAt: challengeParticipantsTable.joinedAt,
    })
    .from(challengeParticipantsTable)
    .where(eq(challengeParticipantsTable.challengeId, challengeId))
    .orderBy(
      desc(challengeParticipantsTable.wins),
      asc(challengeParticipantsTable.losses),
      asc(challengeParticipantsTable.joinedAt),
    );

  if (rows.length === 0) return [];

  const playerIds = rows.map((r) => r.playerId);
  const players = await db
    .select({ id: playersTable.id, name: playersTable.name })
    .from(playersTable)
    .where(inArray(playersTable.id, playerIds));

  const nameMap = Object.fromEntries(players.map((p) => [p.id, p.name]));

  return rows.map((r, i) => ({
    rank: i + 1,
    playerId: r.playerId,
    name: nameMap[r.playerId] ?? 'Unknown',
    wins: r.wins,
    losses: r.losses,
    matches: r.matches,
  }));
}

function attachCounts(
  rows: typeof challengesTable.$inferSelect[],
  countMap: Record<string, number>,
) {
  return rows.map((r) => ({ ...r, participantCount: countMap[r.id] ?? 0 }));
}

async function fetchParticipantCounts(ids: string[]): Promise<Record<string, number>> {
  if (ids.length === 0) return {};
  const counts = await db
    .select({
      challengeId: challengeParticipantsTable.challengeId,
      count: sql<number>`cast(count(*) as int)`,
    })
    .from(challengeParticipantsTable)
    .where(inArray(challengeParticipantsTable.challengeId, ids))
    .groupBy(challengeParticipantsTable.challengeId);
  return Object.fromEntries(counts.map((c) => [c.challengeId, c.count]));
}

// ── Public routes ─────────────────────────────────────────────────────────────

// GET /challenges — active/upcoming challenges
challengesRouter.get('/challenges', async (req, res) => {
  try {
    const rows = await db
      .select()
      .from(challengesTable)
      .where(sql`${challengesTable.status} IN ('upcoming', 'active')`)
      .orderBy(challengesTable.startAt);

    const countMap = await fetchParticipantCounts(rows.map((r) => r.id));
    res.json(attachCounts(rows, countMap));
  } catch {
    res.status(500).json({ error: 'Failed to fetch challenges' });
  }
});

// GET /challenges/all — all challenges (history)
challengesRouter.get('/challenges/all', async (req, res) => {
  try {
    const rows = await db
      .select()
      .from(challengesTable)
      .orderBy(desc(challengesTable.createdAt));

    const countMap = await fetchParticipantCounts(rows.map((r) => r.id));
    res.json(attachCounts(rows, countMap));
  } catch {
    res.status(500).json({ error: 'Failed to fetch challenges' });
  }
});

// GET /challenges/player/:playerId — all challenges the player participated in
challengesRouter.get('/challenges/player/:playerId', async (req, res) => {
  const { playerId } = req.params;
  try {
    // Join challenges the player is a participant in
    const participations = await db
      .select({
        challengeId: challengeParticipantsTable.challengeId,
        wins: challengeParticipantsTable.wins,
        losses: challengeParticipantsTable.losses,
        joinedAt: challengeParticipantsTable.joinedAt,
      })
      .from(challengeParticipantsTable)
      .where(eq(challengeParticipantsTable.playerId, playerId));

    if (participations.length === 0) {
      res.json([]);
      return;
    }

    const challengeIds = participations.map((p) => p.challengeId);
    const challenges = await db
      .select()
      .from(challengesTable)
      .where(inArray(challengesTable.id, challengeIds))
      .orderBy(desc(challengesTable.endAt));

    const countMap = await fetchParticipantCounts(challengeIds);
    const participationMap = Object.fromEntries(participations.map((p) => [p.challengeId, p]));

    // Fetch reward log for this player
    const rewardLogs = await db
      .select()
      .from(challengeRewardLogTable)
      .where(
        and(
          eq(challengeRewardLogTable.playerId, playerId),
          inArray(challengeRewardLogTable.challengeId, challengeIds),
        ),
      );
    const rewardMap = Object.fromEntries(rewardLogs.map((r) => [r.challengeId, r]));

    res.json(
      challenges.map((c) => {
        const p = participationMap[c.id];
        const rew = rewardMap[c.id];
        return {
          ...c,
          participantCount: countMap[c.id] ?? 0,
          myWins: p?.wins ?? 0,
          myLosses: p?.losses ?? 0,
          rewardReceived: rew ? { amount: rew.amount, rank: rew.rank } : null,
        };
      }),
    );
  } catch {
    res.status(500).json({ error: 'Failed to fetch player challenges' });
  }
});

// GET /challenges/:id — detail + leaderboard
challengesRouter.get('/challenges/:id', async (req, res) => {
  const { id } = req.params;
  const playerId = req.query['playerId'] as string | undefined;

  try {
    const [challenge] = await db
      .select()
      .from(challengesTable)
      .where(eq(challengesTable.id, id))
      .limit(1);

    if (!challenge) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }

    const [participantRow, leaderboard] = await Promise.all([
      db
        .select({ count: sql<number>`cast(count(*) as int)` })
        .from(challengeParticipantsTable)
        .where(eq(challengeParticipantsTable.challengeId, id)),
      buildLeaderboard(id),
    ]);

    let isJoined = false;
    let myStats = null;
    let rewardReceived = null;
    if (playerId) {
      const [participation] = await db
        .select()
        .from(challengeParticipantsTable)
        .where(
          and(
            eq(challengeParticipantsTable.challengeId, id),
            eq(challengeParticipantsTable.playerId, playerId),
          ),
        )
        .limit(1);
      isJoined = !!participation;

      if (isJoined) {
        const entry = leaderboard.find((e) => e.playerId === playerId);
        if (entry) myStats = entry;

        if (challenge.status === 'completed') {
          const [rewardLog] = await db
            .select()
            .from(challengeRewardLogTable)
            .where(
              and(
                eq(challengeRewardLogTable.challengeId, id),
                eq(challengeRewardLogTable.playerId, playerId),
              ),
            )
            .limit(1);
          if (rewardLog) {
            rewardReceived = { amount: rewardLog.amount, rank: rewardLog.rank };
          }
        }
      }
    }

    res.json({
      ...challenge,
      participantCount: participantRow[0]?.count ?? 0,
      leaderboard,
      isJoined,
      myStats,
      rewardReceived,
    });
  } catch {
    res.status(500).json({ error: 'Failed to fetch challenge' });
  }
});

// POST /challenges/:id/join — join a challenge (deduct entry fee atomically)
challengesRouter.post('/challenges/:id/join', async (req, res) => {
  const { id } = req.params;
  const { playerId } = req.body as { playerId?: string };

  if (!playerId) {
    res.status(400).json({ error: 'playerId is required' });
    return;
  }

  try {
    const [challenge] = await db
      .select()
      .from(challengesTable)
      .where(eq(challengesTable.id, id))
      .limit(1);

    if (!challenge) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }

    if (challenge.status !== 'active') {
      res.status(409).json({ error: 'Challenge is not currently active' });
      return;
    }

    if (challenge.maxParticipants) {
      const [countRow] = await db
        .select({ count: sql<number>`cast(count(*) as int)` })
        .from(challengeParticipantsTable)
        .where(eq(challengeParticipantsTable.challengeId, id));
      if ((countRow?.count ?? 0) >= challenge.maxParticipants) {
        res.status(409).json({ error: 'Challenge is full' });
        return;
      }
    }

    const tokenBalance = await db.transaction(async (tx) => {
      if (challenge.entryFee > 0) {
        const [player] = await tx
          .select({ tokenBalance: playersTable.tokenBalance })
          .from(playersTable)
          .where(eq(playersTable.id, playerId))
          .limit(1);

        if (!player) throw new Error('PLAYER_NOT_FOUND');
        if (player.tokenBalance < challenge.entryFee) throw new Error('INSUFFICIENT_TOKENS');

        await tx
          .update(playersTable)
          .set({ tokenBalance: sql`${playersTable.tokenBalance} - ${challenge.entryFee}` })
          .where(eq(playersTable.id, playerId));

        await tx.insert(tokenTransactionsTable).values({
          playerId,
          amount: -challenge.entryFee,
          type: 'challenge_entry_fee',
          description: `Entered challenge: ${challenge.title}`,
        });
      }

      await tx.insert(challengeParticipantsTable).values({ challengeId: id, playerId });

      const [updated] = await tx
        .select({ tokenBalance: playersTable.tokenBalance })
        .from(playersTable)
        .where(eq(playersTable.id, playerId))
        .limit(1);

      return updated?.tokenBalance ?? 0;
    });

    res.json({ ok: true, tokenBalance });
  } catch (err) {
    if (err instanceof Error) {
      if (err.message === 'PLAYER_NOT_FOUND') {
        res.status(404).json({ error: 'Player not found' });
        return;
      }
      if (err.message === 'INSUFFICIENT_TOKENS') {
        res.status(402).json({ error: 'Insufficient tokens' });
        return;
      }
    }
    if (
      typeof err === 'object' &&
      err !== null &&
      'code' in err &&
      (err as { code: string }).code === '23505'
    ) {
      res.status(409).json({ error: 'Already joined this challenge' });
      return;
    }
    res.status(500).json({ error: 'Failed to join challenge' });
  }
});

// POST /challenges/:id/start-match
// Body: { participantId }
// Issues a server-signed one-time match token. Must be called before the game starts.
// The token is required when submitting the match result — prevents win fabrication.
challengesRouter.post('/challenges/:id/start-match', async (req, res) => {
  const { id } = req.params;
  const { participantId } = req.body as { participantId?: string };

  if (!participantId) {
    res.status(400).json({ error: 'participantId is required' });
    return;
  }

  try {
    const [challenge] = await db
      .select()
      .from(challengesTable)
      .where(eq(challengesTable.id, id))
      .limit(1);

    if (!challenge) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }

    if (challenge.status !== 'active') {
      res.status(409).json({ error: 'Challenge is not active' });
      return;
    }

    const [participant] = await db
      .select()
      .from(challengeParticipantsTable)
      .where(
        and(
          eq(challengeParticipantsTable.challengeId, id),
          eq(challengeParticipantsTable.playerId, participantId),
        ),
      )
      .limit(1);

    if (!participant) {
      res.status(403).json({ error: 'Player has not joined this challenge' });
      return;
    }

    const { tokenId, signature, expiresAt } = makeMatchToken(id, participantId);

    await db.insert(challengeMatchTokensTable).values({
      tokenId,
      challengeId: id,
      playerId: participantId,
      expiresAt,
    });

    res.json({ matchToken: `${tokenId}.${signature}`, expiresAt: expiresAt.toISOString() });
  } catch {
    res.status(500).json({ error: 'Failed to issue match token' });
  }
});

// POST /challenges/:id/record-match
// Body: { participantId, won, isDraw, matchToken }
// matchToken must be the one-time token issued by /start-match.
// Stores winner_id/loser_id per the data model.
challengesRouter.post('/challenges/:id/record-match', async (req, res) => {
  const { id } = req.params;
  const { participantId, won, isDraw, matchToken } = req.body as {
    participantId?: string;
    won?: boolean;
    isDraw?: boolean;
    matchToken?: string;
  };

  if (participantId === undefined || won === undefined || !matchToken) {
    res.status(400).json({ error: 'participantId, won, and matchToken are required' });
    return;
  }

  // Validate the match token: split off the signature, verify HMAC, check expiry/ownership
  const lastDot = matchToken.lastIndexOf('.');
  if (lastDot === -1) {
    res.status(401).json({ error: 'Invalid match token format' });
    return;
  }
  const tokenId = matchToken.slice(0, lastDot);
  const signature = matchToken.slice(lastDot + 1);

  if (!verifyMatchTokenSignature(tokenId, signature)) {
    res.status(401).json({ error: 'Invalid match token signature' });
    return;
  }

  // Quick pre-flight validation before opening a transaction (non-authoritative reads)
  try {
    const [tokenRow] = await db
      .select()
      .from(challengeMatchTokensTable)
      .where(eq(challengeMatchTokensTable.tokenId, tokenId))
      .limit(1);

    if (!tokenRow) {
      res.status(401).json({ error: 'Match token not found' });
      return;
    }
    if (tokenRow.usedAt) {
      res.status(409).json({ error: 'Match token already used' });
      return;
    }
    if (new Date(tokenRow.expiresAt) < new Date()) {
      res.status(401).json({ error: 'Match token expired' });
      return;
    }
    if (tokenRow.challengeId !== id || tokenRow.playerId !== participantId) {
      res.status(403).json({ error: 'Match token does not match this challenge/player' });
      return;
    }
  } catch {
    res.status(500).json({ error: 'Failed to validate match token' });
    return;
  }

  // All remaining work runs inside a single transaction. The token is consumed
  // first with a RETURNING clause — if no row comes back, another concurrent
  // request already used it, and we abort before touching any stats.
  try {
    await db.transaction(async (tx) => {
      // Atomic consume: only succeeds if token is still unused and not expired.
      const consumed = await tx
        .update(challengeMatchTokensTable)
        .set({ usedAt: new Date() })
        .where(and(
          eq(challengeMatchTokensTable.tokenId, tokenId),
          isNull(challengeMatchTokensTable.usedAt),
          sql`${challengeMatchTokensTable.expiresAt} > now()`,
        ))
        .returning({ tokenId: challengeMatchTokensTable.tokenId });

      if (consumed.length === 0) {
        // Token was already consumed by a concurrent request or expired between checks.
        throw Object.assign(new Error('token_conflict'), { status: 409, msg: 'Match token already used or expired' });
      }

      const [challenge] = await tx
        .select({ status: challengesTable.status })
        .from(challengesTable)
        .where(eq(challengesTable.id, id))
        .limit(1);

      if (!challenge || challenge.status !== 'active') {
        throw Object.assign(new Error('challenge_inactive'), { status: 409, msg: 'Challenge is not active' });
      }

      const draw = isDraw === true;
      const playerWon = !draw && won === true;
      const playerLost = !draw && won === false;

      // matchTokenId is stored for DB-level idempotency (UNIQUE constraint prevents duplicate match rows per token)
      await tx.insert(challengeMatchesTable).values({
        challengeId: id,
        matchTokenId: tokenId,
        winnerId: playerWon ? participantId : null,
        loserId: playerLost ? participantId : null,
      });

      if (playerWon) {
        await tx
          .update(challengeParticipantsTable)
          .set({ wins: sql`${challengeParticipantsTable.wins} + 1` })
          .where(
            and(
              eq(challengeParticipantsTable.challengeId, id),
              eq(challengeParticipantsTable.playerId, participantId),
            ),
          );
      } else if (playerLost) {
        await tx
          .update(challengeParticipantsTable)
          .set({ losses: sql`${challengeParticipantsTable.losses} + 1` })
          .where(
            and(
              eq(challengeParticipantsTable.challengeId, id),
              eq(challengeParticipantsTable.playerId, participantId),
            ),
          );
      }
    });

    res.json({ ok: true });
  } catch (err: unknown) {
    const e = err as { status?: number; msg?: string };
    if (e.status) {
      res.status(e.status).json({ error: e.msg });
    } else {
      res.status(500).json({ error: 'Failed to record match' });
    }
  }
});

// ── Admin routes ──────────────────────────────────────────────────────────────

// GET /admin/challenges — all challenges + top-3 leaderboard each
challengesRouter.get('/admin/challenges', requireAdmin, async (req, res) => {
  try {
    const rows = await db
      .select()
      .from(challengesTable)
      .orderBy(desc(challengesTable.createdAt));

    const countMap = await fetchParticipantCounts(rows.map((r) => r.id));

    const enriched = await Promise.all(
      rows.map(async (r) => ({
        ...r,
        participantCount: countMap[r.id] ?? 0,
        topLeaderboard: (await buildLeaderboard(r.id)).slice(0, 3),
      })),
    );

    res.json(enriched);
  } catch {
    res.status(500).json({ error: 'Failed to fetch challenges' });
  }
});

// POST /admin/challenges — create a challenge
challengesRouter.post('/admin/challenges', requireAdmin, async (req, res) => {
  const {
    title, description, entryFee, prizePool,
    rank1Reward, rank2Reward, rank3Reward,
    startsAt, endsAt, maxParticipants,
  } = req.body as {
    title?: string; description?: string; entryFee?: number; prizePool?: number;
    rank1Reward?: number; rank2Reward?: number; rank3Reward?: number;
    startsAt?: string; endsAt?: string; maxParticipants?: number;
  };

  if (!title || !startsAt || !endsAt) {
    res.status(400).json({ error: 'title, startsAt, endsAt are required' });
    return;
  }

  const start = new Date(startsAt);
  const end = new Date(endsAt);
  if (isNaN(start.getTime()) || isNaN(end.getTime())) {
    res.status(400).json({ error: 'Invalid date format' });
    return;
  }
  if (end <= start) {
    res.status(400).json({ error: 'endsAt must be after startsAt' });
    return;
  }

  try {
    const now = new Date();
    const status = start <= now ? 'active' : 'upcoming';

    const [challenge] = await db
      .insert(challengesTable)
      .values({
        title, description: description ?? null,
        entryFee: entryFee ?? 0, prizePool: prizePool ?? 0,
        rank1Reward: rank1Reward ?? null, rank2Reward: rank2Reward ?? null, rank3Reward: rank3Reward ?? null,
        status, startAt: start, endAt: end, maxParticipants: maxParticipants ?? null,
      })
      .returning();

    res.json(challenge);
  } catch {
    res.status(500).json({ error: 'Failed to create challenge' });
  }
});

// PATCH /admin/challenges/:id — update a challenge
challengesRouter.patch('/admin/challenges/:id', requireAdmin, async (req, res) => {
  const { id } = req.params;
  const {
    title, description, entryFee, prizePool,
    rank1Reward, rank2Reward, rank3Reward,
    startsAt, endsAt, maxParticipants, status,
  } = req.body as {
    title?: string; description?: string; entryFee?: number; prizePool?: number;
    rank1Reward?: number; rank2Reward?: number; rank3Reward?: number;
    startsAt?: string; endsAt?: string; maxParticipants?: number; status?: string;
  };

  try {
    const [existing] = await db
      .select()
      .from(challengesTable)
      .where(eq(challengesTable.id, id))
      .limit(1);

    if (!existing) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }

    const updates: Partial<typeof challengesTable.$inferInsert> = {};
    if (title !== undefined) updates.title = title;
    if (description !== undefined) updates.description = description;
    if (entryFee !== undefined) updates.entryFee = entryFee;
    if (prizePool !== undefined) updates.prizePool = prizePool;
    if (rank1Reward !== undefined) updates.rank1Reward = rank1Reward;
    if (rank2Reward !== undefined) updates.rank2Reward = rank2Reward;
    if (rank3Reward !== undefined) updates.rank3Reward = rank3Reward;
    if (maxParticipants !== undefined) updates.maxParticipants = maxParticipants;
    if (status !== undefined) updates.status = status;
    if (startsAt !== undefined) updates.startAt = new Date(startsAt);
    if (endsAt !== undefined) updates.endAt = new Date(endsAt);

    const [updated] = await db
      .update(challengesTable)
      .set(updates)
      .where(eq(challengesTable.id, id))
      .returning();

    res.json(updated);
  } catch {
    res.status(500).json({ error: 'Failed to update challenge' });
  }
});

// POST /admin/challenges/:id/complete — distribute rewards
challengesRouter.post('/admin/challenges/:id/complete', requireAdmin, async (req, res) => {
  const { id } = req.params;
  try {
    const result = await completeChallengeById(id);
    if (!result.ok) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }
    res.json(result);
  } catch {
    res.status(500).json({ error: 'Failed to complete challenge' });
  }
});

// POST /admin/challenges/:id/cancel — cancel and refund entry fees
challengesRouter.post('/admin/challenges/:id/cancel', requireAdmin, async (req, res) => {
  const { id } = req.params;

  try {
    const [challenge] = await db
      .select()
      .from(challengesTable)
      .where(eq(challengesTable.id, id))
      .limit(1);

    if (!challenge) {
      res.status(404).json({ error: 'Challenge not found' });
      return;
    }

    if (challenge.status === 'cancelled') {
      res.json({ ok: true, message: 'Already cancelled', alreadyDone: true });
      return;
    }

    if (challenge.status === 'completed' || challenge.rewardsDistributed) {
      res.status(409).json({ error: 'Cannot cancel a completed challenge — rewards have already been distributed' });
      return;
    }

    const participants = await db
      .select()
      .from(challengeParticipantsTable)
      .where(eq(challengeParticipantsTable.challengeId, id));

    let refunded = 0;
    await db.transaction(async (tx) => {
      // Gate the status flip inside the transaction: only proceed if the challenge
      // is currently NOT cancelled or completed (concurrent requests will get 0 rows back).
      const updated = await tx
        .update(challengesTable)
        .set({ status: 'cancelled' })
        .where(
          and(
            eq(challengesTable.id, id),
            sql`${challengesTable.status} NOT IN ('cancelled', 'completed')`,
          ),
        )
        .returning({ id: challengesTable.id });

      if (updated.length === 0) {
        // Already cancelled or completed by a concurrent request — no-op refunds.
        return;
      }

      if (challenge.entryFee > 0) {
        for (const p of participants) {
          await tx
            .update(playersTable)
            .set({ tokenBalance: sql`${playersTable.tokenBalance} + ${challenge.entryFee}` })
            .where(eq(playersTable.id, p.playerId));

          await tx.insert(tokenTransactionsTable).values({
            playerId: p.playerId,
            amount: challenge.entryFee,
            type: 'refund',
            description: `Refund: cancelled challenge "${challenge.title}"`,
          });
        }
        refunded = participants.length;
      }
    });

    res.json({ ok: true, refunded });
  } catch {
    res.status(500).json({ error: 'Failed to cancel challenge' });
  }
});

export default challengesRouter;
