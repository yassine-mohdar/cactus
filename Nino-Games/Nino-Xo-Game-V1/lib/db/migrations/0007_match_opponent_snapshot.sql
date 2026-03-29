-- Add opponent snapshot columns to matches table
-- These store the opponent's display name and character ID at match start time,
-- so match history can render opponent info without a join or live lookup.
ALTER TABLE matches ADD COLUMN IF NOT EXISTS opponent_name varchar;
ALTER TABLE matches ADD COLUMN IF NOT EXISTS opponent_character_id varchar;
