# PROJECT_PROGRESS.md

## Last Updated
2026-03-19

## Current Phase
Phase 1 — IAM / Roles / Permissions + Auth

## Last Batch Completed
### Batch 5 — Core Commerce: Categories CRUD (2026-03-19)
- Built `Category` model and migration with support for infinite recursive hierarchy (`parent_id`).
- Implemented `CategoryController` for full CRUD operations and image upload handling.
- Created `index`, `create`, and `edit` Blade templates utilizing the newly built `x-admin` UI components.
- Integrated category management into the admin sidebar, guarded by new `catalog.categories.*` permissions.

### Batch 4 — Phase 1 Final Security & Fixes (2026-03-19)
- Fixed `RouteNotFoundException` for `admin.staff.index` by appending `->names('admin.staff')` to the resource route.
- Installed `lab404/laravel-impersonate` and added the trait to `User` model (`canImpersonate` restricted to Super Admin).
- Added Impersonate buttons inside the Staff list UI and a global "Leave Impersonation" banner.
- Added Fortify 2FA Challenge UI (`two-factor-challenge.blade.php`), completing the foundation for users to login with authenticator apps/recovery codes.

### Batch 3 — Phase 1 IAM Core & Staff Management (2026-03-19)
- Refined `User` model with staff/customer type separation, statuses, and profile fields
- Scaffolded `Organization` hierarchy (Platform > Franchise > Branch)
- Created `AuditLog` model and migrations for tracking sensitive actions
- Implemented `PermissionsSeeder` mapping 40+ granular permissions across 13 core roles
- Built robust scope-aware policies (`UserPolicy`, `OrganizationPolicy`, `RolePolicy`) limiting access by scope
- Implemented `StaffController` and Blade views for full CRUD of staff members
- Updated sidebar with role-aware navigation for Staff & Roles
- Built Fortify-based custom UI for 'Forgot Password' and 'Reset Password' flows

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
- Used Blade + Tailwind for Staff UI, ensuring robust non-SPA fallback and high accessibility before adopting Livewire selectively
- Integrated `lab404/laravel-impersonate` for fast, secure top-level admin impersonation workflows

## Immediate Next Tasks
1. P1-PROD-01 — Products CRUD (simple + variable)
2. Core Commerce: Inventory & Stock Management

## Blockers
- None
