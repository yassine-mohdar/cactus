import { randomBytes } from 'crypto';

// =============================================================================
//  Nino XO — API Server Configuration
//  All environment variables are read here and exported as typed constants.
//  Set these in Replit Secrets (lock icon in the sidebar).
// =============================================================================

// ── Server ────────────────────────────────────────────────────────────────────

export const PORT = parseInt(process.env['PORT'] ?? '8080', 10);
export const NODE_ENV = process.env['NODE_ENV'] ?? 'development';
export const IS_PRODUCTION = NODE_ENV === 'production';

// ── JWT ───────────────────────────────────────────────────────────────────────
// Secret used to sign and verify player session tokens.
// In production, set a long random string (e.g. openssl rand -base64 64).
// Without it, tokens are invalidated every server restart.

export const JWT_SECRET: string = (() => {
  const secret = process.env['JWT_SECRET'];
  if (!secret) {
    if (IS_PRODUCTION) {
      console.error('[config] JWT_SECRET is required in production.');
    } else {
      console.warn('[config] JWT_SECRET not set — using ephemeral secret (sessions reset on restart).');
    }
    return randomBytes(64).toString('hex');
  }
  return secret;
})();

// ── Email / SMTP ──────────────────────────────────────────────────────────────
// Provider priority: Gmail SMTP → Resend → console log (dev only).
//
// Gmail SMTP:
//   GMAIL_USER         — your Gmail address (e.g. you@gmail.com)
//   GMAIL_APP_PASSWORD — 16-char App Password from Google Account → Security
//                        → 2-Step Verification → App Passwords
//
// Resend (fallback):
//   RESEND_API_KEY     — API key from resend.com
//
// Sender address:
//   OTP_FROM_EMAIL     — the "From" address shown in OTP emails
//                        (defaults to GMAIL_USER if Gmail is configured,
//                         or noreply@ninoxo.app otherwise)

export const GMAIL_USER        = process.env['GMAIL_USER'] ?? null;
export const GMAIL_APP_PASSWORD = process.env['GMAIL_APP_PASSWORD'] ?? null;
export const RESEND_API_KEY    = process.env['RESEND_API_KEY'] ?? null;
export const OTP_FROM_EMAIL    = process.env['OTP_FROM_EMAIL']
  ?? GMAIL_USER
  ?? 'noreply@ninoxo.app';

// ── Google OAuth ──────────────────────────────────────────────────────────────
// Used to validate Google ID tokens on the backend.
// Get from: Google Cloud Console → APIs & Credentials → OAuth 2.0 Client IDs
//   GOOGLE_CLIENT_ID — Web application client ID

export const GOOGLE_CLIENT_ID = process.env['GOOGLE_CLIENT_ID'] ?? null;

// ── Apple OAuth ───────────────────────────────────────────────────────────────
// Used to validate Apple identity tokens on the backend.
// Get from: Apple Developer → Certificates, IDs & Profiles → Services IDs
//   APPLE_CLIENT_ID  — your Apple Services ID (e.g. com.nino.xo.app.signin)

export const APPLE_CLIENT_ID = process.env['APPLE_CLIENT_ID'] ?? null;

// ── Admin ─────────────────────────────────────────────────────────────────────
// The player who signs in with this email address is automatically given
// admin privileges. Set to your own email address.

export const ADMIN_EMAIL = process.env['ADMIN_EMAIL'] ?? null;

// ── Game ──────────────────────────────────────────────────────────────────────
// WIN_REWARD_AMOUNT  — tokens awarded for winning a match (default: 50)
// MATCH_TOKEN_SECRET — secret used to sign match result tokens (auto-generated
//                      per restart if not set; set in production for consistency)

export const WIN_REWARD_AMOUNT = parseInt(process.env['WIN_REWARD_AMOUNT'] ?? '50', 10);
export const MATCH_TOKEN_SECRET: string = (() => {
  const secret = process.env['MATCH_TOKEN_SECRET'];
  if (!secret) {
    if (IS_PRODUCTION) {
      console.warn('[config] MATCH_TOKEN_SECRET not set — using ephemeral secret.');
    }
    return randomBytes(32).toString('hex');
  }
  return secret;
})();
