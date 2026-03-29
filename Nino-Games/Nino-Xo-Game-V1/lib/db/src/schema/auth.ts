import { pgTable, text, timestamp, pgEnum, unique } from 'drizzle-orm/pg-core';
import { playersTable } from './players';

export const authProviderEnum = pgEnum('auth_provider', ['google', 'apple', 'email']);

export const authProvidersTable = pgTable(
  'auth_providers',
  {
    id: text('id').primaryKey(),
    playerId: text('player_id')
      .notNull()
      .references(() => playersTable.id, { onDelete: 'cascade' }),
    provider: authProviderEnum('provider').notNull(),
    providerId: text('provider_id').notNull(),
    createdAt: timestamp('created_at').notNull().defaultNow(),
  },
  (t) => [unique('auth_providers_provider_id_unique').on(t.provider, t.providerId)],
);

export const otpTokensTable = pgTable('otp_tokens', {
  id: text('id').primaryKey(),
  email: text('email').notNull(),
  codeHash: text('code_hash').notNull(),
  expiresAt: timestamp('expires_at').notNull(),
  usedAt: timestamp('used_at'),
  createdAt: timestamp('created_at').notNull().defaultNow(),
});

export type AuthProvider = typeof authProvidersTable.$inferSelect;
export type OtpToken = typeof otpTokensTable.$inferSelect;
