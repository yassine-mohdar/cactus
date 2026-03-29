# Nino XO — NinoWorld Premium Mobile Game

## Overview
Nino XO is a premium, subscriber-only 1v1 XO/Tic-Tac-Toe mobile game set in the NinoWorld cactus character universe. Players choose from 6 unique cactus characters and play against bots or friends.

## Architecture

### Monorepo Structure (pnpm workspaces)
- `artifacts/nino-xo/` — Expo React Native mobile app (main game)
- `artifacts/api-server/` — Express API server
- `artifacts/mockup-sandbox/` — Component preview server for canvas

### Nino XO App (`artifacts/nino-xo/`)

**Tech Stack:**
- Expo SDK 54 with Expo Router (file-based navigation)
- React Native + React Native Reanimated v4 (spring/bounce animations)
- **Zustand** stores for all state management (auth, game, settings) with `persist` middleware + AsyncStorage
- React Context bridges (thin wrappers over Zustand stores for component consumption)
- TanStack Query (`useEntitlement` hook) for subscription gate via `subscriptionService`
- Inter font family via @expo-google-fonts/inter
- Jest + ts-jest for unit testing (26 tests passing)

**Routing (app/) — 27 total files (25 navigable screens):**
- `index.tsx` — Animated splash; checks auth + subscription at launch → home/login/locked
- `login.tsx` — Name entry with premium badge; routes to /session-restore after login
- `home.tsx` — Main hub with stats, game modes, missions, explore section
- `characters.tsx` — 6-character selection grid with bouncing preview and personality
- `bot-difficulty.tsx` — Easy/Medium AI difficulty picker
- `quick-match.tsx` — Animated matchmaking lobby via `realtimeService`; passes opponent data
- `friend-room.tsx` — Create/join rooms with `realtimeService`
- `room-lobby.tsx` — Waiting room showing host/guest join status before match starts
- `match.tsx` — Live 3×3 board with bot AI, turn indicators, auto-routes to result
- `result.tsx` — Win/lose/draw with character reactions → rematch/rewards/home
- `rewards.tsx` — Post-match animated rewards badges (wins, streak, missions)
- `locked.tsx` — Paywall screen; subscribe/restore grants access via setSubscription+setAccessOverride
- `profile.tsx` — Stats, win rate, streak, mission progress; links to match-history and stats
- `settings.tsx` — Sound/music toggles, notifications, about; Privacy Policy + Terms rows; Admin section (users + app settings) for admin users; logout
- `admin-users.tsx` — Admin: searchable user list with block/unblock and admin promotion actions
- `admin-app-settings.tsx` — Admin: set Privacy Policy URL, Terms of Use URL, email theme (dark/light)
- `policy-webview.tsx` — Opens a configured policy URL in the system in-app browser (expo-web-browser)
- `session-restore.tsx` — Re-checks entitlement after login/restart; routes to home or locked
- `offline.tsx` — Offline error screen with mode guidance
- `leaderboard.tsx` — Global rankings with mock data; gold/silver/bronze trophies; friends tab
- `missions.tsx` — 6 missions with real-stats-driven progress tracking (wins, streak, matches)
- `how-to-play.tsx` — Collapsible 4-step game flow; 5 pro tips; 8 winning conditions
- `match-history.tsx` — Win/loss/draw history with mode labels; summary stats at top
- `stats.tsx` — Detailed stats breakdown: win rate, W/L record, streak, missions
- `notifications.tsx` — In-app notifications: invites, results, missions, system alerts
- `about.tsx` — App info, features list, subscription details, NinoWorld branding

**Subscription Gate Flow:**
1. New user logs in → `hasSubscription: false` → routes to `/session-restore`
2. `session-restore` calls `subscriptionService.checkEntitlement()` → `hasAccess: false` → routes to `/locked`
3. User taps Subscribe/Restore → `setSubscription(true)` + `setAccessOverride(true)` → routes to `/home`
4. Returning subscriber: `profile.hasSubscription: true` (persisted) → `useEntitlement` uses `||` logic: `data?.hasAccess || profile?.hasSubscription` → correctly returns `true`
5. Home screen `guardedNav()` blocks unsubscribed users to `/locked`

**State Management (`src/stores/`):**
- `authStore.ts` — Zustand + persist: profile, login, logout, stats, character, name, setSubscription, earnBadges, pendingBadgeIds, clearPendingBadges
- `gameStore.ts` — Zustand: session lifecycle, move handling, bot timer scheduling, startQuickGame, startOnlineGame
- `settingsStore.ts` — Zustand + persist: sound/music toggles

**Service Layer (`src/services/`):**
- `realtimeService.ts` — Socket.io-client singleton: matchmaking queue, room create/join, sendMove, onOpponentMove, onOpponentLeft, leaveRoom
- `subscriptionService.ts` — Mock entitlement checker with caching, restore, and `setAccessOverride()` for QA

**Badges System (`src/data/badges.ts`):**
- 12 badges across 4 tiers: bronze, silver, gold, platinum
- Badge definitions include: First Victory, Veteran, Sharp Shooter, Hot Streak, Unstoppable, Legendary Streak, Dedicated, Grand Champion, Century Club, Quest Master, NinoWorld Legend, Equilibrium
- `checkNewBadges(stats, currentBadgeIds)` — returns newly-earned badge IDs post-game
- Badges checked in `match.tsx` after game over, stored in `profile.earnedBadgeIds[]`
- `pendingBadgeIds` (transient, not persisted) shown on result screen and cleared on exit
- `app/badges.tsx` — full badges gallery screen: tier sections, earned/locked states, progress bar

**Core Logic (`src/engine/`):**
- `gameEngine.ts` — Pure XO rules (win detection, board state, move validation)
- `botEngine.ts` — Bot AI (easy: random; medium: win/block/center strategy)
- `__tests__/gameEngine.test.ts` — 26 unit tests

**Characters (6):**
- Nino (green), Detective (blue), Rosie (pink), Scout (blue), Doc (green), Sunny (yellow)
- Each has unique: personality, flavor text, accentColor, accentPale, all-scenario reactions

**Design System (`constants/colors.ts`):**
- **Dark navy theme**: background `#16162A`, cards `#1E1E35` (`Colors.cardBg`/`Colors.darkCard`), border `rgba(255,255,255,0.08)` (`Colors.darkBorder`)
- Character accents used for highlights; `accentColor + '1A'` for selected states on dark (NOT `accentPale` which is light)
- Inter font family (400/500/600/700)
- NinoButton variants: primary=green, secondary=white-glass (`rgba(255,255,255,0.1)`), ghost=semi-white text, danger=coral
- CharacterCard: dark bg, white name text, `rgba(255,255,255,0.4)` personality text

## Key Behaviors
- **Subscription gate at launch**: splash checks `profile.hasSubscription` first (short-circuit for returning users), then entitlement service → routes to `/locked` if no access
- **useEntitlement**: `hasAccess = data?.hasAccess || profile?.hasSubscription` — OR not ?? — so persisted subscription state survives service returning false across sessions
- **Login → session-restore → entitlement check → home/locked**
- **Bot AI**: 700–1100ms delay; Medium bot wins/blocks/center strategy
- **Quick match opponent**: `startQuickGame` receives name + characterId from `realtimeService` result
- **Friend match stats**: match.tsx finds local player by `id === 'player'` first, falls back to any non-bot
- `router.replace()` for major transitions; no back navigation into splash/match after completion

## API Server (`artifacts/api-server/`)

**API Contract Notes:**
- `POST /api/players/sync` intentionally does NOT accept or update `email`. Email is managed exclusively through verified auth flows (OTP, Google, Apple) to prevent email pre-claim attacks. Clients should not send `email` in sync body; it will be silently ignored.

**Auth System (Task #4):**
- JWT (jose, HS256, 7-day expiry) — `lib/jwt.ts`; ephemeral fallback in dev, requires JWT_SECRET in prod
- Middleware: `requireAuth`, `requireAdmin`, `optionalAuth` — `middleware/auth.ts`
- `requireAuth` checks `is_blocked` on every request — blocked players get 403 immediately without needing token revocation
- OTP email template supports dark + light themes; theme is read from `app_settings.email_theme` at send time
- OTP flow: request (3/15min rate limit, Resend email) → verify (5 failures/15min, atomic single-use, crypto.randomInt)
- Google Sign-In: JWKS, requires email_verified=true, aud validated when GOOGLE_CLIENT_ID set; 503 in prod without it
- Apple Sign-In: JWKS, email from token claims only (client email ignored), aud validated when APPLE_CLIENT_ID set; 503 in prod without it
- Admin seeding: idempotent startup, promotes existing player if ADMIN_EMAIL already exists; backfills email/provider for legacy admins
- OTP rate limits are **in-memory only** — reset on restart; use Redis/DB before horizontal scaling

**Admin API (routes/admin.ts):**
- `GET /admin/users?search=` — searchable player list (name/email), last 50, admin-protected
- `POST /admin/users/:id/block|unblock` — block/unblock a user; cannot self-block
- `POST /admin/users/:id/set-admin|remove-admin` — promote/demote admin; cannot self-demote
- `GET /app-settings` — public; returns privacyPolicyUrl, termsUrl, emailTheme
- `PUT /admin/app-settings` — admin-only; updates any combination of the three settings

**DB Schema Migration Approach:**
- Schema defined in Drizzle (`lib/db/src/schema/`)
- Schema changes applied via direct Node.js + pg SQL (not `drizzle push`) due to environment constraints
- Never use `drizzle push` interactively in this project; apply migrations via `node -e "..."` with the pg client at `lib/db/node_modules/pg`

**Required Secrets (Production):**
- JWT_SECRET, RESEND_API_KEY, OTP_FROM_EMAIL, ADMIN_EMAIL, GOOGLE_CLIENT_ID, APPLE_CLIENT_ID

## Testing
- Unit tests: `cd artifacts/nino-xo && pnpm test` (39 tests, 2 suites)
- Validation command: `nino-xo-tests`
- Covers: checkWinner (all 8 lines), checkDraw, getGameResult, isValidMove, applyMove, getLegalMoves, willWinWithMove, bot AI behavior
