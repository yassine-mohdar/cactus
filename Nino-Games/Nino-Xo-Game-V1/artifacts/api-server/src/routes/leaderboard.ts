import { Router } from 'express';
import { db, playersTable } from '@workspace/db';
import { desc, eq, sql } from 'drizzle-orm';
import { optionalAuth } from '../middleware/auth.js';

const leaderboardRouter = Router();

function isUniqueViolation(err: unknown): boolean {
  return (
    typeof err === 'object' &&
    err !== null &&
    'code' in err &&
    (err as { code: string }).code === '23505'
  );
}

leaderboardRouter.post('/players/sync', optionalAuth, async (req, res) => {
  const { id, name, phoneNumber, country, characterId, hasSubscription, stats, earnedBadgeIds } = req.body;
  // `email` is intentionally NOT accepted here: email is managed exclusively by
  // the verified auth flows (OTP/Google/Apple). Accepting client-supplied email
  // in an unauthenticated endpoint allows pre-claiming another user's address and
  // can lead to account-linking hijacking.

  // When the request carries a valid JWT, the JWT identity is the authority.
  // body.id is optional for authenticated callers (derived from token).
  // If both are provided, they must match (IDOR guard).
  // Unauthenticated callers (legacy) must supply id in the body.
  if (req.player) {
    if (id && req.player.id !== id) {
      res.status(403).json({ error: 'Sync target id does not match authenticated player' });
      return;
    }
  } else {
    if (!id) {
      res.status(400).json({ error: 'id is required for unauthenticated requests' });
      return;
    }
  }

  if (!name) {
    res.status(400).json({ error: 'name is required' });
    return;
  }

  // The canonical player ID: JWT identity when auth is present, body id for legacy clients.
  const playerId = req.player ? req.player.id : (id as string);

  try {
    await db
      .insert(playersTable)
      .values({
        id: playerId,
        name,
        // email is not set here — use auth endpoints (OTP/Google/Apple) to bind email
        phoneNumber: phoneNumber ?? null,
        country: country ?? null,
        characterId: characterId ?? 'nino',
        hasSubscription: hasSubscription ?? false,
        totalMatches: stats?.totalMatches ?? 0,
        totalWins: stats?.totalWins ?? 0,
        totalLosses: stats?.totalLosses ?? 0,
        totalDraws: stats?.totalDraws ?? 0,
        streak: stats?.streak ?? 0,
        bestStreak: stats?.bestStreak ?? 0,
        earnedBadgeIds: earnedBadgeIds ?? [],
        lastSeenAt: new Date(),
      })
      .onConflictDoUpdate({
        target: playersTable.id,
        set: {
          name,
          // email is deliberately omitted — preserve whatever was set by auth flow
          phoneNumber: phoneNumber ?? null,
          country: country ?? null,
          characterId: characterId ?? 'nino',
          hasSubscription: hasSubscription ?? false,
          totalMatches: stats?.totalMatches ?? 0,
          totalWins: stats?.totalWins ?? 0,
          totalLosses: stats?.totalLosses ?? 0,
          totalDraws: stats?.totalDraws ?? 0,
          streak: stats?.streak ?? 0,
          bestStreak: stats?.bestStreak ?? 0,
          earnedBadgeIds: earnedBadgeIds ?? [],
          lastSeenAt: new Date(),
        },
      });

    // Return isAdmin from DB so the client can use it for UI role checks
    const [player] = await db
      .select({ isAdmin: playersTable.isAdmin })
      .from(playersTable)
      .where(eq(playersTable.id, playerId))
      .limit(1);

    res.json({ ok: true, isAdmin: player?.isAdmin ?? false });
  } catch (err) {
    if (isUniqueViolation(err)) {
      const detail = (err as { detail?: string }).detail ?? '';
      const field = detail.includes('email') ? 'email' : 'name';
      res.status(409).json({ error: 'Duplicate value', field });
      return;
    }
    res.status(500).json({ error: 'Failed to sync player' });
  }
});

leaderboardRouter.get('/leaderboard', async (req, res) => {
  const limitParam = Number(req.query['limit'] ?? 20);
  const limit = Math.min(Math.max(1, limitParam), 50);

  try {
    const players = await db
      .select({
        id: playersTable.id,
        name: playersTable.name,
        characterId: playersTable.characterId,
        hasSubscription: playersTable.hasSubscription,
        totalWins: playersTable.totalWins,
        totalLosses: playersTable.totalLosses,
        totalDraws: playersTable.totalDraws,
        totalMatches: playersTable.totalMatches,
        streak: playersTable.streak,
        bestStreak: playersTable.bestStreak,
        winRate: sql<number>`
          case when ${playersTable.totalMatches} > 0
          then round(${playersTable.totalWins}::numeric / ${playersTable.totalMatches} * 100)
          else 0 end
        `.as('win_rate'),
      })
      .from(playersTable)
      .where(sql`${playersTable.totalMatches} > 0`)
      .orderBy(desc(playersTable.totalWins), desc(playersTable.streak))
      .limit(limit);

    res.json(players);
  } catch {
    res.status(500).json({ error: 'Failed to fetch leaderboard' });
  }
});

export default leaderboardRouter;
