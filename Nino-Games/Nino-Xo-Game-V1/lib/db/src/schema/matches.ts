import { pgTable, varchar, boolean, timestamp, text } from 'drizzle-orm/pg-core';
import { sql } from 'drizzle-orm';
import { playersTable } from './players';

export const matchesTable = pgTable('matches', {
  id: varchar('id').primaryKey().default(sql`gen_random_uuid()`),
  playerId: varchar('player_id').notNull().references(() => playersTable.id),
  mode: varchar('mode').notNull().default('bot'),
  playerSymbol: varchar('player_symbol', { length: 1 }),
  board: text('board'),
  gameResult: varchar('game_result', { length: 10 }),
  tokensAwarded: boolean('tokens_awarded').notNull().default(false),
  opponentName: varchar('opponent_name'),
  opponentCharacterId: varchar('opponent_character_id'),
  createdAt: timestamp('created_at', { withTimezone: true }).notNull().defaultNow(),
  endedAt: timestamp('ended_at', { withTimezone: true }),
  expiresAt: timestamp('expires_at', { withTimezone: true }).notNull().default(sql`now() + interval '2 hours'`),
});
