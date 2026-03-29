import { Router } from 'express';
import { db, playersTable, appSettingsTable } from '@workspace/db';
import { eq, ilike, or, sql } from 'drizzle-orm';
import { requireAdmin } from '../middleware/auth.js';

const adminRouter = Router();

// ── GET /admin/users?search= ──────────────────────────────────────────────────
// Returns a paginated list of players for admin user management.
adminRouter.get('/admin/users', requireAdmin, async (req, res) => {
  const { search } = req.query as { search?: string };
  const limit = 50;

  try {
    let query = db
      .select({
        id: playersTable.id,
        name: playersTable.name,
        email: playersTable.email,
        isAdmin: playersTable.isAdmin,
        isBlocked: playersTable.isBlocked,
        totalMatches: playersTable.totalMatches,
        totalWins: playersTable.totalWins,
        hasSubscription: playersTable.hasSubscription,
        createdAt: playersTable.createdAt,
        lastSeenAt: playersTable.lastSeenAt,
      })
      .from(playersTable)
      .$dynamic();

    if (search?.trim()) {
      const term = `%${search.trim()}%`;
      query = query.where(or(ilike(playersTable.name, term), ilike(playersTable.email, term)));
    }

    const users = await query
      .orderBy(sql`${playersTable.createdAt} DESC`)
      .limit(limit);

    res.json({ users });
  } catch {
    res.status(500).json({ error: 'Failed to fetch users' });
  }
});

// ── POST /admin/users/:id/block ───────────────────────────────────────────────
adminRouter.post('/admin/users/:id/block', requireAdmin, async (req, res) => {
  const { id } = req.params;

  // Prevent self-block
  if (id === req.player!.id) {
    res.status(400).json({ error: 'You cannot block your own account' });
    return;
  }

  try {
    const [updated] = await db
      .update(playersTable)
      .set({ isBlocked: true })
      .where(eq(playersTable.id, id))
      .returning({ id: playersTable.id });

    if (!updated) {
      res.status(404).json({ error: 'User not found' });
      return;
    }
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to block user' });
  }
});

// ── POST /admin/users/:id/unblock ─────────────────────────────────────────────
adminRouter.post('/admin/users/:id/unblock', requireAdmin, async (req, res) => {
  const { id } = req.params;

  try {
    const [updated] = await db
      .update(playersTable)
      .set({ isBlocked: false })
      .where(eq(playersTable.id, id))
      .returning({ id: playersTable.id });

    if (!updated) {
      res.status(404).json({ error: 'User not found' });
      return;
    }
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to unblock user' });
  }
});

// ── POST /admin/users/:id/set-admin ──────────────────────────────────────────
adminRouter.post('/admin/users/:id/set-admin', requireAdmin, async (req, res) => {
  const { id } = req.params;

  try {
    const [updated] = await db
      .update(playersTable)
      .set({ isAdmin: true })
      .where(eq(playersTable.id, id))
      .returning({ id: playersTable.id });

    if (!updated) {
      res.status(404).json({ error: 'User not found' });
      return;
    }
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to grant admin' });
  }
});

// ── POST /admin/users/:id/remove-admin ────────────────────────────────────────
adminRouter.post('/admin/users/:id/remove-admin', requireAdmin, async (req, res) => {
  const { id } = req.params;

  // Prevent removing own admin
  if (id === req.player!.id) {
    res.status(400).json({ error: 'You cannot remove your own admin access' });
    return;
  }

  try {
    const [updated] = await db
      .update(playersTable)
      .set({ isAdmin: false })
      .where(eq(playersTable.id, id))
      .returning({ id: playersTable.id });

    if (!updated) {
      res.status(404).json({ error: 'User not found' });
      return;
    }
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to remove admin' });
  }
});

// ── GET /app-settings (public) ────────────────────────────────────────────────
// Returns the current app settings (policy URLs, email theme).
// Falls back to the built-in /policy/* pages when no custom URL is configured.
adminRouter.get('/app-settings', async (req, res) => {
  try {
    const rows = await db.select().from(appSettingsTable);
    const settings: Record<string, string> = {};
    for (const row of rows) {
      settings[row.key] = row.value;
    }
    const baseUrl = `${req.protocol}://${req.get('host')}`;
    res.json({
      privacyPolicyUrl: settings['privacy_policy_url'] || `${baseUrl}/policy/privacy`,
      termsUrl: settings['terms_url'] || `${baseUrl}/policy/terms`,
      emailTheme: (settings['email_theme'] as 'dark' | 'light') ?? 'dark',
    });
  } catch {
    res.status(500).json({ error: 'Failed to load app settings' });
  }
});

// ── PUT /admin/app-settings ───────────────────────────────────────────────────
// Allows admin to update any combination of privacy_policy_url, terms_url, email_theme.
adminRouter.put('/admin/app-settings', requireAdmin, async (req, res) => {
  const { privacyPolicyUrl, termsUrl, emailTheme } = req.body as {
    privacyPolicyUrl?: string;
    termsUrl?: string;
    emailTheme?: string;
  };

  // Validate URL fields — must be empty string (clearing) or a valid https:// URL
  function isValidPolicyUrl(value: string): boolean {
    if (value === '') return true; // Allow clearing
    try {
      const url = new URL(value);
      return url.protocol === 'https:';
    } catch {
      return false;
    }
  }

  if (privacyPolicyUrl !== undefined && !isValidPolicyUrl(privacyPolicyUrl)) {
    res.status(400).json({ error: 'privacyPolicyUrl must be a valid https:// URL or empty string' });
    return;
  }
  if (termsUrl !== undefined && !isValidPolicyUrl(termsUrl)) {
    res.status(400).json({ error: 'termsUrl must be a valid https:// URL or empty string' });
    return;
  }

  const updates: { key: string; value: string }[] = [];
  if (privacyPolicyUrl !== undefined) updates.push({ key: 'privacy_policy_url', value: privacyPolicyUrl });
  if (termsUrl !== undefined) updates.push({ key: 'terms_url', value: termsUrl });
  if (emailTheme !== undefined) {
    if (!['dark', 'light'].includes(emailTheme)) {
      res.status(400).json({ error: 'emailTheme must be "dark" or "light"' });
      return;
    }
    updates.push({ key: 'email_theme', value: emailTheme });
  }

  if (updates.length === 0) {
    res.status(400).json({ error: 'No valid fields provided' });
    return;
  }

  try {
    await Promise.all(
      updates.map(({ key, value }) =>
        db
          .insert(appSettingsTable)
          .values({ key, value, updatedAt: new Date() })
          .onConflictDoUpdate({ target: appSettingsTable.key, set: { value, updatedAt: new Date() } }),
      ),
    );
    res.json({ ok: true });
  } catch {
    res.status(500).json({ error: 'Failed to save app settings' });
  }
});

export default adminRouter;
