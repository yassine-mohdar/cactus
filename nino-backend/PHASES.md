# PHASES.md

## Current Active Phase
Phase 1 — Core Commerce & Operations

## Overall Status
## Overall Status
- Project Status: IN_PROGRESS
- Current Focus: Phase 16 In Progress — Sensitive event audit coverage completed, moving into security hardening.
- Current Milestone: Phase 16.2 Completed (`P16-EVENT-01` through `P16-EVENT-08`).

---

## Phase Summary
- Phase 0 — Project Foundation: DONE
- Phase 1 — Core Commerce & Operations: IN_PROGRESS
- Phase 2 — Advanced Operations & Growth: TODO
- Phase 2A — Admin UI/UX Design System & Stitch Implementation: TODO
- Phase 3 — Community: TODO

---

## Completed Task IDs
- P1-PROD-01 — Products CRUD (simple + variable)
- P1-CAT-01 — Categories hierarchy (recursive)
- P1-SEC-01 — Add staff 2FA enforcement foundation
- P1-SEC-03 — Add impersonation with audit logs
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
- P1-IAM-01 — Permission model (foundation via spatie, needs custom permissions)
- P1-IAM-02 — Role model created (via spatie/permission)
- P1-IAM-03 — Role-permission relationships
- P1-IAM-04 — Role presets seeded (13 roles)
- P1-IAM-07 through P1-IAM-10 — Scope-aware permissions
- P1-USERS-01 — Core user entity refinement
- P1-USERS-02 — User type handling (staff/customer)
- P1-USERS-05 — Active/inactive/suspended states
- P1-POLICY-01 — Policies for major models
- P1-POLICY-02 — Middleware/helpers for permission checks
- P1-ORG-01 through P1-ORG-06 — Organization hierarchy
- P1-STAFF-01 through P1-STAFF-06 — Staff management CRUD
- P1-NAV-01 through P1-NAV-03 — Role-aware navigation
- P0-ADMIN-04 — Role-aware menu placeholder system
- P1-AUTH-03 — Password reset / password setup flow
- P1-SEC-02 — 2FA foundation (email/app based)
- P1-SEC-03 — Admin impersonation (Super Admin only)
- P3-SETTINGS-01 — Settings storage strategy (key-value with typed groups)
- P3-SETTINGS-02 — Grouped settings retrieval (SettingsService)
- P3-SETTINGS-03 — Validation per settings section (SettingsDefinitions)
- P3-SETTINGS-04 — Secure secret storage (encrypted via Crypt)
- P3-SETTINGS-05 — Cache and cache invalidation
- P5-MODEL-01 — Define stock item model or equivalent
- P5-MODEL-02 — Add product-level stock
- P5-MODEL-03 — Add variant-level stock
- P5-MODEL-04 — Add branch-awareness
- P5-MODEL-05 — Add reservation/release-ready fields
- P3-GENERAL-01 through P3-GENERAL-05 — General settings fields
- P5-ADJUST-01 — Build manual adjustment UI
- P5-ADJUST-02 — Add adjustment reasons
- P5-ADJUST-03 — Support positive/negative adjustments
- P5-ADJUST-04 — Add staff attribution
- P5-ADJUST-05 — Add audit logs
- P5-LOWSTOCK-01 through P5-LOWSTOCK-03 — Low stock fields & lists
- P5-DAMAGE-01 through P5-DAMAGE-03 — Damaged stock workflow
- P5-REPORT-01 through P5-REPORT-02 — Inventory reports foundation
- P5-RESERVE-01 through P5-RESERVE-03 — Reservation and release design
- P5-BRANCH-01 through P5-BRANCH-03 — Branch-aware architecture
- P6-CUSTOMER-01 through P6-CUSTOMER-03 — Add customer-specific fields
- P6-PROFILE-01 through P6-PROFILE-06 — Implement profile fields and credentials
- P6-ADDRESS-01 through P6-ADDRESS-06 — Schema & endpoints for address structures
- P2A-AUDIT-01 — Run visual consistency pass across admin screens
- P2A-DESIGN-01 — Enforce Stitch Design System core CSS (Tailwind v4 theme variables)
- P2A-SHELL-01 — Update Sidebar, Topbar, and Dashboard for role-adapted metrics
- P2A-COMP-01 — Generate nino component library (table, button, card, modal)
- P6-TIMELINE-01 through P6-TIMELINE-03 — Admin Customer timeline & CustomerNote setup
- P6-CHECKOUT-01 through P6-CHECKOUT-04 — Checkout identity automation wrapper
- P6-ACCOUNT-01 through P6-ACCOUNT-06 — Customer Account APIs with order stubs
- P7-ORDER-01 through P7-ORDER-05 — Order models and snapshot schema
- P7-STATUS-01 through P7-STATUS-09 — Order status Enum definitions
- P7-CART-01 through P7-CART-07 — DB-backed Session Cart algorithms and API Controller
- P7-CHECKOUT-01 through P7-CHECKOUT-07 — Interactive Checkout endpoints and validation pipeline
- P7-AUTOACC-01 through P7-AUTOACC-03 — Secure guest-account mid-transaction bootstrapping
- P7-ADMIN-01 through P7-ADMIN-10 — Admin Orders UX, status tracking, customer notes.
- P7-THANKYOU-01 through P7-THANKYOU-03 — Order tracking APIs supplying post-checkout success pages.
- P7-PAYERR-01 through P7-PAYERR-03 — Order tracking APIs supplying post-checkout failure pages.
- P8-DOMAIN-01 through P8-DOMAIN-04 — Payment transaction model, status, order mapping, gateway abstraction.
- P8-ADAPTER-01 through P8-ADAPTER-04 — Provider contract, request/response normalization, error handling, webhook foundation.
- P8-OFFLINE-01 through P8-OFFLINE-05 — Offline payment method CRUD, bank transfer, admin instructions, checkout rendering, verification workflow.
- P8-GATEWAY-01 through P8-GATEWAY-04 — Test/live modes, enable/disable controls, secret storage, validation.
- P8-CMI-01 through P8-CMI-04 — CMI Morocco settings, initiation, response handling, logging.
- P8-PAYZONE-01 through P8-PAYZONE-04 — Payzone Morocco settings, initiation, response handling, logging.
- P8-STRIPE-01 through P8-STRIPE-04 — Stripe settings, initiation, response handling, logging.
- P8-LOGS-01 through P8-LOGS-03 — Payment request/response logging with redaction, failure logs, status change audit.
- P9-MODEL-01 through P9-MODEL-05 — Shipping methods, shipment records, tracking, carrier/service fields, fulfillment status.
- P9-SETTINGS-01 through P9-SETTINGS-03 — Shipping methods CRUD, defaults, carrier metadata.
- P9-FLOW-01 through P9-FLOW-06 — Fulfillment states: ready to ship, packed, dispatched, delivered, failed delivery, returned.
- P9-AGENT-01 through P9-AGENT-04 — Ready-to-ship queue, tracking entry/update, status actions, delivery issue flags.
- P9-CUSTOMER-01 through P9-CUSTOMER-02 — Customer tracking number visibility and shipping timeline API.
- P9-REPORT-01 through P9-REPORT-03 — Shipment history, status changes log, failed/returned queues.
- P10-ARCH-01 through P10-ARCH-04 — Notification event mapping, channel abstraction, template variable system, queue job structure.
- P10-CHANNEL-01 through P10-CHANNEL-03 — Email (Laravel Mail + SMTP), SMS (Twilio REST API), WhatsApp (Meta Cloud API) channels.
- P10-TRIGGER-01 through P10-TRIGGER-10 — Order placed/cancelled, payment success/failed, shipped/delivered, welcome, password setup, abandoned cart, promotional offer triggers.
- P10-TEMPLATE-01 through P10-TEMPLATE-04 — Template CRUD, channel-specific content, placeholder preview, enable/disable toggle.
- P10-LOGS-01 through P10-LOGS-04 — Sent/failed history, retry with exponential backoff, error capture, search/filter.
- P10-INTEGRATION-01 through P10-INTEGRATION-03 — SMTP config, Twilio config, WhatsApp API config.
- P11-MODEL-01 through P11-MODEL-10 — Coupon data model: code uniqueness, fixed/percentage types, active/inactive, start/end dates, usage limits, per-user limits, minimum cart, product/category targeting, exclusion rules, stackability.
- P11-ADMIN-01 through P11-ADMIN-03 — Coupon CRUD with search/filter, usage counters, computed status badges.
- P11-VALIDATE-01 through P11-VALIDATE-04 — 11-rule validation engine: apply/remove with transaction, eligibility rules, structured error messages with codes, order snapshot of discount usage.
- P11-REPORT-01 through P11-REPORT-02 — Coupon usage counts, revenue impact with 3 tabbed reports.
- P11-ABANDON-01 through P11-ABANDON-03 — Abandoned cart capture (update-or-create), trigger-ready state with recoverable scope, recovery hooks integrated with notification system.
- P12-TAX-01 through P12-TAX-04 — Blog categories (hierarchy, slugs, SEO, status/ordering) and tags (name, slug, post count) with full CRUD.
- P12-POST-01 through P12-POST-08 — Blog posts with title, slug, author, featured image, body, draft/published/scheduled/archived status, scheduling foundation (publishScheduled()), search/filter.
- P12-SEO-01 through P12-SEO-05 — Per-post meta title, meta description, canonical URL, OG fields (title, description, image), noindex toggle.
- P12-ENTITYSEO-01 through P12-ENTITYSEO-04 — Polymorphic `seo_metadata` table attachable to products, categories, blog categories, and posts.
- P12-SITEMAP-01 through P12-SITEMAP-03 — Sitemap-ready slug architecture, `redirects` table (301/302 with hit tracking), auto-slug generation strategy.
- P12-UX-01 through P12-UX-04 — Publish/unpublish toggle workflow, rich post preview with SEO card, author attribution, featured image URL field.
- P13-DATA-01 through P13-DATA-04 — Order financial summary fields (subtotal/discount/shipping/tax/total snapshot), transaction records, PaymentMethod enum (8 methods), currency/base currency (MAD default).
- P13-TRANS-01 through P13-TRANS-03 — Payment transactions list with multi-filter (search/status/type/method/date range), transaction detail view, linked to orders.
- P13-REPORT-01 through P13-REPORT-02 — Revenue overview (gross/net/fees/refunds/discounts), gateway success/failure summary with success rate.
- P13-COD-01 through P13-COD-02 — CodStatus enum (5 states), COD lifecycle (collected→deposited→reconciled), discrepancy tracking, reconciliation pipeline with actions.
- P13-REFUND-01 through P13-REFUND-02 — RefundRequest model with approval workflow (request→approve→process→complete/reject), refund totals visibility.
- P13-FEE-01 through P13-FEE-02 — Discount visibility (total/avg per transaction), payment fee visibility (total/avg), both in reports dashboard.
- P14-LOOKUP-01 through P14-LOOKUP-03 — Unified support search (order#/email/phone/name/txn reference), fast filter results, clickable rows.
- P14-TIMELINE-01 through P14-TIMELINE-04 — Order timeline view with recent orders, status display, payment/shipping/transaction history, polymorphic activity timeline with performer attribution.
- P14-NOTES-01 through P14-NOTES-04 — Polymorphic InternalNote model (attachable to orders, customers, issues), staff attribution via created_by, pin/unpin, audit visibility via ActivityTimeline.
- P14-QUEUE-01 through P14-QUEUE-02 — SupportIssue model with IssueStatus (5 states), IssueType (7 categories), IssuePriority (4 levels), assignment, lifecycle actions (progress/resolve/close/reopen), issue queue with multi-filter.
- P15-REPORT-01 through P15-REPORT-05 — Five core admin reports (Sales, Orders, Inventory, Coupons, Finance) with dynamic date filtering and visual summary cards.
- P15-SEARCH-01 through P15-SEARCH-03 — Filterable trait for shared queries, SavedFilter model and migration for custom view presets.
- P15-BULK-01 through P15-BULK-03 — BulkActionController with batch operations for products, orders, and CMS content.
- P15-EXPORT-01 through P15-EXPORT-02 — ExportController for chunked CSV downloads (Products, Orders, Transactions, Inventory) and basic Catalog CSV importer.
- P16-AUDIT-01 through P16-AUDIT-03 — Audit model/store, sensitive action hooks foundation, actor/target/action/context logging.
- P16-EVENT-01 — Audit role changes
- P16-EVENT-02 — Audit staff creation/deactivation
- P16-EVENT-03 — Audit product price changes
- P16-EVENT-04 — Audit order status overrides
- P16-EVENT-05 — Audit refund updates
- P16-EVENT-06 — Audit stock adjustments
- P16-EVENT-07 — Audit payment configuration changes
- P16-EVENT-08 — Audit impersonation

---

## In Progress Task IDs
- none

---

## Next Task IDs
- P16-SEC-01 — Add forced 2FA if enabled
- P16-SEC-02 — Review session security
- P16-SEC-03 — Add rate limiting where appropriate
- P16-SEC-04 — Add safe logging/redaction rules

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
- Remaining: P0-ADMIN-07 (reusable components)

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

## Admin UI/UX Design System
- Status: DONE
- Completed: P2A-DESIGN-01, P2A-SHELL-01, P2A-COMP-01, P2A-AUDIT-01
  
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
- Status: DONE

## Customers
- Status: TODO

## Shipping
- Status: DONE

## Notifications
- Status: DONE

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
