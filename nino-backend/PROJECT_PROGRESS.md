# PROJECT_PROGRESS.md

## Last Updated
2026-03-19

## Current Phase
Phase 1 — IAM / Roles / Permissions + Auth

## Last Batch Completed
### Batch 2 — Phase 0 Complete + Phase 1 Auth/Role Foundation (2026-03-19)
- Updated PHASES.md with accurate task ID tracking (P0 + P1 IDs)
- Updated PROJECT_PROGRESS.md to follow mandatory document workflow
- Phase 0 fully verified: login page + dashboard render correctly

### Batch 1 — Phase 0 Bootstrap (2026-03-18)
- Bootstrapped Laravel 12 project (PHP 8.4.3)
- Configured MySQL 8 via XAMPP, Gmail SMTP, database cache/queue
- Installed: Sanctum, Fortify, Livewire, Pest, spatie/laravel-permission
- Created 18 domain module directories under `app/Modules/`
- Built admin shell: layout, sidebar, topbar, dashboard, login page
- Applied NinoWorld palette (cream/sage/Nunito)
- Seeded 13 role presets + Super Admin user
- All 5 migrations ran, 38 routes registered, Vite built

## Key Decisions
- MySQL 8 via XAMPP (Homebrew MySQL has startup issues)
- Cache/queue: `database` driver (phpredis not available on PHP 8.4)
- PHP 8.4.3 (8.3.8 had broken ICU library)
- spatie/laravel-permission as RBAC foundation

## Immediate Next Tasks
1. P1-IAM-01 — Define custom permissions (define the full permission set)
2. P1-IAM-03 — Define role-permission relationships (assign permissions to roles)
3. P1-IAM-07 through P1-IAM-10 — Scope-aware permissions
4. P1-USERS-01 — Refine User model (type, states, profile fields)
5. P1-POLICY-01 — Build policies for major models
6. P1-ORG-01 through P1-ORG-06 — Organization hierarchy

## Blockers
- None
