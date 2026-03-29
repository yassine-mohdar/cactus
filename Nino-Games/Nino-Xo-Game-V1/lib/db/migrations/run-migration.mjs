/**
 * Migration runner — applies all pending .sql files in this directory
 * in lexicographic order, skipping already-applied ones.
 *
 * ## DEPLOYMENT RUNBOOK
 * Before deploying a new version of the API server:
 * 1. Ensure DATABASE_URL is set in the deployment environment.
 * 2. Run this script BEFORE starting the server:
 *      node lib/db/migrations/run-migration.mjs
 * 3. The script applies only unapplied migrations and records them in
 *    the `schema_migrations` table. It is safe to run multiple times.
 * 4. New auth routes require the tables from 0004_auth_tables.sql to exist.
 *    If these tables are missing, the server will start but auth endpoints
 *    will return 500 errors at runtime.
 *
 * ## NOTES
 * - Rate limiting is in-memory only; acceptable for a single instance.
 *   Use Redis or DB-backed rate limiting before horizontal scaling.
 * - The pg client is bundled in lib/db/node_modules/pg — no extra install needed.
 *
 * Idempotent: safe to run multiple times.
 */

import { createRequire } from 'module';
import { readdir, readFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(import.meta.url);
const { Pool } = require('../node_modules/pg/lib/index.js');

const __dirname = dirname(fileURLToPath(import.meta.url));

const pool = new Pool({ connectionString: process.env['DATABASE_URL'] });

async function run() {
  const client = await pool.connect();
  try {
    // Create migration tracking table if it doesn't exist
    await client.query(`
      CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP    NOT NULL DEFAULT NOW()
      )
    `);

    const files = (await readdir(__dirname))
      .filter(f => f.endsWith('.sql'))
      .sort();

    for (const file of files) {
      const row = await client.query(
        'SELECT filename FROM schema_migrations WHERE filename = $1',
        [file],
      );
      if (row.rows.length > 0) {
        console.log(`[migrations] Already applied: ${file}`);
        continue;
      }

      const sql = await readFile(join(__dirname, file), 'utf8');
      await client.query('BEGIN');
      try {
        await client.query(sql);
        await client.query(
          'INSERT INTO schema_migrations (filename) VALUES ($1)',
          [file],
        );
        await client.query('COMMIT');
        console.log(`[migrations] Applied: ${file}`);
      } catch (err) {
        await client.query('ROLLBACK');
        throw err;
      }
    }

    console.log('[migrations] All migrations applied successfully.');
  } finally {
    client.release();
    await pool.end();
  }
}

run().catch(err => {
  console.error('[migrations] Failed:', err);
  process.exit(1);
});
