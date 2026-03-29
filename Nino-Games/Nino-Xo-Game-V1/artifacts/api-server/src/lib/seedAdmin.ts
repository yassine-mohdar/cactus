import { randomBytes } from 'crypto';
import { db, playersTable, authProvidersTable } from '@workspace/db';
import { eq, sql } from 'drizzle-orm';
import { ADMIN_EMAIL } from '../config.js';

export async function seedAdmin(): Promise<void> {
  try {
    // Normalize to lowercase so case variations in ADMIN_EMAIL don't create mismatches
    const adminEmail = (ADMIN_EMAIL ?? 'admin@ninoxo.app').toLowerCase().trim();

    // Fast path: at least one admin already exists.
    // Still ensure they have an email and an email auth_provider link so they can
    // sign in via OTP. This handles legacy admin rows that may lack email.
    const existing = await db
      .select({ id: playersTable.id, email: playersTable.email })
      .from(playersTable)
      .where(eq(playersTable.isAdmin, true))
      .limit(1);

    if (existing.length > 0) {
      const admin = existing[0];
      // If the existing admin has no email, set it to adminEmail
      const effectiveEmail = admin.email ?? adminEmail;
      if (!admin.email) {
        await db
          .update(playersTable)
          .set({ email: adminEmail })
          .where(eq(playersTable.id, admin.id));
        console.info(`[auth] Backfilled email for existing admin (id: ${admin.id}) → ${adminEmail}`);
      } else {
        console.info(`[auth] Admin account exists (id: ${admin.id}, email: ${effectiveEmail})`);
      }

      // Backfill: ensure the admin has an email provider link for OTP sign-in
      await db
        .insert(authProvidersTable)
        .values({
          id: `ap_${randomBytes(8).toString('hex')}`,
          playerId: admin.id,
          provider: 'email',
          providerId: effectiveEmail,
        })
        .onConflictDoNothing();
      return;
    }

    // Check whether the target email already belongs to a non-admin player.
    // If so, promote that player to admin rather than trying (and failing due to
    // email uniqueness) to insert a duplicate row.
    const [existingByEmail] = await db
      .select({ id: playersTable.id })
      .from(playersTable)
      .where(sql`lower(${playersTable.email}) = ${adminEmail}`)
      .limit(1);

    let resolvedId: string;

    if (existingByEmail) {
      // Promote the existing player to admin
      await db
        .update(playersTable)
        .set({ isAdmin: true })
        .where(eq(playersTable.id, existingByEmail.id));
      resolvedId = existingByEmail.id;
      console.info(`[auth] Promoted existing player to admin — id: ${resolvedId}, email: ${adminEmail}`);
    } else {
      // Create a fresh admin player.
      // Use a hex-suffixed name to avoid unique-name constraint collisions if a
      // non-admin player already has the display name "Admin".
      const adminId = `admin_${randomBytes(8).toString('hex')}`;
      const suffix = randomBytes(3).toString('hex');
      await db.insert(playersTable).values({
        id: adminId,
        name: `Admin_${suffix}`,
        email: adminEmail,
        isAdmin: true,
      });
      resolvedId = adminId;
      console.info(`[auth] Default admin account created — email: ${adminEmail}`);
    }

    // Ensure an email auth_provider link exists so admin can sign in via OTP
    await db
      .insert(authProvidersTable)
      .values({
        id: `ap_${randomBytes(8).toString('hex')}`,
        playerId: resolvedId,
        provider: 'email',
        providerId: adminEmail,
      })
      .onConflictDoNothing();

    console.info('[auth] Admin can sign in via Email OTP using the address above.');
    console.info('[auth] To change the admin email, set the ADMIN_EMAIL environment variable.');
  } catch (err) {
    console.error('[auth] Admin seeding failed:', err);
  }
}
