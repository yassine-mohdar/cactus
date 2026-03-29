import { Router } from 'express';
import { db, followsTable, playersTable } from '@workspace/db';
import { and, eq, inArray, sql } from 'drizzle-orm';
import { profileRegistry } from '../socket/profileRegistry.js';
import { emitToSocket } from '../socket/socketNotifier.js';

const followsRouter = Router();

followsRouter.post('/follows/:targetId', async (req, res) => {
  const { targetId } = req.params;
  const { followerId } = req.body as { followerId?: string };

  if (!followerId) {
    res.status(400).json({ error: 'followerId is required' });
    return;
  }

  if (followerId === targetId) {
    res.status(400).json({ error: 'Cannot follow yourself' });
    return;
  }

  try {
    const inserted = await db
      .insert(followsTable)
      .values({ followerId, followeeId: targetId })
      .onConflictDoNothing()
      .returning();

    if (inserted.length > 0) {
      const targetSocketId = profileRegistry.getSocketIdByProfileId(targetId);
      if (targetSocketId) {
        const followerRows = await db
          .select({ name: playersTable.name, characterId: playersTable.characterId })
          .from(playersTable)
          .where(eq(playersTable.id, followerId))
          .limit(1);
        const follower = followerRows[0];
        if (follower) {
          emitToSocket(targetSocketId, 'followReceived', {
            followerName: follower.name,
            followerCharacterId: follower.characterId,
            followerProfileId: followerId,
          });
        }
      }
    }

    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to follow' });
  }
});

followsRouter.delete('/follows/:targetId', async (req, res) => {
  const { targetId } = req.params;
  const followerId = (req.query['followerId'] ?? req.body?.followerId) as string | undefined;

  if (!followerId) {
    res.status(400).json({ error: 'followerId is required' });
    return;
  }

  try {
    await db
      .delete(followsTable)
      .where(
        and(
          eq(followsTable.followerId, followerId),
          eq(followsTable.followeeId, targetId),
        ),
      );
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to unfollow' });
  }
});

followsRouter.get('/follows/me', async (req, res) => {
  const followerId = req.query['followerId'] as string | undefined;

  if (!followerId) {
    res.status(400).json({ error: 'followerId is required' });
    return;
  }

  try {
    const rows = await db
      .select({ followeeId: followsTable.followeeId })
      .from(followsTable)
      .where(eq(followsTable.followerId, followerId));

    res.json({ following: rows.map((r) => r.followeeId) });
  } catch {
    res.status(500).json({ error: 'Failed to fetch follows' });
  }
});

followsRouter.get('/follows/counts/:profileId', async (req, res) => {
  const { profileId } = req.params;

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
      followerCount: followerRow[0]?.count ?? 0,
      followingCount: followingRow[0]?.count ?? 0,
    });
  } catch {
    res.status(500).json({ error: 'Failed to fetch counts' });
  }
});

followsRouter.get('/follows/friends', async (req, res) => {
  const profileId = req.query['profileId'] as string | undefined;

  if (!profileId) {
    res.status(400).json({ error: 'profileId is required' });
    return;
  }

  try {
    const f1 = followsTable;
    const mutualRows = await db
      .select({ friendId: f1.followeeId })
      .from(f1)
      .where(
        and(
          eq(f1.followerId, profileId),
          inArray(
            f1.followeeId,
            db
              .select({ id: followsTable.followerId })
              .from(followsTable)
              .where(eq(followsTable.followeeId, profileId)),
          ),
        ),
      );

    const friendIds = mutualRows.map((r) => r.friendId);

    if (friendIds.length === 0) {
      res.json({ friends: [] });
      return;
    }

    const dbPlayers = await db
      .select()
      .from(playersTable)
      .where(inArray(playersTable.id, friendIds));

    const friends = dbPlayers.map((p) => {
      const socketId = profileRegistry.getSocketIdByProfileId(p.id);
      const isOnline = profileRegistry.isOnline(p.id);
      const isPlaying = profileRegistry.isPlayingByProfileId(p.id);
      return {
        id: p.id,
        name: p.name,
        characterId: p.characterId,
        hasSubscription: p.hasSubscription,
        isOnline,
        isPlaying,
        socketId: socketId ?? null,
        stats: {
          totalMatches: p.totalMatches,
          totalWins: p.totalWins,
          totalLosses: p.totalLosses,
          totalDraws: p.totalDraws,
          streak: p.streak,
          bestStreak: p.bestStreak,
        },
      };
    });

    friends.sort((a, b) => {
      if (a.isOnline !== b.isOnline) return a.isOnline ? -1 : 1;
      return a.name.localeCompare(b.name);
    });

    res.json({ friends });
  } catch (err) {
    console.error('Friends endpoint error:', err);
    res.status(500).json({ error: 'Failed to fetch friends' });
  }
});

export default followsRouter;
