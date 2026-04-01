# PHASES.md

## Current Active Phase
Phase 18 — Final Stabilization, QA, Testing, and Release Readiness

## Overall Status
- Project Status: RELEASE_CANDIDATE
- Reconciliation State: Release-candidate hardening verified on 2026-03-29
- Current Focus: Runtime defaults, payment initiation, notification lifecycle wiring, admin media reuse, and stale tracker cleanup are implemented and covered by the full automated suite
- Real Milestone: Core commerce, finance, support, shipping, content, notifications, and release-readiness verification are complete; Phase 2A remains intentionally deferred and non-blocking

---

## Phase Summary
| Phase | Status | Short Reason |
| --- | --- | --- |
| Phase 0 | DONE | Project boots, module structure exists, admin shell renders, docs/core stack exist; some tooling remains deferred but does not block runtime |
| Phase 1 | DONE | Staff/customer auth, core user entity design, avatar support, last-login tracking, local IAM models, role-permission relationships, role presets, permission naming conventions, policy/middleware coverage, explicit platform/franchise/branch foundations, organization assignment, scope constraints, staff-management workflows, security controls, and role-aware navigation foundations are implemented and tested |
| Phase 2 | DONE | Shared dashboard layout patterns, widget sections, empty/loading states, date presets, and all planned role-based dashboard slices are implemented and tested across both admin themes |
| Phase 2A | TODO | Intentionally deferred for now; the current admin UI system exists, but the Stitch-specific source-of-truth work is being skipped while later core phases proceed |
| Phase 3 | DONE | Settings center is fully implemented across grouped storage, runtime application, security/finance/payment/notification/shipping/system settings, safe connection tests, maintenance mode, media/storage settings, and feature flags, with passing coverage |
| Phase 4 | DONE | Catalog category/product foundations, SEO fields, media, variants, merchandising, admin filtering, bulk workflows, and edit audit visibility are implemented |
| Phase 5 | DONE | Inventory model foundations, adjustment/damage workflows, reservation release, branch transfer-ready foundation, and scoped stock/adjustment/low-stock/damaged-stock reports are implemented and covered |
| Phase 6 | DONE | Customer/account foundations, profile/address management, checkout-created account security, welcome trigger, and support timeline order/note context are implemented and tested |
| Phase 7 | DONE | Cart, checkout, thank-you/failure flows, order snapshots, order lifecycle wiring, returned/refund-ready handling, and the full planned admin order UX including filters, timeline, notes, and audit visibility are implemented and tested |
| Phase 8 | DONE | Payment transaction domain, adapter architecture, CMI/Payzone/Stripe foundations, offline bank-transfer configuration/rendering, finance-side manual verification, gateway-admin controls/validation, and redacted payment logging with failure/status audit are implemented and tested |
| Phase 9 | DONE | Shipping methods, shipment lifecycle, agent workspace, reports, and tracking APIs are implemented and verified |
| Phase 10 | DONE | Notification architecture, channel templates, logs, integrations, and release-blocking operational triggers are wired through real checkout, payment, shipping, and account flows |
| Phase 11 | DONE | Coupon validation, reporting, and abandoned-cart foundations are implemented to the planned foundation depth without forcing speculative marketing scope |
| Phase 12 | DONE | CMS/blog/redirects, product/category SEO, and admin media reuse support are implemented |
| Phase 13 | DONE | Finance transaction visibility, offline verification, COD reconciliation, refunds, and order linkage are implemented and covered |
| Phase 14 | DONE | Support lookup, issue queue, order timeline, and notes tooling are implemented in usable form |
| Phase 15 | DONE | Reports/export/search foundations and product/order/content bulk actions are implemented against the live schema |
| Phase 16 | DONE | Audit logging, sensitive-event coverage, security hardening, and observability foundations are implemented and tested |
| Phase 17 | DONE | Community schema, moderation foundation, onboarding hooks, and documentation boundaries are implemented without coupling release to unfinished UI |
| Phase 18 | DONE | Full test coverage, QA/release checks, performance review, docs updates, and release-readiness verification are complete |

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
  - `P3-SECURITY-03`
  - `P3-SECURITY-04`
  - `P3-FINANCE-01`
  - `P3-FINANCE-02`
  - `P3-FINANCE-03`
  - `P3-FINANCE-04`
  - `P3-PAYSET-01`
  - `P3-PAYSET-02`
  - `P3-PAYSET-03`
  - `P3-PAYSET-04`
  - `P3-PAYSET-05`
  - `P3-PAYSET-06`
  - `P3-PAYSET-07`
  - `P3-NOTIFY-01`
  - `P3-NOTIFY-02`
  - `P3-NOTIFY-03`
  - `P3-NOTIFY-04`
  - `P3-SHIPSET-01`
  - `P3-SHIPSET-02`
  - `P3-SHIPSET-03`
  - `P3-SYSTEM-01`
  - `P3-SYSTEM-02`
  - `P3-SYSTEM-03`
  - `P4-CATEGORY-01`
  - `P4-CATEGORY-02`
  - `P4-CATEGORY-03`
  - `P4-CATEGORY-04`
  - `P4-CATEGORY-05`
  - `P4-CATEGORY-06`
  - `P4-CATEGORY-07`
  - `P4-CATEGORY-08`
  - `P4-CATEGORY-09`
  - `P4-PRODUCT-01`
  - `P4-PRODUCT-02`
  - `P4-PRODUCT-03`
  - `P4-PRODUCT-04`
  - `P4-PRODUCT-05`
  - `P4-PRODUCT-06`
  - `P4-PRODUCT-07`
  - `P4-PRODUCT-08`
  - `P4-PRODUCT-09`
  - `P4-PRODUCT-10`
  - `P4-PRICE-01`
  - `P4-PRICE-02`
  - `P4-PRICE-03`
  - `P4-PRICE-04`
  - `P4-DESC-01`
  - `P4-DESC-02`
  - `P4-DESC-03`
  - `P4-MEDIA-01`
  - `P4-MEDIA-02`
  - `P4-MEDIA-03`
  - `P4-MEDIA-04`
  - `P4-MEDIA-05`
  - `P4-MEDIA-06`
  - `P4-VARIANT-01`
  - `P4-VARIANT-02`
  - `P4-VARIANT-03`
  - `P4-VARIANT-04`
  - `P4-VARIANT-05`
  - `P4-MERCH-01`
  - `P4-MERCH-02`
  - `P4-MERCH-03`
  - `P4-MERCH-04`
  - `P4-SEO-01`
  - `P4-SEO-02`
  - `P4-SEO-03`
  - `P4-SEO-04`
  - `P4-SEO-05`
  - `P4-SEO-06`
  - `P4-ADMIN-01`
  - `P4-ADMIN-02`
  - `P4-ADMIN-03`
  - `P4-ADMIN-04`
  - `P4-ADMIN-05`
  - `P5-MODEL-01`
  - `P5-MODEL-02`
  - `P5-MODEL-03`
  - `P5-MODEL-04`
  - `P5-MODEL-05`
  - `P5-ADJUST-01`
  - `P5-ADJUST-02`
  - `P5-ADJUST-03`
  - `P5-ADJUST-04`
  - `P5-ADJUST-05`
  - `P5-LOWSTOCK-01`
  - `P5-LOWSTOCK-02`
  - `P5-LOWSTOCK-03`
  - `P5-DAMAGE-01`
  - `P5-DAMAGE-02`
  - `P5-DAMAGE-03`
  - `P5-RESERVE-01`
  - `P5-RESERVE-02`
  - `P5-RESERVE-03`
  - `P5-BRANCH-01`
  - `P5-BRANCH-02`
  - `P5-BRANCH-03`
  - `P5-REPORT-01`
  - `P5-REPORT-02`
  - `P5-REPORT-03`
  - `P5-REPORT-04`
  - `P6-CUSTOMER-01`
  - `P6-CUSTOMER-02`
  - `P6-CUSTOMER-03`
  - `P6-PROFILE-01`
  - `P6-PROFILE-02`
  - `P6-PROFILE-03`
  - `P6-PROFILE-04`
  - `P6-PROFILE-05`
  - `P6-PROFILE-06`
  - `P6-ADDRESS-01`
  - `P6-ADDRESS-02`
  - `P6-ADDRESS-03`
  - `P6-ADDRESS-04`
  - `P6-ADDRESS-05`
  - `P6-ADDRESS-06`
  - `P6-ACCOUNT-01`
  - `P6-ACCOUNT-02`
  - `P6-ACCOUNT-03`
  - `P6-ACCOUNT-04`
  - `P6-ACCOUNT-05`
  - `P6-ACCOUNT-06`
  - `P6-CHECKOUT-01`
  - `P6-CHECKOUT-02`
  - `P6-CHECKOUT-03`
  - `P6-CHECKOUT-04`
  - `P6-TIMELINE-01`
  - `P6-TIMELINE-02`
  - `P6-TIMELINE-03`
  - `P7-CART-01`
  - `P7-CART-02`
  - `P7-CART-03`
  - `P7-CART-04`
  - `P7-CART-05`
  - `P7-CART-06`
  - `P7-CART-07`
  - `P7-CHECKOUT-01`
  - `P7-CHECKOUT-02`
  - `P7-CHECKOUT-03`
  - `P7-CHECKOUT-04`
  - `P7-CHECKOUT-05`
  - `P7-CHECKOUT-06`
  - `P7-CHECKOUT-07`
  - `P7-ORDER-01`
  - `P7-ORDER-02`
  - `P7-ORDER-03`
  - `P7-ORDER-04`
  - `P7-ORDER-05`
  - `P7-AUTOACC-01`
  - `P7-AUTOACC-02`
  - `P7-AUTOACC-03`
  - `P7-THANKYOU-01`
  - `P7-THANKYOU-02`
  - `P7-THANKYOU-03`
  - `P7-PAYERR-01`
  - `P7-PAYERR-02`
  - `P7-PAYERR-03`
  - `P7-STATUS-01`
  - `P7-STATUS-02`
  - `P7-STATUS-03`
  - `P7-STATUS-04`
  - `P7-STATUS-05`
  - `P7-STATUS-06`
  - `P7-STATUS-07`
  - `P7-STATUS-08`
  - `P7-STATUS-09`
  - `P7-ADMIN-01`
  - `P7-ADMIN-02`
  - `P7-ADMIN-03`
  - `P7-ADMIN-04`
  - `P7-ADMIN-05`
  - `P7-ADMIN-06`
  - `P7-ADMIN-07`
  - `P7-ADMIN-08`
  - `P7-ADMIN-09`
  - `P7-ADMIN-10`
  - `P8-DOMAIN-01`
  - `P8-DOMAIN-02`
  - `P8-DOMAIN-03`
  - `P8-DOMAIN-04`
  - `P8-ADAPTER-01`
  - `P8-ADAPTER-02`
  - `P8-ADAPTER-03`
  - `P8-ADAPTER-04`
  - `P8-CMI-01`
  - `P8-CMI-02`
  - `P8-CMI-03`
  - `P8-CMI-04`
  - `P8-PAYZONE-01`
  - `P8-PAYZONE-02`
  - `P8-PAYZONE-03`
  - `P8-PAYZONE-04`
  - `P8-STRIPE-01`
  - `P8-STRIPE-02`
  - `P8-STRIPE-03`
  - `P8-STRIPE-04`
  - `P8-OFFLINE-01`
  - `P8-OFFLINE-02`
  - `P8-OFFLINE-03`
  - `P8-OFFLINE-04`
  - `P8-OFFLINE-05`
  - `P8-GATEWAY-01`
  - `P8-GATEWAY-02`
  - `P8-GATEWAY-03`
  - `P8-GATEWAY-04`
  - `P8-LOGS-01`
  - `P8-LOGS-02`
  - `P8-LOGS-03`
  - `P10-TRIGGER-01`
  - `P10-TRIGGER-02`
  - `P10-TRIGGER-03`
  - `P10-TRIGGER-04`
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
- none at the phase level; only post-release-candidate hardening and deferred Phase 2A design-system work remain

---

## Next Task IDs
- none in the original phase ledger; current follow-up work is stabilization hardening on top of the completed release-candidate scope

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
| Settings Center | DONE | Grouped retrieval, validation, encrypted secrets, cache invalidation, locale/timezone runtime application, SMTP/Twilio/WhatsApp settings, safe connection tests, full SEO/security/finance/payment/notification/shipping/system settings, maintenance mode, media/storage settings, and feature flags are implemented and covered |
| Catalog | DONE | Category foundations are complete through hierarchy, CRUD guardrails, ordering, slug management, image/description/status handling, full category SEO metadata, and noindex support; product statuses, type handling, category relationships, tag assignment, dimensions, featured flag, base/sale pricing, internal cost, currency-ready pricing structure, short description, full description, safe rich-description rendering, featured media, gallery uploads, media ordering, alt-text editing, configured media storage, queue-backed media processing, option foundations, variant SKU/price/stock handling, variant media, related products, upsells, cross-sells, badges, full product SEO fields including canonical/OG/noindex, and nino-v2 product admin search/filter, bulk actions, publishing workflow, and edit audit visibility are in place |
| Inventory | DONE | Core operations are in place across product-level, variant-level, branch-aware, and reservation-ready stock; manual adjustments, threshold editing, low-stock queues, damaged-stock intake/history, order-bound reservation release on cancellation/failure/timeout, branch-scoped visibility/update rules, transfer-ready service foundations, and scoped stock / adjustment / low-stock / damaged-stock reports are implemented and covered |
| Customers / Account Area | DONE | Customer model foundations, login/setup flow, full profile-edit foundation including username/password change, address-book CRUD/default/validation foundations, customer account overview/history/detail/tracking screens, account address-management/profile-edit pages, checkout-created account auto-create/setup flow, welcome notification triggering, and support timeline order plus internal-note context are implemented and covered |
| Orders / Checkout | DONE | Cart item mutation, quantity updates, validated coupon apply/remove, totals, shipping estimate, free-shipping progress, merchandising suggestions, guest checkout, authenticated checkout, saved-address reuse, structured checkout error states, shipping-default application, inventory reservation, hard line-item/address snapshots, complete pricing summary fields, model-level order reference generation, secure checkout auto-account handling, signed and public success-summary lookup access, payment-failure recovery with order/payment reference resolution, real pending/awaiting-payment/paid/preparing/shipped/delivered/failed/cancelled lifecycle wiring, returned/refund-ready foundation handling, and the full planned admin orders UX with filters, detail timeline, internal/customer notes, and audit visibility are implemented and covered |
| Payments | DONE | Canonical transaction domain, adapter registry, response normalization, shared callback handling, CMI/Payzone/Stripe foundations, offline bank-transfer admin-plus-checkout rendering, finance-side manual offline verification, gateway-admin validation, and redacted payment logging with failure/status audit are implemented and verified |
| Shipping | DONE | Operationally solid |
| Notifications | DONE | Messaging center, templates, channels, logs, integrations, and the release-blocking operational triggers are implemented and wired |
| Promotions | DONE | Coupon validation, reporting, and abandoned-cart foundations are implemented to the planned release scope |
| CMS / SEO | DONE | Blog, redirects, product/category SEO attachment, and admin media reuse support are implemented |
| Finance | DONE | Reporting, refunds, transaction linkage, offline verification, and COD reconciliation are implemented and verified |
| Support | DONE | Lookup, issues, timeline, and notes are usable |
| Reports / Bulk / Export | DONE | Reporting, export, search, and live-schema-safe bulk actions are implemented and covered |
| Audit / Security / Observability | DONE | Implemented and verified |
| Community Schema | DONE | Foundation only, intentionally decoupled from release |
| Release Readiness | DONE | Release-candidate readiness is verified; remaining work is limited to stabilization hardening and operational go-live configuration |

---

## Audit Notes
- This file was corrected in a strict reconciliation pass.
- Status now reflects implemented reality, not prior intent or optimistic batch claims.
- Phase 2A is intentionally deferred by user direction while Phase 3 proceeds.
