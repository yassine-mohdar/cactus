-- Admin Panel Enhancements
-- 1. Block/unblock support on players
ALTER TABLE players ADD COLUMN IF NOT EXISTS is_blocked boolean NOT NULL DEFAULT false;

-- 2. App settings key/value store
CREATE TABLE IF NOT EXISTS app_settings (
  key text PRIMARY KEY,
  value text NOT NULL DEFAULT '',
  updated_at timestamp NOT NULL DEFAULT now()
);

-- Seed default settings (idempotent)
INSERT INTO app_settings (key, value) VALUES 
  ('privacy_policy_url', ''),
  ('terms_url', ''),
  ('email_theme', 'dark')
ON CONFLICT (key) DO NOTHING;
