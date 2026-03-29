import { Router } from 'express';
import { db, followsTable, playersTable } from '@workspace/db';
import { eq, sql } from 'drizzle-orm';
import { profileRegistry } from '../socket/profileRegistry.js';

const profilesRouter = Router();

profilesRouter.get('/profiles/:profileId', async (req, res) => {
  const { profileId } = req.params;

  // Try live registry first (online player with real-time socket data)
  const liveProfile = profileRegistry.getByProfileId(profileId);

  // Fall back to persistent DB record if offline
  let baseProfile: Record<string, unknown> | null = null;

  if (liveProfile) {
    baseProfile = { ...liveProfile };
  } else {
    try {
      const rows = await db
        .select()
        .from(playersTable)
        .where(eq(playersTable.id, profileId))
        .limit(1);

      if (rows.length > 0) {
        const r = rows[0];
        baseProfile = {
          profileId: r.id,
          name: r.name,
          characterId: r.characterId,
          hasSubscription: r.hasSubscription,
          country: r.country ?? undefined,
          stats: {
            totalMatches: r.totalMatches,
            totalWins: r.totalWins,
            totalLosses: r.totalLosses,
            totalDraws: r.totalDraws,
            streak: r.streak,
            bestStreak: r.bestStreak,
          },
          earnedBadgeIds: r.earnedBadgeIds,
        };
      }
    } catch {
      // DB unavailable — fall through to 404
    }
  }

  if (!baseProfile) {
    res.status(404).json({ error: 'Profile not found' });
    return;
  }

  try {
    const [followerRow, followingRow] = await Promise.all([
      db
        .select({ count: sql<number>`cast(count(*) as int)` })
        .from(followsTable)
        .where(eq(followsTable.followeeId, profileId)),
      db
        .select({ count: sql<number>`cast(count(*) as int)` })
        .from(followsTable)
        .where(eq(followsTable.followerId, profileId)),
    ]);

    res.json({
      ...baseProfile,
      isOnline: profileRegistry.isOnline(profileId),
      isPlaying: profileRegistry.isPlayingByProfileId(profileId),
      socketId: profileRegistry.getSocketIdByProfileId(profileId),
      followerCount: followerRow[0]?.count ?? 0,
      followingCount: followingRow[0]?.count ?? 0,
    });
  } catch {
    res.json({
      ...baseProfile,
      isOnline: profileRegistry.isOnline(profileId),
      isPlaying: profileRegistry.isPlayingByProfileId(profileId),
      socketId: profileRegistry.getSocketIdByProfileId(profileId),
      followerCount: 0,
      followingCount: 0,
    });
  }
});

profilesRouter.get('/online', (_req, res) => {
  res.json({ count: profileRegistry.count() });
});

export default profilesRouter;
