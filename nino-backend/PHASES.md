# PHASES.md

## Current Active Phase
Phase 3 — Settings & System Configuration

## Overall Status
- Project Status: NOT_RELEASE_READY
- Reconciliation State: Strict audit completed on 2026-03-20
- Current Focus: begin Phase 3 with the settings foundation now that Phase 1 and Phase 2 are complete; Phase 2A is intentionally deferred by user direction
- Real Milestone: Phase 18 stabilization work exists, but release readiness is blocked by unfinished earlier phases

---

## Phase Summary
| Phase | Status | Short Reason |
| --- | --- | --- |
| Phase 0 | DONE | Project boots, module structure exists, admin shell renders, docs/core stack exist; some tooling remains deferred but does not block runtime |
| Phase 1 | DONE | Staff/customer auth, core user entity design, avatar support, last-login tracking, local IAM models, role-permission relationships, role presets, permission naming conventions, policy/middleware coverage, explicit platform/franchise/branch foundations, organization assignment, scope constraints, staff-management workflows, security controls, and role-aware navigation foundations are implemented and tested |
| Phase 2 | DONE | Shared dashboard layout patterns, widget sections, empty/loading states, date presets, and all planned role-based dashboard slices are implemented and tested across both admin themes |
| Phase 2A | TODO | Intentionally deferred for now; the current admin UI system exists, but the Stitch-specific source-of-truth work is being skipped while Phase 3 proceeds |
| Phase 3 | IN_PROGRESS | Settings foundation, general settings, locale/timezone runtime application, safe connection testing, and the main SEO analytics/verification/pixel fields are now implemented and tested; the remaining operational settings slices are still incomplete |
| Phase 4 | IN_PROGRESS | Catalog CRUD exists, but product/category search/filter depth, merchandising fields, and full SEO/media readiness are incomplete |
| Phase 5 | IN_PROGRESS | Inventory core, adjustments, reservations, and branch-aware stock exist, but the full report set and some operational surfaces are incomplete |
| Phase 6 | IN_PROGRESS | Customer/address foundations and customer auth entry exist, but account order history/details/tracking are still stubbed |
| Phase 7 | IN_PROGRESS | Cart/checkout/order creation exist, but coupon/address reuse depth is partial and thank-you/error pages are still stubs |
| Phase 8 | IN_PROGRESS | Gateway adapters, callbacks, and admin settings exist, but checkout does not yet drive the real payment initiation flow and offline methods are only partially operational |
| Phase 9 | DONE | Shipping methods, shipment lifecycle, agent workspace, reports, and tracking APIs are implemented and verified |
| Phase 10 | IN_PROGRESS | Notification architecture, templates, logs, and integrations exist, but core domain flows do not yet reliably trigger the service in production paths |
| Phase 11 | IN_PROGRESS | Coupon and abandoned-cart foundations exist, but real cart/checkout integration remains shallow |
| Phase 12 | IN_PROGRESS | CMS/blog/redirects exist, but product/category polymorphic SEO and true media-picker depth are incomplete |
| Phase 13 | IN_PROGRESS | Finance reports/refunds/COD flows exist, but transaction linkage is not fully clean and model wiring still has defects |
| Phase 14 | DONE | Support lookup, issue queue, order timeline, and notes tooling are implemented in usable form |
| Phase 15 | IN_PROGRESS | Reports/export/search foundations exist, but at least one bulk action path is still broken against the live schema |
| Phase 16 | DONE | Audit logging, sensitive-event coverage, security hardening, and observability foundations are implemented and tested |
| Phase 17 | DONE | Community schema, moderation foundation, onboarding hooks, and documentation boundaries are implemented without coupling release to unfinished UI |
| Phase 18 | BLOCKED | Tests/QA/perf/release checks were executed, but the phase cannot be truthfully marked done while earlier core phases remain incomplete |

---

## Verified Completed Task IDs
- Verified foundation/core ranges:
  - `P0-INIT-01` through `P0-INIT-05`
  - `P0-TOOLS-01` through `P0-TOOLS-04`
  - `P0-MODULES-01` through `P0-MODULES-20`
  - `P0-ARCH-01` through `P0-ARCH-08`
  - `P0-ADMIN-01` through `P0-ADMIN-07`
  - `P0-DOCS-01` through `P0-DOCS-04`
- Verified incremental Phase 1 tasks:
  - `P1-AUTH-01`
  - `P1-AUTH-02`
  - `P1-AUTH-03`
  - `P1-AUTH-04`
  - `P1-AUTH-05`
  - `P1-AUTH-06`
  - `P1-USERS-01`
  - `P1-USERS-02`
  - `P1-USERS-03`
  - `P1-USERS-04`
  - `P1-USERS-05`
  - `P1-USERS-06`
  - `P1-IAM-01`
  - `P1-IAM-02`
  - `P1-IAM-03`
  - `P1-IAM-04`
  - `P1-IAM-05`
  - `P1-IAM-06`
  - `P1-IAM-07`
  - `P1-IAM-08`
  - `P1-IAM-09`
  - `P1-IAM-10`
  - `P1-POLICY-01`
  - `P1-POLICY-02`
  - `P1-POLICY-03`
  - `P1-POLICY-04`
  - `P1-ORG-01`
  - `P1-ORG-02`
  - `P1-ORG-03`
  - `P1-ORG-04`
  - `P1-ORG-05`
  - `P1-ORG-06`
  - `P1-SEC-01`
  - `P1-SEC-02`
  - `P1-SEC-03`
  - `P1-SEC-04`
  - `P1-STAFF-01`
  - `P1-STAFF-02`
  - `P1-STAFF-03`
  - `P1-STAFF-04`
  - `P1-STAFF-05`
  - `P1-STAFF-06`
  - `P1-NAV-01`
  - `P1-NAV-02`
  - `P1-NAV-03`
- Verified incremental Phase 2 tasks:
  - `P2-DASH-01`
  - `P2-DASH-02`
  - `P2-DASH-03`
  - `P2-DASH-04`
  - `P2-SUPER-01`
  - `P2-SUPER-02`
  - `P2-SUPER-03`
  - `P2-SUPER-04`
  - `P2-SUPER-05`
  - `P2-SUPER-06`
  - `P2-SUPER-07`
  - `P2-SUPER-08`
  - `P2-SUPER-09`
  - `P2-SUPER-10`
  - `P2-SUPER-11`
  - `P2-PLATFORM-01`
  - `P2-PLATFORM-02`
  - `P2-PLATFORM-03`
  - `P2-PLATFORM-04`
  - `P2-PLATFORM-05`
  - `P2-FRANCHISE-01`
  - `P2-FRANCHISE-02`
  - `P2-FRANCHISE-03`
  - `P2-FRANCHISE-04`
  - `P2-BRANCH-01`
  - `P2-BRANCH-02`
  - `P2-BRANCH-03`
  - `P2-BRANCH-04`
  - `P2-SUPPORT-01`
  - `P2-SUPPORT-02`
  - `P2-SUPPORT-03`
  - `P2-SUPPORT-04`
  - `P2-SHIPPING-01`
  - `P2-SHIPPING-02`
  - `P2-SHIPPING-03`
  - `P2-SHIPPING-04`
  - `P2-SHIPPING-05`
  - `P2-FINANCE-01`
  - `P2-FINANCE-02`
  - `P2-FINANCE-03`
  - `P2-FINANCE-04`
  - `P2-FINANCE-05`
  - `P2-FINANCE-06`
  - `P2-SEO-01`
  - `P2-SEO-02`
  - `P2-SEO-03`
  - `P2-SEO-04`
  - `P2-MEDIA-01`
  - `P2-MEDIA-02`
  - `P2-MEDIA-03`
  - `P2-SALES-01`
  - `P2-SALES-02`
  - `P2-SALES-03`
  - `P2-SALES-04`
  - `P2-STOCK-01`
  - `P2-STOCK-02`
  - `P2-STOCK-03`
  - `P2-STOCK-04`
  - `P2-MOD-01`
- Verified incremental Phase 3 tasks:
  - `P3-SETTINGS-01`
  - `P3-SETTINGS-02`
  - `P3-SETTINGS-03`
  - `P3-SETTINGS-04`
  - `P3-SETTINGS-05`
  - `P3-GENERAL-01`
  - `P3-GENERAL-02`
  - `P3-GENERAL-03`
  - `P3-GENERAL-04`
  - `P3-GENERAL-05`
  - `P3-CONN-01`
  - `P3-CONN-02`
  - `P3-CONN-03`
  - `P3-CONN-04`
  - `P3-SEO-01`
  - `P3-SEO-02`
  - `P3-SEO-03`
  - `P3-SEO-04`
  - `P3-SEO-05`
  - `P3-SECURITY-01`
  - `P3-SECURITY-02`
- Verified complete phase ranges:
  - `P9-MODEL-01` through `P9-REPORT-03`
  - `P14-LOOKUP-01` through `P14-QUEUE-02`
  - `P16-AUDIT-01` through `P16-OBS-04`
  - `P17-DOMAIN-01` through `P17-PERF-03`
- Verified executed stabilization ranges:
  - `P18-TEST-01` through `P18-TEST-06`
  - `P18-QA-01` through `P18-QA-05`
  - `P18-PERF-01` through `P18-PERF-04`
  - `P18-DOCS-01` through `P18-DOCS-04`
  - `P18-RELEASE-01` through `P18-RELEASE-04`

---

## In Progress Task IDs
- none

---

## Blocked Task IDs
- none at task level
- Release readiness is blocked at the phase level by incomplete Phases 1, 3, 6, 7, 8, 10, 12, 13, and 15

---

## Next Task IDs
- `P3-SECURITY-03`
- `P3-SECURITY-04`
- `P3-FINANCE-01`
- `P3-FINANCE-02`

---

## Deferred Task IDs
- `P0-INIT-06` — Configure Redis
- `P0-TOOLS-05` — Code style / linting
- `P0-TOOLS-06` — Debug / local mail utilities

---

## Module Status Summary
| Module / Area | Status | Notes |
| --- | --- | --- |
| Bootstrap / Architecture | DONE | Runtime foundation is in place |
| Admin Shell / Theme System | DONE | Switchable `nino-v1` / `nino-v2` theme system exists |
| IAM / Organizations | DONE | Staff and customer auth, core user entity/type/profile/status handling, avatar support, last-login tracking, local role/permission models, role-permission relationships, role presets, permission naming conventions, custom roles, permission overrides, broader model/controller authorization, role-home routing, explicit platform/franchise/branch hierarchy foundations, organization assignment, scope constraints, branch-aware operational scope helpers, branch/own scope enforcement, permission-safe navigation, role-adapted sidebar ordering, full staff list/create/edit/role-scope/status workflows, impersonation audit logging, staff 2FA enforcement, and staff session revocation are implemented and covered |
| Dashboards | DONE | Shared dashboard framework, widget sections, preset filters, loading/empty states, and all planned role-based workspaces are implemented across both admin themes, including Super Admin, Platform, Franchise, Branch, Support, Shipping, Finance, Content, Media Buying, Sales, Stock, and Community Moderator |
| Settings Center | IN_PROGRESS | Grouped retrieval, validation, encrypted secrets, cache invalidation, general settings, locale/timezone runtime application, SMTP/Twilio/WhatsApp settings, safe connection tests, full current SEO fields including TikTok/default SEO values, and security foundations for forced 2FA plus password-policy enforcement are implemented; the remaining Phase 3 slices are still incomplete |
| Catalog | IN_PROGRESS | CRUD exists; SEO/media/admin filtering depth is incomplete |
| Inventory | IN_PROGRESS | Core operations exist; full reporting surface is incomplete |
| Customers / Account Area | IN_PROGRESS | Admin customer tooling and customer login/setup flow exist; account history/details/tracking remain partially stubbed |
| Orders / Checkout | IN_PROGRESS | Order creation works; customer-facing completion surfaces are incomplete |
| Payments | IN_PROGRESS | Adapter layer exists; end-to-end checkout payment flow is not fully wired |
| Shipping | DONE | Operationally solid |
| Notifications | IN_PROGRESS | Messaging center exists; real trigger wiring remains incomplete |
| Promotions | IN_PROGRESS | Engine exists; live cart/checkout wiring remains incomplete |
| CMS / SEO | IN_PROGRESS | Blog/redirect tooling exists; product/category SEO attachment is incomplete |
| Finance | IN_PROGRESS | Reporting/refunds exist; model-level linkage still needs correction |
| Support | DONE | Lookup, issues, timeline, and notes are usable |
| Reports / Bulk / Export | IN_PROGRESS | Good surface area, but at least one bulk path is not safe on the live schema |
| Audit / Security / Observability | DONE | Implemented and verified |
| Community Schema | DONE | Foundation only, intentionally decoupled from release |
| Release Readiness | BLOCKED | Cannot be called complete until earlier commerce foundations are reconciled |

---

## Audit Notes
- This file was corrected in a strict reconciliation pass.
- Status now reflects implemented reality, not prior intent or optimistic batch claims.
- Phase 2A is intentionally deferred by user direction while Phase 3 proceeds.
