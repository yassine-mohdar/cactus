import { pgTable, text, timestamp, primaryKey } from 'drizzle-orm/pg-core';

export const followsTable = pgTable(
  'follows',
  {
    followerId: text('follower_id').notNull(),
    followeeId: text('followee_id').notNull(),
    createdAt: timestamp('created_at').notNull().defaultNow(),
  },
  (t) => [primaryKey({ columns: [t.followerId, t.followeeId] })],
);

export type Follow = typeof followsTable.$inferSelect;
