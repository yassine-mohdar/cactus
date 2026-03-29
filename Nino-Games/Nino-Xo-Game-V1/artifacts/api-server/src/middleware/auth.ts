import type { Request, Response, NextFunction } from 'express';
import { verifyPlayerJwt } from '../lib/jwt.js';
import { db, playersTable } from '@workspace/db';
import { eq } from 'drizzle-orm';

declare global {
  namespace Express {
    interface Request {
      player?: { id: string; isAdmin: boolean };
    }
  }
}

/**
 * Fetch the player row and validate active status.
 * Returns the row on success, or responds with an error and returns null.
 */
async function resolvePlayer(
  playerId: string,
  res: Response,
): Promise<{ isBlocked: boolean; isAdmin: boolean } | null> {
  const [row] = await db
    .select({ isBlocked: playersTable.isBlocked, isAdmin: playersTable.isAdmin })
    .from(playersTable)
    .where(eq(playersTable.id, playerId))
    .limit(1);

  if (!row) {
    res.status(401).json({ error: 'Player not found' });
    return null;
  }

  if (row.isBlocked) {
    res.status(403).json({ error: 'Your account has been suspended. Please contact support.' });
    return null;
  }

  return row;
}

export async function requireAuth(
  req: Request,
  res: Response,
  next: NextFunction,
): Promise<void> {
  const authHeader = req.headers['authorization'];
  if (!authHeader?.startsWith('Bearer ')) {
    res.status(401).json({ error: 'Authentication required' });
    return;
  }
  const token = authHeader.slice(7);
  const payload = await verifyPlayerJwt(token);
  if (!payload) {
    res.status(401).json({ error: 'Invalid or expired token' });
    return;
  }

  // Live DB read: enforce block status and get current isAdmin from DB
  // (not from JWT claim, so demotion is effective immediately on next request)
  const row = await resolvePlayer(payload.playerId, res);
  if (!row) return;

  req.player = { id: payload.playerId, isAdmin: row.isAdmin };
  next();
}

export async function requireAdmin(
  req: Request,
  res: Response,
  next: NextFunction,
): Promise<void> {
  await requireAuth(req, res, async () => {
    // req.player.isAdmin is already sourced from the DB by requireAuth
    if (!req.player?.isAdmin) {
      res.status(403).json({ error: 'Admin access required' });
      return;
    }
    next();
  });
}

export async function optionalAuth(
  req: Request,
  res: Response,
  next: NextFunction,
): Promise<void> {
  const authHeader = req.headers['authorization'];
  if (authHeader?.startsWith('Bearer ')) {
    // When the Authorization header is explicitly set, a bad/expired token is a
    // hard error — silently falling through would hide client bugs and could
    // allow replayed old tokens to be ignored rather than rejected.
    const token = authHeader.slice(7);
    const payload = await verifyPlayerJwt(token);
    if (!payload) {
      res.status(401).json({ error: 'Invalid or expired token' });
      return;
    }

    // Apply same block + live isAdmin check as requireAuth
    const row = await resolvePlayer(payload.playerId, res);
    if (!row) return;

    req.player = { id: payload.playerId, isAdmin: row.isAdmin };
  }
  next();
}
