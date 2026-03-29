-- Fix follows table to match the Drizzle schema exactly.
--
-- The original table was created with:
--   id text PRIMARY KEY
--   follower_id text NOT NULL
--   following_id text NOT NULL  (wrong name — schema uses followee_id)
--   created_at timestamp NOT NULL DEFAULT now()
--   UNIQUE (follower_id, following_id)
--
-- The Drizzle schema defines:
--   follower_id text NOT NULL
--   followee_id text NOT NULL
--   created_at timestamp NOT NULL DEFAULT now()
--   PRIMARY KEY (follower_id, followee_id)
--
-- Two problems were causing all follows endpoints to 500:
--  1. Column name mismatch: following_id → followee_id
--  2. Spurious id column (text, NOT NULL, no default) causing Drizzle INSERTs to fail

-- Step 1: rename the column (done first so FK constraint names below are stable)
ALTER TABLE follows RENAME COLUMN following_id TO followee_id;

-- Step 2: drop the old id-based PK and the id column itself
ALTER TABLE follows DROP CONSTRAINT follows_pkey;
ALTER TABLE follows DROP COLUMN id;

-- Step 3: drop redundant UNIQUE constraint (will be replaced by the new PK)
ALTER TABLE follows DROP CONSTRAINT IF EXISTS follows_follower_id_following_id_key;

-- Step 4: add the composite PK that the Drizzle schema defines
ALTER TABLE follows ADD PRIMARY KEY (follower_id, followee_id);
