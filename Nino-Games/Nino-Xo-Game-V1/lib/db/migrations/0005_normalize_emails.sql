-- Migration: Normalize emails to lowercase (Task #4)
-- Backfills existing players.email and auth_providers.provider_id (email type)
-- to lowercase to ensure consistent case-insensitive account linking.
-- Skips rows that would cause a unique constraint collision (duplicate case-variant emails).

-- Normalize players.email to lowercase.
-- Where two players have emails that differ only by case (edge case from old sync data),
-- only the row with the highest sort-order id gets updated; the other is left unchanged
-- to avoid violating the unique constraint. This means one of the duplicate-email rows
-- retains its mixed-case value. Manual admin intervention is required for those rare
-- cases; this migration prioritizes not breaking existing accounts over full normalization.
UPDATE players
   SET email = lower(email)
 WHERE email IS NOT NULL
   AND email <> lower(email)
   AND NOT EXISTS (
     SELECT 1 FROM players p2
      WHERE lower(p2.email) = lower(players.email)
        AND p2.id < players.id
   );

-- Normalize auth_providers.provider_id to lowercase for email provider.
UPDATE auth_providers
   SET provider_id = lower(provider_id)
 WHERE provider = 'email'
   AND provider_id IS NOT NULL
   AND provider_id <> lower(provider_id);
