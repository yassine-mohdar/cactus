-- Migration: Auth Tables (Task #4)
-- Adds is_admin to players, and creates auth_providers + otp_tokens tables.
-- Idempotent: safe to run multiple times.

-- Add is_admin column to players (default false, not null)
ALTER TABLE players
  ADD COLUMN IF NOT EXISTS is_admin BOOLEAN NOT NULL DEFAULT FALSE;

-- Provider enum type: named 'auth_provider' to match Drizzle schema definition.
-- Create only if it doesn't already exist (Drizzle may have created it already).
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'auth_provider') THEN
    CREATE TYPE auth_provider AS ENUM ('google', 'apple', 'email');
  END IF;
END
$$;

-- auth_providers: links players to OAuth/email identity providers.
-- (provider, provider_id) is unique to prevent one identity from being linked twice.
CREATE TABLE IF NOT EXISTS auth_providers (
  id           VARCHAR(64)       PRIMARY KEY,
  player_id    VARCHAR(64)       NOT NULL REFERENCES players(id) ON DELETE CASCADE,
  provider     auth_provider     NOT NULL,
  provider_id  VARCHAR(255)      NOT NULL,
  created_at   TIMESTAMP         NOT NULL DEFAULT NOW(),
  UNIQUE (provider, provider_id)
);

-- otp_tokens: one-time 6-digit verification codes for email sign-in.
-- code_hash stores SHA-256(code); used_at marks single-use consumption.
CREATE TABLE IF NOT EXISTS otp_tokens (
  id         VARCHAR(64)   PRIMARY KEY,
  email      VARCHAR(255)  NOT NULL,
  code_hash  VARCHAR(64)   NOT NULL,
  expires_at TIMESTAMP     NOT NULL,
  used_at    TIMESTAMP,
  created_at TIMESTAMP     NOT NULL DEFAULT NOW()
);

-- Index to speed up OTP lookup by email
CREATE INDEX IF NOT EXISTS idx_otp_tokens_email ON otp_tokens (email);
