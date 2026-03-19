# PHASES.md

## Current Active Phase
Phase 1 — Core Commerce & Operations

## Overall Status
- Project Status: IN_PROGRESS
- Current Focus: Bootstrap + IAM + Admin Shell
- Current Milestone: Complete auth, role presets, scoped permissions foundation, and role-aware navigation

---

## Phase Summary
- Phase 1 — Core Commerce & Operations: IN_PROGRESS
- Phase 2 — Advanced Operations & Growth: TODO
- Phase 3 — Community: TODO

---

## Completed Task IDs
- P1-BOOT-01
- P1-BOOT-02
- P1-DOCS-01

---

## In Progress Task IDs
- P1-IAM-01
- P1-IAM-02

---

## Next Task IDs
- P1-IAM-03
- P1-IAM-04
- P1-ADMIN-01
- P1-SETTINGS-01

---

## Blocked Task IDs
- none

---

## Deferred Task IDs
- P3-COMMUNITY-01
- P3-COMMUNITY-02

---

# Module Status Summary

## Bootstrap
- Status: DONE
- Active Tasks:
  - P1-BOOT-01
  - P1-BOOT-02

## Documentation
- Status: DONE
- Active Tasks:
  - P1-DOCS-01

## IAM / Roles / Permissions
- Status: IN_PROGRESS
- Active Tasks:
  - P1-IAM-01
  - P1-IAM-02
  - P1-IAM-03
  - P1-IAM-04
- Notes:
  - Scoped RBAC is mandatory
  - No hardcoded role checks as the primary strategy

## Admin Shell
- Status: TODO
- Active Tasks:
  - P1-ADMIN-01
  - P1-ADMIN-02

## Settings
- Status: TODO
- Active Tasks:
  - P1-SETTINGS-01
  - P1-SETTINGS-02

## Catalog
- Status: TODO
- Active Tasks:
  - P1-CATALOG-01
  - P1-CATALOG-02
  - P1-CATALOG-03

## Inventory
- Status: TODO
- Active Tasks:
  - P1-INVENTORY-01
  - P1-INVENTORY-02
  - P1-INVENTORY-03

## Orders
- Status: TODO
- Active Tasks:
  - P1-ORDERS-01
  - P1-ORDERS-02
  - P1-ORDERS-03

## Checkout
- Status: TODO
- Active Tasks:
  - P1-CHECKOUT-01
  - P1-CHECKOUT-02

## Payments
- Status: TODO
- Active Tasks:
  - P1-PAYMENTS-01
  - P1-PAYMENTS-02
  - P1-PAYMENTS-03

## Customers
- Status: TODO
- Active Tasks:
  - P1-CUSTOMERS-01
  - P1-CUSTOMERS-02

## Shipping
- Status: TODO
- Active Tasks:
  - P1-SHIPPING-01
  - P1-SHIPPING-02

## Notifications
- Status: TODO
- Active Tasks:
  - P1-NOTIFY-01
  - P1-NOTIFY-02

## Coupons / Promotions
- Status: TODO
- Active Tasks:
  - P1-COUPONS-01
  - P1-COUPONS-02

## CMS / Blog / SEO
- Status: TODO
- Active Tasks:
  - P1-CMS-01
  - P1-SEO-01

## Finance
- Status: TODO
- Active Tasks:
  - P1-FINANCE-01
  - P1-FINANCE-02

## Reports / Analytics
- Status: TODO
- Active Tasks:
  - P1-REPORTS-01

## Support
- Status: TODO
- Active Tasks:
  - P1-SUPPORT-01

## Community
- Status: DEFERRED
- Active Tasks:
  - P3-COMMUNITY-01
  - P3-COMMUNITY-02
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