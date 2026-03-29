-- Migration: Clean up orphan enum type (Task #4 fix)
-- Drops the 'auth_provider_type' type that was accidentally created by migration
-- 0004 before the enum name was aligned to match the Drizzle schema ('auth_provider').
-- Safe to run even if the type doesn't exist (IF EXISTS).

DROP TYPE IF EXISTS auth_provider_type;
