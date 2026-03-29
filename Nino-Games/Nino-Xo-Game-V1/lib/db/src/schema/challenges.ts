import { pgTable, varchar, text, integer, boolean, timestamp, serial, primaryKey, unique } from 'drizzle-orm/pg-core';
import { sql } from 'drizzle-orm';
import { playersTable } from './players';

export const challengesTable = pgTable('challenges', {
  id: varchar('id').primaryKey().default(sql`gen_random_uuid()`),
  title: text('title').notNull(),
  description: text('description'),
  entryFee: integer('entry_fee').notNull().default(0),
  prizePool: integer('prize_pool').notNull().default(0),
  rank1Reward: integer('rank1_reward'),
  rank2Reward: integer('rank2_reward'),
  rank3Reward: integer('rank3_reward'),
  status: text('status').notNull().default('upcoming'),
  startAt: timestamp('start_at', { withTimezone: true }).notNull(),
  endAt: timestamp('end_at', { withTimezone: true }).notNull(),
  maxParticipants: integer('max_participants'),
  rewardsDistributed: boolean('rewards_distributed').notNull().default(false),
  createdBy: text('created_by').notNull().default('admin'),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
});

export const challengeParticipantsTable = pgTable(
  'challenge_participants',
  {
    challengeId: varchar('challenge_id').notNull().references(() => challengesTable.id),
    playerId: text('player_id').notNull().references(() => playersTable.id),
    wins: integer('wins').notNull().default(0),
    losses: integer('losses').notNull().default(0),
    joinedAt: timestamp('joined_at', { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [primaryKey({ columns: [t.challengeId, t.playerId] })],
);

export const challengeRewardLogTable = pgTable(
  'challenge_reward_log',
  {
    id: serial('id').primaryKey(),
    challengeId: varchar('challenge_id').notNull().references(() => challengesTable.id),
    playerId: text('player_id').notNull().references(() => playersTable.id),
    amount: integer('amount').notNull(),
    rank: integer('rank').notNull(),
    paidAt: timestamp('paid_at', { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [unique('challenge_reward_log_chal_player_unique').on(t.challengeId, t.playerId)],
);

/**
 * One-time use tokens issued by the server at match start.
 * Required to submit a match result — prevents fabricated win submissions.
 */
export const challengeMatchTokensTable = pgTable('challenge_match_tokens', {
  tokenId: text('token_id').primaryKey(),
  challengeId: varchar('challenge_id').notNull().references(() => challengesTable.id),
  playerId: text('player_id').notNull().references(() => playersTable.id),
  expiresAt: timestamp('expires_at', { withTimezone: true }).notNull(),
  usedAt: timestamp('used_at', { withTimezone: true }),
});

/**
 * One row per completed match in a challenge.
 * winner_id = the human player's ID when they won, null otherwise (bot win or draw).
 * loser_id  = the human player's ID when they lost, null otherwise (bot win or draw).
 * Both null on a draw.
 * match_token_id: UNIQUE — ensures each server-issued token can produce at most one match row,
 *                 providing DB-level idempotency even if the app logic has regressions.
 */
export const challengeMatchesTable = pgTable('challenge_matches', {
  id: varchar('id').primaryKey().default(sql`gen_random_uuid()`),
  challengeId: varchar('challenge_id').notNull().references(() => challengesTable.id),
  matchTokenId: text('match_token_id').unique().references(() => challengeMatchTokensTable.tokenId),
  winnerId: text('winner_id').references(() => playersTable.id),
  loserId: text('loser_id').references(() => playersTable.id),
  playedAt: timestamp('played_at', { withTimezone: true }).notNull().defaultNow(),
});

export type Challenge = typeof challengesTable.$inferSelect;
export type ChallengeParticipant = typeof challengeParticipantsTable.$inferSelect;
export type ChallengeMatch = typeof challengeMatchesTable.$inferSelect;
export type ChallengeRewardLog = typeof challengeRewardLogTable.$inferSelect;
export type ChallengeMatchToken = typeof challengeMatchTokensTable.$inferSelect;
