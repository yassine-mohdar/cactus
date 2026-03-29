# Nino XO — Game Reference

A premium subscriber-only 1v1 Tic-Tac-Toe mobile game set in the NinoWorld cactus universe.
Built as a pnpm monorepo: Expo React Native app, Express.js API server, PostgreSQL + Drizzle ORM, Socket.io real-time play, JWT auth with email OTP.

---

## Admin Account

| Field | Value |
|---|---|
| Player ID | `admin_1a6d5eceefe179a9` |
| Display name | `Admin_9b6358` |
| Email | `admin@ninoxo.app` |
| Sign-in method | Email OTP (passwordless — enter the email, receive a 6-digit code) |
| `is_admin` | `true` |
| `has_subscription` | `false` (admin bypasses subscription checks) |

To change the admin email, set the `ADMIN_EMAIL` environment variable before the server starts.
On first boot the server auto-creates the admin row if none exists, or promotes the matching email row if one is already there.

---

## Environment Variables / Secrets

All secrets are set in Replit Secrets (lock icon in the sidebar).

| Variable | Required | Description |
|---|---|---|
| `DATABASE_URL` | Yes | PostgreSQL connection string (auto-provided by Replit) |
| `JWT_SECRET` | Production | Long random string for signing player JWTs. Without it sessions reset on every server restart. Generate with `openssl rand -base64 64` |
| `MATCH_TOKEN_SECRET` | Production | Secret for signing match result tokens. Same warning as JWT_SECRET |
| `ADMIN_EMAIL` | Optional | Email address that receives admin privileges on first boot. Defaults to `admin@ninoxo.app` |
| `GMAIL_USER` | Optional* | Gmail address for sending emails (e.g. you@gmail.com) |
| `GMAIL_APP_PASSWORD` | Optional* | 16-char Google App Password (Account → Security → App Passwords) |
| `RESEND_API_KEY` | Optional* | API key from resend.com (used if Gmail not configured) |
| `OTP_FROM_EMAIL` | Optional | "From" address on emails. Defaults to `GMAIL_USER` or `noreply@ninoxo.app` |
| `GOOGLE_CLIENT_ID` | Optional | Web OAuth client ID for Google Sign-In (Google Cloud Console) |
| `APPLE_CLIENT_ID` | Optional | Apple Services ID for Sign in with Apple |
| `WIN_REWARD_AMOUNT` | Optional | Tokens awarded per win. Defaults to `50` |

*At least one email provider (`GMAIL_USER`+`GMAIL_APP_PASSWORD` or `RESEND_API_KEY`) is required in production. In development, OTP codes are printed to the server console.

---

## App Settings (stored in `app_settings` table)

| Key | Current Value | Description |
|---|---|---|
| `email_theme` | `dark` | Default email theme for admin preview / non-adaptive clients (`dark` or `light`). Adaptive clients (iOS Mail, Apple Mail) switch automatically based on device |
| `privacy_policy_url` | *(empty)* | URL linked in Privacy Policy screen |
| `terms_url` | *(empty)* | URL linked in Terms of Service screen |

Update via `PUT /admin/app-settings` (admin JWT required) or directly in the DB.

---

## Database Schema

### `players`

The core user table. One row per player.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `text` | — | No | PK. Format: `player_<hex>` or `admin_<hex>` |
| `name` | `text` | — | No | Unique display name |
| `email` | `text` | — | Yes | Unique. Set after email OTP sign-in |
| `phone_number` | `text` | — | Yes | Not used yet |
| `country` | `text` | — | Yes | ISO country code |
| `character_id` | `text` | `'nino'` | No | Selected cactus character |
| `has_subscription` | `boolean` | `false` | No | Premium subscriber flag |
| `is_admin` | `boolean` | `false` | No | Admin flag |
| `is_blocked` | `boolean` | `false` | No | Blocked players cannot sign in |
| `total_matches` | `integer` | `0` | No | Lifetime match count |
| `total_wins` | `integer` | `0` | No | Lifetime win count |
| `total_losses` | `integer` | `0` | No | Lifetime loss count |
| `total_draws` | `integer` | `0` | No | Lifetime draw count |
| `streak` | `integer` | `0` | No | Current win streak |
| `best_streak` | `integer` | `0` | No | All-time best win streak |
| `earned_badge_ids` | `text[]` | `{}` | No | Array of unlocked badge IDs |
| `token_balance` | `integer` | `0` | No | Current token wallet balance |
| `last_seen_at` | `timestamp` | `now()` | No | Updated on each sync |
| `created_at` | `timestamp` | `now()` | No | Account creation time |

---

### `otp_tokens`

One-time sign-in codes sent by email. Keyed by email (not player ID — players sign in before they exist).

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `text` | — | No | PK. Format: `otp_<hex>` |
| `email` | `text` | — | No | Recipient email (lowercase) |
| `code_hash` | `text` | — | No | SHA-256 of the 6-digit code |
| `expires_at` | `timestamp` | — | No | 10 minutes after creation |
| `used_at` | `timestamp` | — | Yes | Set when the code is consumed |
| `created_at` | `timestamp` | `now()` | No | Creation time |

---

### `auth_providers`

Links a player to their sign-in method (email OTP, Google, Apple). One row per provider per player.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `text` | — | No | PK. Format: `ap_<hex>` |
| `player_id` | `text` | — | No | FK → `players.id` |
| `provider` | `auth_provider` (enum) | — | No | `email`, `google`, or `apple` |
| `provider_id` | `text` | — | No | Email address / Google sub / Apple sub |
| `created_at` | `timestamp` | `now()` | No | Link creation time |

Unique constraint on `(provider, provider_id)`.

---

### `matches`

Individual game records (bot, friend, and online matches).

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `varchar` | `gen_random_uuid()` | No | PK |
| `player_id` | `varchar` | — | No | FK → `players.id` |
| `mode` | `varchar` | `'bot'` | No | `bot`, `friend`, or `online` |
| `player_symbol` | `varchar(1)` | — | Yes | `X` or `O` |
| `board` | `text` | — | Yes | Final board state |
| `game_result` | `varchar(10)` | — | Yes | `win`, `loss`, or `draw` |
| `tokens_awarded` | `boolean` | `false` | No | Whether win tokens were paid |
| `opponent_name` | `varchar` | — | Yes | Display name of opponent |
| `opponent_character_id` | `varchar` | — | Yes | Character used by opponent |
| `created_at` | `timestamptz` | `now()` | No | Match start time |
| `ended_at` | `timestamptz` | — | Yes | Match end time |
| `expires_at` | `timestamptz` | `now() + 2h` | No | Stale match cleanup cutoff |

---

### `token_transactions`

Immutable ledger of all token credits and debits.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `integer` | auto-increment | No | PK |
| `player_id` | `text` | — | No | FK → `players.id` |
| `amount` | `integer` | — | No | Positive = credit, negative = debit |
| `type` | `text` | — | No | Transaction type label (e.g. `win`, `challenge_entry`, `challenge_reward`) |
| `description` | `text` | — | Yes | Human-readable note |
| `related_match_id` | `text` | — | Yes | FK to match if applicable |
| `created_at` | `timestamp` | `now()` | No | Transaction time |

Unique constraint on `(player_id, related_match_id)` prevents double-rewarding a match.

---

### `challenges`

Tournament-style events with entry fees and prize pools.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `varchar` | `gen_random_uuid()` | No | PK |
| `title` | `text` | — | No | Challenge name |
| `description` | `text` | — | Yes | Optional details |
| `entry_fee` | `integer` | `0` | No | Tokens to enter |
| `prize_pool` | `integer` | `0` | No | Total tokens in the pool |
| `rank1_reward` | `integer` | — | Yes | Tokens awarded to 1st place |
| `rank2_reward` | `integer` | — | Yes | Tokens awarded to 2nd place |
| `rank3_reward` | `integer` | — | Yes | Tokens awarded to 3rd place |
| `status` | `text` | `'upcoming'` | No | `upcoming`, `active`, `completed`, or `cancelled` |
| `start_at` | `timestamptz` | — | No | When the challenge opens |
| `end_at` | `timestamptz` | — | No | When the challenge closes |
| `max_participants` | `integer` | — | Yes | Cap on entrants (null = unlimited) |
| `rewards_distributed` | `boolean` | `false` | No | Whether prizes have been paid |
| `created_by` | `text` | `'admin'` | No | Creator player ID |
| `created_at` | `timestamptz` | `now()` | No | Creation time |

---

### `challenge_participants`

Join table: which players entered which challenge.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `challenge_id` | `varchar` | — | No | PK + FK → `challenges.id` |
| `player_id` | `text` | — | No | PK + FK → `players.id` |
| `wins` | `integer` | `0` | No | Wins within this challenge |
| `losses` | `integer` | `0` | No | Losses within this challenge |
| `joined_at` | `timestamptz` | `now()` | No | Entry time |

---

### `challenge_match_tokens`

One-time use tokens that authorise a specific challenge match between two players.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `token_id` | `text` | — | No | PK |
| `challenge_id` | `varchar` | — | No | FK → `challenges.id` |
| `player_id` | `text` | — | No | FK → `players.id` |
| `expires_at` | `timestamptz` | — | No | Token expiry |
| `used_at` | `timestamptz` | — | Yes | Set when consumed |

---

### `challenge_matches`

Records of individual matches played within a challenge.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `varchar` | `gen_random_uuid()` | No | PK |
| `challenge_id` | `varchar` | — | No | FK → `challenges.id` |
| `match_token_id` | `text` | — | Yes | FK → `challenge_match_tokens.token_id` (unique) |
| `winner_id` | `text` | — | Yes | FK → `players.id` |
| `loser_id` | `text` | — | Yes | FK → `players.id` |
| `played_at` | `timestamptz` | `now()` | No | Match time |

---

### `challenge_reward_log`

Prevents double-paying challenge prizes.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `id` | `integer` | auto-increment | No | PK |
| `challenge_id` | `varchar` | — | No | FK → `challenges.id` |
| `player_id` | `text` | — | No | FK → `players.id` |
| `amount` | `integer` | — | No | Tokens paid |
| `rank` | `integer` | — | No | Final rank (1, 2, 3, …) |
| `paid_at` | `timestamptz` | `now()` | No | Payment time |

Unique constraint on `(challenge_id, player_id)`.

---

### `follows`

Social graph — which players follow which.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `follower_id` | `text` | — | No | PK + FK → `players.id` |
| `followee_id` | `text` | — | No | PK + FK → `players.id` |
| `created_at` | `timestamp` | `now()` | No | Follow time |

---

### `app_settings`

Key-value store for global configuration.

| Column | Type | Default | Nullable | Notes |
|---|---|---|---|---|
| `key` | `text` | — | No | PK |
| `value` | `text` | `''` | No | Setting value |
| `updated_at` | `timestamp` | `now()` | No | Last update time |

---

### `schema_migrations`

Tracks applied schema migrations (manual SQL files, not Drizzle push).

| Column | Type | Notes |
|---|---|---|
| `filename` | `varchar(255)` | PK — migration filename |
| `applied_at` | `timestamp` | When it was applied |

> **Important:** Never use `drizzle push` interactively. Apply schema changes via raw SQL executed through `executeSql` or the DB tool.

---

### Enums

| Name | Values |
|---|---|
| `auth_provider` | `email`, `google`, `apple` |

---

## API Routes

Base URL in development: the `API_URL` env var (Replit reverse-proxy URL of the API server).

### Auth — `/auth`

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/otp/request` | — | Send a 6-digit OTP to the given email |
| `POST` | `/auth/otp/verify` | — | Verify the OTP and return a JWT |
| `POST` | `/auth/google` | — | Verify a Google ID token and return a JWT |
| `POST` | `/auth/apple` | — | Verify an Apple identity token and return a JWT |
| `GET` | `/auth/me` | Player JWT | Return the current player record |
| `POST` | `/auth/logout` | — | No-op (stateless JWT — client discards the token) |
| `DELETE` | `/auth/account` | Player JWT | Permanently delete account + send farewell email |

### Players & Leaderboard

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/players/sync` | Optional JWT | Upsert player stats, return full profile + leaderboard rank |
| `GET` | `/leaderboard` | — | Paginated global leaderboard |
| `GET` | `/profiles/:profileId` | — | Public profile (live socket data merged if online) |
| `GET` | `/online` | — | List all currently online player IDs |

### Matches

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/matches/start` | — | Record match start, return match ID |
| `POST` | `/matches/record-result` | — | Record match result, award win tokens |
| `GET` | `/matches/history` | Player JWT | Return the player's match history |

### Tokens

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/tokens/award-win` | — | Award win tokens (signed match token required) |
| `GET` | `/tokens/balance/:playerId` | — | Current token balance |
| `GET` | `/tokens/history/:playerId` | — | Token transaction ledger |

### Follows (Social)

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/follows/:targetId` | — | Follow a player |
| `DELETE` | `/follows/:targetId` | — | Unfollow a player |
| `GET` | `/follows/me` | — | My followers and following |
| `GET` | `/follows/counts/:profileId` | — | Follower/following counts |
| `GET` | `/follows/friends` | — | Mutual follows (friends list) |

### Challenges

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/challenges` | — | Active and upcoming challenges |
| `GET` | `/challenges/all` | — | All challenges (any status) |
| `GET` | `/challenges/player/:playerId` | — | Challenges a specific player joined |
| `GET` | `/challenges/:id` | — | Single challenge detail |
| `POST` | `/challenges/:id/join` | — | Join a challenge (deducts entry fee) |
| `POST` | `/challenges/:id/start-match` | — | Get a match token to play a challenge match |
| `POST` | `/challenges/:id/record-match` | — | Record result of a challenge match |

### Admin

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/admin/users` | Admin JWT | List all players |
| `POST` | `/admin/users/:id/block` | Admin JWT | Block a player |
| `POST` | `/admin/users/:id/unblock` | Admin JWT | Unblock a player |
| `POST` | `/admin/users/:id/set-admin` | Admin JWT | Grant admin to a player |
| `POST` | `/admin/users/:id/remove-admin` | Admin JWT | Revoke admin from a player |
| `GET` | `/app-settings` | — | Read all app settings |
| `PUT` | `/admin/app-settings` | Admin JWT | Update one or more app settings |
| `GET` | `/admin/challenges` | Admin JWT | List all challenges |
| `POST` | `/admin/challenges` | Admin JWT | Create a challenge |
| `PATCH` | `/admin/challenges/:id` | Admin JWT | Update a challenge |
| `POST` | `/admin/challenges/:id/complete` | Admin JWT | Mark challenge complete, distribute prizes |
| `POST` | `/admin/challenges/:id/cancel` | Admin JWT | Cancel a challenge |

### Health

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/healthz` | — | Returns `{ status: "ok" }` |

---

## Real-time (Socket.io)

The API server runs a Socket.io namespace on the same port as the HTTP server.

### Events emitted by server → client

| Event | Payload | Description |
|---|---|---|
| `online_count` | `{ count: number }` | Current online player count |
| `player_online` | `{ playerId }` | A followed player came online |
| `player_offline` | `{ playerId }` | A followed player went offline |
| `challenge_invite` | challenge data | Another player challenged you |
| `match_start` | match data | Challenge match ready to play |

### Events emitted by client → server

| Event | Payload | Description |
|---|---|---|
| `register_profile` | `{ playerId, name, characterId }` | Announce presence on connect |
| `challenge_player` | `{ targetId, challengeId }` | Invite another player to a challenge match |

---

## Monorepo Structure

```
/
├── artifacts/
│   ├── nino-xo/           Expo React Native app
│   │   ├── app/           Expo Router screens
│   │   ├── src/
│   │   │   ├── context/   ThemeContext, AuthContext
│   │   │   └── stores/    Zustand stores (settingsStore, etc.)
│   │   └── constants/     theme.ts (color tokens)
│   └── api-server/        Express.js API + Socket.io
│       └── src/
│           ├── config.ts  All env vars
│           ├── routes/    HTTP route handlers
│           ├── socket/    Socket.io room/session logic
│           └── lib/       JWT, logger, seedAdmin
├── packages/
│   └── db/                Drizzle ORM schema + client
└── xo-game-info.md        This file
```
