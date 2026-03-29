import { pgTable, text, integer, timestamp, serial, unique } from 'drizzle-orm/pg-core';

export type TokenTransactionType =
  | 'match_win_reward'
  | 'challenge_entry_fee'
  | 'challenge_reward'
  | 'admin_adjustment'
  | 'refund';

export const tokenTransactionsTable = pgTable(
  'token_transactions',
  {
    id: serial('id').primaryKey(),
    playerId: text('player_id').notNull(),
    amount: integer('amount').notNull(),
    type: text('type').notNull().$type<TokenTransactionType>(),
    description: text('description'),
    relatedMatchId: text('related_match_id'),
    createdAt: timestamp('created_at').notNull().defaultNow(),
  },
  (t) => [
    unique('token_tx_player_match_unique').on(t.playerId, t.relatedMatchId),
  ],
);

export type TokenTransaction = typeof tokenTransactionsTable.$inferSelect;
