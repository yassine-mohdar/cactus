import { pgTable, text, integer, boolean, timestamp } from 'drizzle-orm/pg-core';
import { createInsertSchema } from 'drizzle-zod';
import { z } from 'zod/v4';

export const playersTable = pgTable('players', {
  id: text('id').primaryKey(),
  name: text('name').notNull().unique(),
  email: text('email').unique(),
  phoneNumber: text('phone_number'),
  country: text('country'),
  characterId: text('character_id').notNull().default('nino'),
  hasSubscription: boolean('has_subscription').notNull().default(false),
  isAdmin: boolean('is_admin').notNull().default(false),
  isBlocked: boolean('is_blocked').notNull().default(false),
  totalMatches: integer('total_matches').notNull().default(0),
  totalWins: integer('total_wins').notNull().default(0),
  totalLosses: integer('total_losses').notNull().default(0),
  totalDraws: integer('total_draws').notNull().default(0),
  streak: integer('streak').notNull().default(0),
  bestStreak: integer('best_streak').notNull().default(0),
  earnedBadgeIds: text('earned_badge_ids').array().notNull().default([]),
  tokenBalance: integer('token_balance').notNull().default(0),
  lastSeenAt: timestamp('last_seen_at').notNull().defaultNow(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
});

export const insertPlayerSchema = createInsertSchema(playersTable).omit({ createdAt: true, lastSeenAt: true });
export type InsertPlayer = z.infer<typeof insertPlayerSchema>;
export type Player = typeof playersTable.$inferSelect;
