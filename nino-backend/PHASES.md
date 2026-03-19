# PHASES.md

## Current Active Phase
Phase 1 — Core Commerce & Operations

## Overall Status
- Project Status: IN_PROGRESS
- Current Focus: IAM / Roles / Permissions + Auth Foundation
- Current Milestone: Complete auth, role presets, scoped permissions, policies, organization hierarchy, and role-aware navigation

---

## Phase Summary
- Phase 0 — Project Foundation: DONE
- Phase 1 — Core Commerce & Operations: IN_PROGRESS
- Phase 2 — Advanced Operations & Growth: TODO
- Phase 3 — Community: TODO

---

## Completed Task IDs
- P0-INIT-01 — Create nino-backend folder
- P0-INIT-02 — Initialize Laravel 12 project
- P0-INIT-03 — Set PHP version compatibility to 8.3+ (using 8.4.3)
- P0-INIT-04 — Configure environment files and sane defaults
- P0-INIT-05 — Choose and configure database driver (MySQL 8 via XAMPP)
- P0-TOOLS-01 — Set up Tailwind CSS v4
- P0-TOOLS-02 — Set up Livewire
- P0-TOOLS-03 — Set up Sanctum/Fortify
- P0-TOOLS-04 — Set up Pest
- P0-MODULES-01 through P0-MODULES-20 — All 18 module directories created
- P0-ADMIN-01 — Build base layout
- P0-ADMIN-02 — Create topbar/sidebar/content shell
- P0-ADMIN-03 — Add theme tokens for professional palette
- P0-ADMIN-05 — Add flash message pattern
- P0-ADMIN-06 — Add page header pattern
- P0-DOCS-01 — Create PROJECT_PROGRESS.md
- P0-DOCS-02 — Create ARCHITECTURE.md (updated to MySQL)
- P0-DOCS-03 — Create PHASES.md
- P1-AUTH-01 — Staff authentication (basic login/logout via AuthController)
- P1-IAM-02 — Role model created (via spatie/permission)
- P1-IAM-04 — Role presets seeded (13 roles)

---

## In Progress Task IDs
- P1-AUTH-03 — Password reset / password setup flow
- P1-USERS-01 — Core user entity refinement
- P1-IAM-01 — Permission model (foundation via spatie, needs custom permissions)
- P1-IAM-03 — Role-permission relationships
- P1-IAM-07 through P1-IAM-10 — Scope-aware permissions

---

## Next Task IDs
- P1-USERS-02 — User type handling (staff/customer)
- P1-USERS-05 — Active/inactive/suspended states
- P1-POLICY-01 — Policies for major models
- P1-POLICY-02 — Middleware/helpers for permission checks
- P1-ORG-01 through P1-ORG-06 — Organization hierarchy
- P1-STAFF-01 through P1-STAFF-06 — Staff management CRUD
- P1-NAV-01 through P1-NAV-03 — Role-aware navigation
- P0-ADMIN-04 — Role-aware menu placeholder system
- P0-ADMIN-07 — Reusable table/filter/form card components

---

## Blocked Task IDs
- none

---

## Deferred Task IDs
- P0-INIT-06 — Configure Redis (phpredis ext not available on PHP 8.4)
- P0-TOOLS-05 — Code style/linting
- P0-TOOLS-06 — Debug/local mail utilities
- P3-COMMUNITY-01 — Community module
- P3-COMMUNITY-02 — Community UI

---

# Module Status Summary

## Bootstrap
- Status: DONE
- Completed: P0-INIT-01 through P0-INIT-05, P0-TOOLS-01 through P0-TOOLS-04

## Documentation
- Status: DONE
- Completed: P0-DOCS-01 through P0-DOCS-03

## Admin Shell
- Status: IN_PROGRESS
- Completed: P0-ADMIN-01, P0-ADMIN-02, P0-ADMIN-03, P0-ADMIN-05, P0-ADMIN-06
- Remaining: P0-ADMIN-04 (role-aware menu), P0-ADMIN-07 (reusable components)

## IAM / Roles / Permissions
- Status: IN_PROGRESS
- Completed: P1-IAM-02, P1-IAM-04
- Active: P1-IAM-01, P1-IAM-03, P1-IAM-07 through P1-IAM-10
- Notes:
  - Scoped RBAC is mandatory
  - No hardcoded role checks as primary strategy
  - spatie/laravel-permission installed as foundation

## Auth
- Status: IN_PROGRESS
- Completed: P1-AUTH-01 (basic staff login/logout)
- Active: P1-AUTH-03 (password reset)

## Organizations
- Status: TODO

## Catalog
- Status: TODO

## Inventory
- Status: TODO

## Orders
- Status: TODO

## Checkout
- Status: TODO

## Payments
- Status: TODO

## Customers
- Status: TODO

## Shipping
- Status: TODO

## Notifications
- Status: TODO

## Coupons / Promotions
- Status: TODO

## CMS / Blog / SEO
- Status: TODO

## Finance
- Status: TODO

## Reports / Analytics
- Status: TODO

## Support
- Status: TODO

## Community
- Status: DEFERRED
- Notes:
  - Schema/boundaries only before Phase 3
  - Full UI must not start during unfinished Phase 1

---

# Current Execution Rules
- Read `READ_FIRST.md` first in every session
- Use `PHASES.md` as the primary execution checkpoint
- Use `PROJECT_PROGRESS.md` for latest handoff context
- Read `ARCHITECTURE.md` only when current work touches architecture-sensitive areas
- Read `phases-tasks.md` only for the active phase/module/task details
- Update this file after each meaningful implementation batch

---

# Key Constraints
- Commerce and operations come before Community
- Admin UI must stay professional, dense, fast, and non-playful
- No plain password delivery
- Use queues for async work
- Use audit logs for sensitive actions
- Avoid N+1 queries
- Keep modules clean and separable

---

# Notes
- Backend folder: `nino-backend`
- Existing frontend landing page folder: `nino-world-Landing-page`
- Preferred DB: MySQL 8+
- Preferred admin approach: Laravel + Tailwind + Livewire