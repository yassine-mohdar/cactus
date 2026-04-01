# Nino Backend — Phases & Tasks (Task-ID Version)

## Purpose
This file is the execution blueprint for the `nino-backend` project. It exists to guide AI coding agents and human reviewers through the full project in a controlled, modular, and production-minded way.

This document is intentionally detailed. It should be used before implementation begins for any major module, and updated only when the project scope materially changes.

## Core Principles
- Build as a **modular monolith**.
- Organize by **domain/module**, not by technical layer alone.
- Prioritize **commerce and operations first**.
- Keep **community** out of phase 1 delivery.
- Use **thin controllers**, **service/action classes**, **policies**, **queues**, and **audit logs**.
- Optimize for **maintainability**, **performance**, **clear permissions**, and **future scalability**.
- Never trade security for convenience.
- Never send plain passwords by email, SMS, or WhatsApp.

## Task ID Convention
Task IDs use this format:

- `P{phase number}-{module code}-{task number}`

Examples:
- `P0-INIT-01`
- `P1-IAM-01`
- `P2-DASH-01`
- `P4-CATALOG-01`
- `P7-CHECKOUT-01`

These IDs are the source of truth for `PHASES.md`.
`PHASES.md` should track status primarily by task ID, not by long descriptive text.

## Global Non-Functional Requirements

### Performance
- No N+1 queries.
- Add indexes for high-frequency filters and lookups.
- Queue all notifications, webhooks, media processing, and heavy exports/imports.
- Cache settings, lookup data, and safe summary widgets.
- Paginate all large tables.
- Optimize images/media and use storage abstraction.
- Add slow-query monitoring and basic performance logging.

### Security
- Enforce strong validation everywhere.
- Use policies and scoped permissions for all sensitive actions.
- Keep API credentials encrypted and never expose secrets in logs.
- Use password setup links, magic links, or OTPs instead of sending passwords.
- Add audit logs for sensitive actions such as price edits, refunds, role changes, status overrides, stock adjustments, and payment updates.
- Support forced staff 2FA at the settings level.

### Code Quality
- Domain-oriented modules.
- Thin controllers.
- Business logic in services/actions.
- Clear DTOs/value objects where complexity justifies them.
- Consistent naming and module conventions.
- Feature tests for critical user flows.
- Factories and seeders for development/test data.

### Admin UX
- Professional, fast, dense UI.
- No playful admin UI.
- Role-adapted sidebars/navigation.
- Search, filters, bulk actions, tabs, grouped forms, and clear status indicators.
- Compact but readable tables.

---

# Phase 0 — Project Foundation & Architecture

## Phase ID
`P0`

## Objective
Establish the technical, architectural, and documentation foundation for the project before business modules are built.

## Dependencies
- Requires: none
- Blocks: all later phases

## Deliverables
- `nino-backend` Laravel project bootstrapped.
- Base module structure created.
- Core docs created:
  - `PROJECT_PROGRESS.md`
  - `ARCHITECTURE.md`
  - `PHASES.md`
  - `phases-tasks.md`
- Initial admin shell and navigation scaffold.
- Core stack configured.

## Tasks

### 0.1 Initialize project
- [ ] `P0-INIT-01` Create `nino-backend` folder
- [ ] `P0-INIT-02` Initialize Laravel 12 project
- [ ] `P0-INIT-03` Set PHP version compatibility to 8.3+
- [ ] `P0-INIT-04` Configure environment files and sane defaults
- [ ] `P0-INIT-05` Choose and configure database driver
- [ ] `P0-INIT-06` Configure Redis for queue/cache

### 0.2 Configure core packages/tools
- [ ] `P0-TOOLS-01` Set up Tailwind CSS
- [ ] `P0-TOOLS-02` Set up Livewire or chosen admin UI stack
- [ ] `P0-TOOLS-03` Set up Sanctum/Fortify/auth stack
- [ ] `P0-TOOLS-04` Set up Pest
- [ ] `P0-TOOLS-05` Set up code style/linting if applicable
- [ ] `P0-TOOLS-06` Set up debug and local mail/testing utilities

### 0.3 Create module structure
- [ ] `P0-MODULES-01` Define module directory standard
- [ ] `P0-MODULES-02` Create IAM module
- [ ] `P0-MODULES-03` Create Organizations module
- [ ] `P0-MODULES-04` Create Catalog module
- [ ] `P0-MODULES-05` Create Inventory module
- [ ] `P0-MODULES-06` Create Orders module
- [ ] `P0-MODULES-07` Create Checkout module
- [ ] `P0-MODULES-08` Create Payments module
- [ ] `P0-MODULES-09` Create Shipping module
- [ ] `P0-MODULES-10` Create Customers module
- [ ] `P0-MODULES-11` Create Notifications module
- [ ] `P0-MODULES-12` Create Coupons module
- [ ] `P0-MODULES-13` Create CMS module
- [ ] `P0-MODULES-14` Create Finance module
- [ ] `P0-MODULES-15` Create Reports module
- [ ] `P0-MODULES-16` Create Settings module
- [ ] `P0-MODULES-17` Create Support module
- [ ] `P0-MODULES-18` Create Audit module
- [ ] `P0-MODULES-19` Create Community module
- [ ] `P0-MODULES-20` Add README or stub files if needed to keep structure visible

### 0.4 Define architecture conventions
- [ ] `P0-ARCH-01` Define controller rules
- [ ] `P0-ARCH-02` Define service/action rules
- [ ] `P0-ARCH-03` Define event/listener rules
- [ ] `P0-ARCH-04` Define job/queue rules
- [ ] `P0-ARCH-05` Define policy/permission rules
- [ ] `P0-ARCH-06` Define request validation rules
- [ ] `P0-ARCH-07` Define resource/transformer rules
- [ ] `P0-ARCH-08` Define audit logging standard

### 0.5 Admin shell foundation
- [ ] `P0-ADMIN-01` Build base layout
- [ ] `P0-ADMIN-02` Create topbar/sidebar/content shell
- [ ] `P0-ADMIN-03` Add theme tokens for professional palette
- [ ] `P0-ADMIN-04` Create role-aware menu placeholder system
- [ ] `P0-ADMIN-05` Add flash message pattern
- [ ] `P0-ADMIN-06` Add page header pattern
- [ ] `P0-ADMIN-07` Add reusable table/filter/form card components

### 0.6 Documentation foundation
- [ ] `P0-DOCS-01` Create `PROJECT_PROGRESS.md` with concise template
- [ ] `P0-DOCS-02` Create `ARCHITECTURE.md` with module map
- [ ] `P0-DOCS-03` Create `PHASES.md` with phase summary
- [ ] `P0-DOCS-04` Ensure docs remain concise and low-token for AI continuation

## Acceptance Criteria
- Project boots locally.
- Admin shell renders.
- Module structure exists.
- Queue/cache/database configs are working.
- Base docs exist and are clear.

---

# Phase 1 — IAM, Roles, Permissions, and Organizations

## Phase ID
`P1`

## Objective
Build authentication, authorization, scoped access, and the organization model that all business modules depend on.

## Dependencies
- Requires: Phase 0 complete
- Blocks: all scoped admin features, dashboards, branch/franchise logic

## Deliverables
- Secure auth system.
- Role presets and custom role capability foundation.
- Scoped permissions.
- Organization hierarchy foundation.
- Staff/user management UI.
- Role-aware navigation and dashboard routing.

## Tasks

### 1.1 Authentication foundation
- [x] `P1-AUTH-01` Implement staff authentication
- [x] `P1-AUTH-02` Implement customer authentication foundation
- [x] `P1-AUTH-03` Implement password reset / password setup flow
- [x] `P1-AUTH-04` Implement magic-link or password setup flow for checkout-created accounts
- [x] `P1-AUTH-05` Implement session management foundation
- [x] `P1-AUTH-06` Implement optional remember-me behavior if appropriate

### 1.2 User model design
- [x] `P1-USERS-01` Define core user entity
- [x] `P1-USERS-02` Add user type handling (staff/customer or equivalent)
- [x] `P1-USERS-03` Add profile attributes
- [x] `P1-USERS-04` Add avatar/profile picture support foundation
- [x] `P1-USERS-05` Add active/inactive/suspended states
- [x] `P1-USERS-06` Add last login tracking

### 1.3 Role and permission system
- [x] `P1-IAM-01` Define permission model
- [x] `P1-IAM-02` Define role model
- [x] `P1-IAM-03` Define role-permission relationships
- [x] `P1-IAM-04` Add role presets seed/data
- [x] `P1-IAM-05` Support custom roles
- [x] `P1-IAM-06` Support permission overrides
- [x] `P1-IAM-07` Support scope-aware permissions: platform
- [x] `P1-IAM-08` Support scope-aware permissions: franchise
- [x] `P1-IAM-09` Support scope-aware permissions: branch
- [x] `P1-IAM-10` Support scope-aware permissions: own records only

### 1.4 Policy and authorization layer
- [x] `P1-POLICY-01` Build policies for all major models/actions
- [x] `P1-POLICY-02` Add middleware/helpers for permission checks
- [x] `P1-POLICY-03` Define naming conventions for permissions
- [x] `P1-POLICY-04` Ensure UI respects permissions and hides forbidden actions

### 1.5 Organization hierarchy
- [x] `P1-ORG-01` Define platform entity
- [x] `P1-ORG-02` Define franchise entity
- [x] `P1-ORG-03` Define branch entity
- [x] `P1-ORG-04` Define user assignment to organization units
- [x] `P1-ORG-05` Define scope constraints
- [x] `P1-ORG-06` Prepare for branch-aware stock/orders/reports

### 1.6 Staff user management
- [x] `P1-STAFF-01` Create staff list/search/filter UI
- [x] `P1-STAFF-02` Create add staff UI
- [x] `P1-STAFF-03` Create edit staff UI
- [x] `P1-STAFF-04` Assign roles and scopes
- [x] `P1-STAFF-05` Activate/deactivate staff
- [x] `P1-STAFF-06` Implement reset access/setup link flow

### 1.7 Security controls
- [x] `P1-SEC-01` Add staff 2FA enforcement foundation
- [x] `P1-SEC-02` Add session revoke/forced logout foundation
- [x] `P1-SEC-03` Add impersonation with audit logs
- [x] `P1-SEC-04` Add audit logs for role changes and sensitive account updates

### 1.8 Role-aware menu/dashboard routing
- [x] `P1-NAV-01` Build role-adapted sidebar items
- [x] `P1-NAV-02` Build role-based home dashboard routing
- [x] `P1-NAV-03` Ensure permission-safe navigation

## Acceptance Criteria
- Staff can securely log in.
- Permissions are scope-aware.
- Roles can be assigned and enforced.
- Organization hierarchy exists.
- Sensitive auth/role actions are audited.

---

# Phase 2 — Dashboards & Admin Workspaces

## Phase ID
`P2`

## Objective
Provide each role with a focused dashboard and workspace entry point.

## Dependencies
- Requires: Phase 1 complete
- Blocks: efficient role-based daily operations

## Deliverables
- Dashboard framework.
- Widgets by role.
- Reusable dashboard card system.
- Quick actions and issue widgets.

## Tasks

### 2.1 Dashboard framework
- [x] `P2-DASH-01` Create dashboard layout patterns
- [x] `P2-DASH-02` Support widget-based sections
- [x] `P2-DASH-03` Add loading states and empty states
- [x] `P2-DASH-04` Add lightweight filtering/date presets where appropriate

### 2.2 Super Admin dashboard
- [x] `P2-SUPER-01` Add GMV summary widget
- [x] `P2-SUPER-02` Add orders today widget
- [x] `P2-SUPER-03` Add revenue trend widget
- [x] `P2-SUPER-04` Add failed payments widget
- [x] `P2-SUPER-05` Add low stock alerts widget
- [x] `P2-SUPER-06` Add shipping exceptions widget
- [x] `P2-SUPER-07` Add franchise performance summary widget
- [x] `P2-SUPER-08` Add branch performance summary widget
- [x] `P2-SUPER-09` Add support KPI widget
- [x] `P2-SUPER-10` Add finance summary widget
- [x] `P2-SUPER-11` Add system alerts widget

### 2.3 Platform Admin dashboard
- [x] `P2-PLATFORM-01` Add operational action queue
- [x] `P2-PLATFORM-02` Add recent orders widget
- [x] `P2-PLATFORM-03` Add stock alerts widget
- [x] `P2-PLATFORM-04` Add payment exceptions widget
- [x] `P2-PLATFORM-05` Add content/promo highlights widget

### 2.4 Franchise dashboard
- [x] `P2-FRANCHISE-01` Add sales by branch widget
- [x] `P2-FRANCHISE-02` Add branch stock overview widget
- [x] `P2-FRANCHISE-03` Add staff performance summary widget
- [x] `P2-FRANCHISE-04` Add branch order metrics widget

### 2.5 Branch dashboard
- [x] `P2-BRANCH-01` Add orders to prepare widget
- [x] `P2-BRANCH-02` Add branch stock widgets
- [x] `P2-BRANCH-03` Add dispatch queue widget
- [x] `P2-BRANCH-04` Add branch issue queue widget

### 2.6 Customer Support dashboard
- [x] `P2-SUPPORT-01` Add order lookup widget
- [x] `P2-SUPPORT-02` Add customer lookup widget
- [x] `P2-SUPPORT-03` Add open cases/refund queue foundation
- [x] `P2-SUPPORT-04` Add recent issue timeline widget

### 2.7 Shipping dashboard
- [x] `P2-SHIPPING-01` Add ready-to-ship widget
- [x] `P2-SHIPPING-02` Add shipped widget
- [x] `P2-SHIPPING-03` Add failed delivery widget
- [x] `P2-SHIPPING-04` Add returned parcel queue widget
- [x] `P2-SHIPPING-05` Add tracking queue widget

### 2.8 Finance dashboard
- [x] `P2-FINANCE-01` Add paid/unpaid summary widget
- [x] `P2-FINANCE-02` Add payment method summary widget
- [x] `P2-FINANCE-03` Add gateway transactions summary widget
- [x] `P2-FINANCE-04` Add COD reconciliation summary widget
- [x] `P2-FINANCE-05` Add refund summary widget
- [x] `P2-FINANCE-06` Add discount/fee impact widgets

### 2.9 SEO / Content dashboard
- [x] `P2-SEO-01` Add drafts widget
- [x] `P2-SEO-02` Add missing metadata widget
- [x] `P2-SEO-03` Add SEO issue summary widget
- [x] `P2-SEO-04` Add scheduled content widget if applicable

### 2.10 Media Buying dashboard
- [x] `P2-MEDIA-01` Add integration health widget
- [x] `P2-MEDIA-02` Add attribution/coupon campaign hooks widget
- [x] `P2-MEDIA-03` Add traffic/campaign placeholders if analytics later connect

### 2.11 Sales dashboard
- [x] `P2-SALES-01` Add revenue trend widget
- [x] `P2-SALES-02` Add AOV widget
- [x] `P2-SALES-03` Add best sellers widget
- [x] `P2-SALES-04` Add promo performance widget

### 2.12 Stock dashboard
- [x] `P2-STOCK-01` Add low stock widget
- [x] `P2-STOCK-02` Add damaged stock widget
- [x] `P2-STOCK-03` Add adjustment summary widget
- [x] `P2-STOCK-04` Add transfer-ready metrics widget

### 2.13 Community Moderator dashboard
- [x] `P2-MOD-01` Add report queue schema-ready placeholders if community is not fully implemented

## Acceptance Criteria
- Each role lands on a tailored dashboard.
- Dashboard widgets respect scopes and permissions.
- No dashboard makes unnecessary heavy queries.

---
# Phase 2A — Admin UI/UX Design System & Stitch Implementation

## Phase ID
`P2A`

## Objective
Implement the actual admin platform UI/UX design generated in Stitch and apply it as the visual/design-system foundation for the backend.

This phase exists to transform the current generic admin UI into a production-grade, reusable, professional interface aligned with the Stitch design assets stored in `stitch_assets`.

This is not a static HTML copy task.
This is a real implementation phase where the Stitch assets are translated into a maintainable Laravel admin design system using reusable layouts, components, tables, filters, forms, and dashboard patterns.

## Dependencies
- Requires: Phase 0 complete
- Recommended: Phase 1 and Phase 2 foundations available
- Supports: all later CRUD/admin modules

## Deliverables
- Stitch design assets audited and interpreted
- Reusable admin design system implemented
- Global admin layout updated
- Sidebar/topbar/page-shell aligned with Stitch
- Dashboard UI aligned with Stitch
- Tables/list pages aligned with Stitch
- Forms/settings pages aligned with Stitch
- Design consistency applied across core admin screens
- Existing functionality preserved

## Not in Scope
- Marketing/landing-page style playful frontend
- Rebuilding business logic
- Replacing backend functionality with static HTML
- Blindly dumping Stitch export into production templates
- Full customer storefront redesign unless explicitly requested

## Tasks

### 2A.1 Stitch asset audit
- [ ] `P2A-AUDIT-01` Inspect `stitch_assets` folder
- [ ] `P2A-AUDIT-02` Identify layout patterns
- [ ] `P2A-AUDIT-03` Identify navigation/sidebar patterns
- [ ] `P2A-AUDIT-04` Identify dashboard widget patterns
- [ ] `P2A-AUDIT-05` Identify card, table, form, filter, and settings patterns
- [ ] `P2A-AUDIT-06` Extract color tokens from Stitch assets
- [ ] `P2A-AUDIT-07` Extract typography scale from Stitch assets
- [ ] `P2A-AUDIT-08` Extract spacing system from Stitch assets
- [ ] `P2A-AUDIT-09` Extract button hierarchy and interaction states
- [ ] `P2A-AUDIT-10` Compare current admin UI against Stitch UI and document gaps briefly

### 2A.2 Design system foundation
- [ ] `P2A-DESIGN-01` Define admin color tokens in code
- [ ] `P2A-DESIGN-02` Define typography tokens/classes
- [ ] `P2A-DESIGN-03` Define spacing system
- [ ] `P2A-DESIGN-04` Define border radius, shadows, and surface styles
- [ ] `P2A-DESIGN-05` Define status/badge color system
- [ ] `P2A-DESIGN-06` Define button variants
- [ ] `P2A-DESIGN-07` Define input/select/textarea field styles
- [ ] `P2A-DESIGN-08` Define table/listing styles
- [ ] `P2A-DESIGN-09` Define empty/loading/error state styles
- [ ] `P2A-DESIGN-10` Define reusable layout and container rules

### 2A.3 Global admin shell implementation
- [ ] `P2A-SHELL-01` Refactor global admin layout to match Stitch direction
- [ ] `P2A-SHELL-02` Implement Stitch-aligned sidebar
- [ ] `P2A-SHELL-03` Implement Stitch-aligned topbar
- [ ] `P2A-SHELL-04` Implement role-aware navigation visuals using existing menu system
- [ ] `P2A-SHELL-05` Implement page header pattern
- [ ] `P2A-SHELL-06` Implement breadcrumb pattern if applicable
- [ ] `P2A-SHELL-07` Implement flash/alert presentation aligned with Stitch
- [ ] `P2A-SHELL-08` Implement responsive admin layout behavior

### 2A.4 Reusable component system
- [ ] `P2A-COMP-01` Build reusable card component styles
- [ ] `P2A-COMP-02` Build stat/KPI widget component styles
- [ ] `P2A-COMP-03` Build badge/status chip components
- [ ] `P2A-COMP-04` Build button components/variants
- [ ] `P2A-COMP-05` Build dropdown/menu styles
- [ ] `P2A-COMP-06` Build tabs/section navigation styles
- [ ] `P2A-COMP-07` Build modal/drawer styles if needed
- [ ] `P2A-COMP-08` Build search/filter bar pattern
- [ ] `P2A-COMP-09` Build pagination pattern
- [ ] `P2A-COMP-10` Build form section/card pattern
- [ ] `P2A-COMP-11` Build empty state pattern
- [ ] `P2A-COMP-12` Build confirmation/destructive action pattern

### 2A.5 Dashboard UI implementation
- [ ] `P2A-DASH-01` Apply Stitch layout to dashboard page shell
- [ ] `P2A-DASH-02` Refactor KPI/stat widgets to Stitch style
- [ ] `P2A-DASH-03` Refactor dashboard cards and sections to Stitch style
- [ ] `P2A-DASH-04` Align dashboard spacing/grid with Stitch
- [ ] `P2A-DASH-05` Align loading/empty/error dashboard states with Stitch

### 2A.6 Table and list-page implementation
- [ ] `P2A-TABLE-01` Refactor table/list page shell to Stitch style
- [ ] `P2A-TABLE-02` Refactor filters/search bar to Stitch style
- [ ] `P2A-TABLE-03` Refactor status columns/badges to Stitch style
- [ ] `P2A-TABLE-04` Refactor row action patterns
- [ ] `P2A-TABLE-05` Refactor bulk action bar
- [ ] `P2A-TABLE-06` Refactor pagination/list footer
- [ ] `P2A-TABLE-07` Refactor empty list states
- [ ] `P2A-TABLE-08` Ensure dense but readable operational table UX

### 2A.7 Forms and CRUD pages
- [ ] `P2A-FORM-01` Refactor create/edit page shell to Stitch style
- [ ] `P2A-FORM-02` Refactor grouped form sections/cards
- [ ] `P2A-FORM-03` Refactor labels/helper/error states
- [ ] `P2A-FORM-04` Refactor save/cancel/action bar patterns
- [ ] `P2A-FORM-05` Refactor long-form layouts for settings and complex CRUD
- [ ] `P2A-FORM-06` Refactor file/image upload presentation
- [ ] `P2A-FORM-07` Ensure form interaction states align with Stitch

### 2A.8 Settings UI implementation
- [ ] `P2A-SETTINGS-01` Apply Stitch design to settings shell
- [ ] `P2A-SETTINGS-02` Implement tab/subnavigation style for settings
- [ ] `P2A-SETTINGS-03` Refactor secret/API key fields presentation
- [ ] `P2A-SETTINGS-04` Refactor toggle/checkbox/radio patterns
- [ ] `P2A-SETTINGS-05` Refactor helper descriptions and save feedback
- [ ] `P2A-SETTINGS-06` Ensure settings UX feels enterprise-grade and clean

### 2A.9 Functional safety and refactor discipline
- [ ] `P2A-SAFE-01` Preserve existing data rendering
- [ ] `P2A-SAFE-02` Preserve existing forms and submissions
- [ ] `P2A-SAFE-03` Preserve existing filters/search behavior
- [ ] `P2A-SAFE-04` Preserve permissions/policies visibility rules
- [ ] `P2A-SAFE-05` Preserve Livewire/Blade interactions
- [ ] `P2A-SAFE-06` Remove duplicated UI markup where possible
- [ ] `P2A-SAFE-07` Avoid raw Stitch HTML copy-paste debt

### 2A.10 Final polish and consistency pass
- [ ] `P2A-POLISH-01` Run visual consistency pass across admin screens
- [ ] `P2A-POLISH-02` Fix spacing inconsistencies
- [ ] `P2A-POLISH-03` Fix responsiveness issues
- [ ] `P2A-POLISH-04` Fix state inconsistencies
- [ ] `P2A-POLISH-05` Ensure UI remains professional, dense, and fast
- [ ] `P2A-POLISH-06` Update `PROJECT_PROGRESS.md`
- [ ] `P2A-POLISH-07` Update `PHASES.md`

## Acceptance Criteria
- Stitch assets are used as the visual source of truth
- Admin layout clearly reflects Stitch design direction
- Dashboard/list/form/settings screens align with the same design system
- UI is implemented through reusable components, not static pasted HTML
- Existing backend functionality remains intact
- Role-aware navigation still works
- UI is professional, fast, maintainable, and production-ready
# Phase 3 — Settings & System Configuration

## Phase ID
`P3`

## Objective
Build a robust settings center that controls platform behavior, branding, integrations, and operational defaults.

## Dependencies
- Requires: Phase 1 complete
- Supports: most later modules

## Deliverables
- Multi-tab settings module.
- Secure credentials handling.
- Settings caching and invalidation.

## Tasks

### 3.1 Settings foundation
- [x] `P3-SETTINGS-01` Define settings storage strategy
- [x] `P3-SETTINGS-02` Implement grouped settings retrieval
- [x] `P3-SETTINGS-03` Add validation per settings section
- [x] `P3-SETTINGS-04` Add secure secret storage
- [x] `P3-SETTINGS-05` Add cache and cache invalidation

### 3.2 General settings
- [x] `P3-GENERAL-01` Add website information settings
- [x] `P3-GENERAL-02` Add company information settings
- [x] `P3-GENERAL-03` Add contact information settings
- [x] `P3-GENERAL-04` Add branding basics settings
- [x] `P3-GENERAL-05` Add locale/timezone if needed

### 3.3 Connections / Integrations settings
- [x] `P3-CONN-01` Add SMTP settings
- [x] `P3-CONN-02` Add Twilio SMS settings
- [x] `P3-CONN-03` Add WhatsApp API foundation settings
- [x] `P3-CONN-04` Add test connection utilities if safe

### 3.4 SEO settings
- [x] `P3-SEO-01` Add analytics integration fields
- [x] `P3-SEO-02` Add Google verification field
- [x] `P3-SEO-03` Add Facebook Pixel field
- [x] `P3-SEO-04` Add TikTok Pixel field
- [x] `P3-SEO-05` Add SEO defaults settings

### 3.5 Security settings
- [x] `P3-SECURITY-01` Add force staff/admin 2FA toggle
- [x] `P3-SECURITY-02` Add password policy settings foundation
- [x] `P3-SECURITY-03` Add session timeout foundation
- [x] `P3-SECURITY-04` Add security alert settings foundation

### 3.6 Finance settings
- [x] `P3-FINANCE-01` Add base currency settings
- [x] `P3-FINANCE-02` Add multi-currency foundation settings
- [x] `P3-FINANCE-03` Add conversion adjustment rules
- [x] `P3-FINANCE-04` Add financial defaults settings

### 3.7 Payment method settings
- [x] `P3-PAYSET-01` Add CMI config settings
- [x] `P3-PAYSET-02` Add Payzone config settings
- [x] `P3-PAYSET-03` Add Stripe config settings
- [x] `P3-PAYSET-04` Add offline payment methods CRUD settings
- [x] `P3-PAYSET-05` Add bank transfer default setup
- [x] `P3-PAYSET-06` Add test/live modes
- [x] `P3-PAYSET-07` Add gateway enable/disable controls

### 3.8 Notifications settings
- [x] `P3-NOTIFY-01` Add channel enable/disable settings
- [x] `P3-NOTIFY-02` Add sender name settings
- [x] `P3-NOTIFY-03` Add basic template global settings
- [x] `P3-NOTIFY-04` Add retry/queue behavior foundation

### 3.9 Shipping settings
- [x] `P3-SHIPSET-01` Add shipping defaults
- [x] `P3-SHIPSET-02` Add carrier/method management foundation
- [x] `P3-SHIPSET-03` Add tracking-related defaults

### 3.10 System / maintenance settings
- [x] `P3-SYSTEM-01` Add maintenance mode
- [x] `P3-SYSTEM-02` Add media/storage settings foundation
- [x] `P3-SYSTEM-03` Add feature flags foundation

## Acceptance Criteria
- Settings are grouped, validated, cached, and secure.
- Secrets are protected.
- Changes are reflected in system behavior.

---

# Phase 4 — Catalog: Categories, Products, Media, SEO Fields

## Phase ID
`P4`

## Objective
Build the product catalog foundation with strong admin UX and SEO readiness.

## Dependencies
- Requires: Phase 1 complete
- Recommended: Phase 3 available

## Deliverables
- Categories CRUD.
- Products CRUD.
- Variants foundation.
- Product media management.
- Product/category SEO fields.

## Tasks

### 4.1 Category management
- [x] `P4-CATEGORY-01` Define nested category model
- [x] `P4-CATEGORY-02` Add parent/child support
- [x] `P4-CATEGORY-03` Add category create/edit/delete rules
- [x] `P4-CATEGORY-04` Add category ordering
- [x] `P4-CATEGORY-05` Add slug management
- [x] `P4-CATEGORY-06` Add category image
- [x] `P4-CATEGORY-07` Add category description
- [x] `P4-CATEGORY-08` Add active/inactive status
- [x] `P4-CATEGORY-09` Add SEO fields

### 4.2 Product model design
- [x] `P4-PRODUCT-01` Add simple product support
- [x] `P4-PRODUCT-02` Add variable product support
- [x] `P4-PRODUCT-03` Add SKU
- [x] `P4-PRODUCT-04` Add slug
- [x] `P4-PRODUCT-05` Add statuses: draft/published/archived
- [x] `P4-PRODUCT-06` Add product type handling
- [x] `P4-PRODUCT-07` Add category relationships
- [x] `P4-PRODUCT-08` Add tags
- [x] `P4-PRODUCT-09` Add dimensions/weight
- [x] `P4-PRODUCT-10` Add featured flag

### 4.3 Product pricing fields
- [x] `P4-PRICE-01` Add price
- [x] `P4-PRICE-02` Add sale price
- [x] `P4-PRICE-03` Add internal cost if included
- [x] `P4-PRICE-04` Add currency-ready structure

### 4.4 Product description fields
- [x] `P4-DESC-01` Add short description
- [x] `P4-DESC-02` Add full description
- [x] `P4-DESC-03` Add rich content support if needed

### 4.5 Product media
- [x] `P4-MEDIA-01` Add featured image
- [x] `P4-MEDIA-02` Add gallery
- [x] `P4-MEDIA-03` Add media ordering
- [x] `P4-MEDIA-04` Add alt text foundation
- [x] `P4-MEDIA-05` Add media storage abstraction
- [x] `P4-MEDIA-06` Add image processing queue if needed

### 4.6 Variable products
- [x] `P4-VARIANT-01` Add option/attribute foundation
- [x] `P4-VARIANT-02` Add variant SKU
- [x] `P4-VARIANT-03` Add variant price
- [x] `P4-VARIANT-04` Add variant stock
- [x] `P4-VARIANT-05` Add variant media support 

### 4.7 Merchandising fields
- [x] `P4-MERCH-01` Add related products
- [x] `P4-MERCH-02` Add upsells
- [x] `P4-MERCH-03` Add cross-sells
- [x] `P4-MERCH-04` Add product badges foundation

### 4.8 Catalog SEO
- [x] `P4-SEO-01` Add product meta title
- [x] `P4-SEO-02` Add product meta description
- [x] `P4-SEO-03` Add canonical foundation
- [x] `P4-SEO-04` Add OG fields if included
- [x] `P4-SEO-05` Add noindex foundation if included
- [x] `P4-SEO-06` Add category SEO

### 4.9 Catalog admin UX
- [x] `P4-ADMIN-01` Build table with search/filter
- [x] `P4-ADMIN-02` Add filters for status/category/type/stock
- [x] `P4-ADMIN-03` Add bulk actions
- [x] `P4-ADMIN-04` Add draft/published workflows
- [x] `P4-ADMIN-05` Add audit log visibility for edits

## Acceptance Criteria
- Products and categories are easy to manage.
- Variants are supported.
- SEO fields exist.
- Media works reliably.

---

# Phase 5 — Inventory & Stock Operations

## Phase ID
`P5`

## Objective
Build inventory features stronger than basic ecommerce stock handling, with branch-ready design.

## Dependencies
- Requires: Phase 4
- Supports: Orders, Checkout, Shipping, Reports

## Deliverables
- Stock management.
- Stock adjustment workflows.
- Low stock alerts.
- Variant stock.
- Branch-aware stock architecture.

## Tasks

### 5.1 Inventory data model
- [x] `P5-MODEL-01` Define stock item model or equivalent
- [x] `P5-MODEL-02` Add product-level stock
- [x] `P5-MODEL-03` Add variant-level stock
- [x] `P5-MODEL-04` Add branch-awareness
- [x] `P5-MODEL-05` Add reservation/release-ready fields

### 5.2 Stock adjustment workflows
- [x] `P5-ADJUST-01` Build manual adjustment UI
- [x] `P5-ADJUST-02` Add adjustment reasons
- [x] `P5-ADJUST-03` Support positive/negative adjustments
- [x] `P5-ADJUST-04` Add staff attribution
- [x] `P5-ADJUST-05` Add audit logs

### 5.3 Low stock management
- [x] `P5-LOWSTOCK-01` Add threshold fields
- [x] `P5-LOWSTOCK-02` Add low stock alert list
- [x] `P5-LOWSTOCK-03` Add dashboard widgets

### 5.4 Damaged stock
- [x] `P5-DAMAGE-01` Build damaged stock workflow
- [x] `P5-DAMAGE-02` Add separate reason logging
- [x] `P5-DAMAGE-03` Add reporting visibility

### 5.5 Reservation and release design
- [x] `P5-RESERVE-01` Define reserve stock on checkout/payment stage rules
- [x] `P5-RESERVE-02` Implement release reserved stock on cancellation/failure/timeout
- [x] `P5-RESERVE-03` Ensure future readiness even if simple first pass

### 5.6 Branch-aware architecture
- [x] `P5-BRANCH-01` Add branch stock visibility
- [x] `P5-BRANCH-02` Add branch stock update rules
- [x] `P5-BRANCH-03` Add transfer-ready schema and services

### 5.7 Inventory reports foundation
- [x] `P5-REPORT-01` Add current stock report
- [x] `P5-REPORT-02` Add adjustment history report
- [x] `P5-REPORT-03` Add low stock report
- [x] `P5-REPORT-04` Add damaged stock report

## Acceptance Criteria
- Stock changes are auditable.
- Low stock is visible.
- Branch-aware logic is prepared cleanly.
- Variant stock works.

---

# Phase 6 — Customers, Profiles, Addresses, and Account Area

## Phase ID
`P6`

## Objective
Build the customer identity and account layer used by checkout, orders, support, and future community.

## Dependencies
- Requires: Phase 1
- Recommended: Phase 7 in parallel for account-order connection

## Deliverables
- Customer account area.
- Address book.
- Profile editing.
- Order history foundation.

## Tasks

### 6.1 Customer model refinement
- [x] `P6-CUSTOMER-01` Add customer-specific fields
- [x] `P6-CUSTOMER-02` Add account states
- [x] `P6-CUSTOMER-03` Add marketing preference fields foundation 

### 6.2 Customer profile
- [x] `P6-PROFILE-01` Add profile picture
- [x] `P6-PROFILE-02` Add first name
- [x] `P6-PROFILE-03` Add last name
- [x] `P6-PROFILE-04` Add email
- [x] `P6-PROFILE-05` Add username
- [x] `P6-PROFILE-06` Add password change

### 6.3 Address book
- [x] `P6-ADDRESS-01` Add billing addresses
- [x] `P6-ADDRESS-02` Add shipping addresses
- [x] `P6-ADDRESS-03` Add add/edit/delete actions
- [x] `P6-ADDRESS-04` Add default/primary address
- [x] `P6-ADDRESS-05` Add validation rules
- [x] `P6-ADDRESS-06` Add country/city/state structure as needed

### 6.4 Account area screens
- [x] `P6-ACCOUNT-01` Build account overview
- [x] `P6-ACCOUNT-02` Build order history
- [x] `P6-ACCOUNT-03` Build order details
- [x] `P6-ACCOUNT-04` Build tracking visibility
- [x] `P6-ACCOUNT-05` Build address management
- [x] `P6-ACCOUNT-06` Build profile edit

### 6.5 Checkout-created accounts
- [x] `P6-CHECKOUT-01` Add auto account creation after checkout
- [x] `P6-CHECKOUT-02` Add password setup link or magic-link flow
- [x] `P6-CHECKOUT-03` Add welcome notification trigger
- [x] `P6-CHECKOUT-04` Ensure no plain password sending

### 6.6 Customer support timeline foundation
- [x] `P6-TIMELINE-01` Add customer order summary
- [x] `P6-TIMELINE-02` Add recent order statuses
- [x] `P6-TIMELINE-03` Add internal notes foundation

## Acceptance Criteria
- Customers can manage profile and addresses.
- Orders are visible from account.
- Checkout-created accounts are secure.

---

# Phase 7 — Orders, Cart, Checkout, Thank You, and Error Flows

## Phase ID
`P7`

## Objective
Implement the full commerce flow from cart through checkout to post-order states.

## Dependencies
- Requires: Phases 4, 5, 6
- Works with: Phase 8, Phase 9, Phase 10

## Deliverables
- Cart.
- Checkout.
- Order creation.
- Thank you page.
- Payment error page.
- Order lifecycle foundation.

## Tasks

### 7.1 Cart foundation
- [x] `P7-CART-01` Add add/remove/update items
- [x] `P7-CART-02` Add quantity management
- [x] `P7-CART-03` Add coupon apply/remove
- [x] `P7-CART-04` Add cart totals
- [x] `P7-CART-05` Add shipping estimate foundation
- [x] `P7-CART-06` Add free shipping progress foundation if included
- [x] `P7-CART-07` Add upsell/bundle suggestion foundation

### 7.2 Checkout form flow
- [x] `P7-CHECKOUT-01` Add guest checkout
- [x] `P7-CHECKOUT-02` Add logged-in checkout
- [x] `P7-CHECKOUT-03` Add billing address handling
- [x] `P7-CHECKOUT-04` Add shipping address handling
- [x] `P7-CHECKOUT-05` Add validation
- [x] `P7-CHECKOUT-06` Add address reuse
- [x] `P7-CHECKOUT-07` Add clear error states

### 7.3 Order creation pipeline
- [x] `P7-ORDER-01` Add order snapshot creation
- [x] `P7-ORDER-02` Add line item snapshot
- [x] `P7-ORDER-03` Add address snapshot
- [x] `P7-ORDER-04` Add pricing/tax/discount/shipping summary
- [x] `P7-ORDER-05` Add internal order IDs/reference generation

### 7.4 Account auto-creation on checkout
- [x] `P7-AUTOACC-01` Add secure account generation
- [x] `P7-AUTOACC-02` Add post-checkout access flow
- [x] `P7-AUTOACC-03` Add welcome communication trigger

### 7.5 Thank you page
- [x] `P7-THANKYOU-01` Build order success summary
- [x] `P7-THANKYOU-02` Add next steps
- [x] `P7-THANKYOU-03` Add tracking/order lookup access

### 7.6 Payment error page
- [x] `P7-PAYERR-01` Build clear failure explanation
- [x] `P7-PAYERR-02` Add retry or recovery CTA
- [x] `P7-PAYERR-03` Add order/payment reference if needed

### 7.7 Order statuses and lifecycle
- [x] `P7-STATUS-01` Add Pending
- [x] `P7-STATUS-02` Add Awaiting payment
- [x] `P7-STATUS-03` Add Paid
- [x] `P7-STATUS-04` Add Preparing
- [x] `P7-STATUS-05` Add Shipped
- [x] `P7-STATUS-06` Add Delivered
- [x] `P7-STATUS-07` Add Failed
- [x] `P7-STATUS-08` Add Cancelled
- [x] `P7-STATUS-09` Add Returned/refund-ready states foundation

### 7.8 Order admin UX
- [x] `P7-ADMIN-01` Build orders table
- [x] `P7-ADMIN-02` Add search/filter
- [x] `P7-ADMIN-03` Add status filters
- [x] `P7-ADMIN-04` Add date filters
- [x] `P7-ADMIN-05` Add customer filter
- [x] `P7-ADMIN-06` Add payment/shipping filter
- [x] `P7-ADMIN-07` Build order detail page with timeline
- [x] `P7-ADMIN-08` Add internal notes
- [x] `P7-ADMIN-09` Add customer notes
- [x] `P7-ADMIN-10` Add audit visibility

## Acceptance Criteria
- Checkout works for guest and authenticated users.
- Orders are created correctly.
- Thank-you and error pages exist.
- Order snapshots are reliable.

---

# Phase 8 — Payments & Payment Method Adapters

## Phase ID
`P8`

## Objective
Implement a clean, extensible payments architecture with Moroccan and global payment methods.

## Dependencies
- Requires: Phase 7 foundation
- Recommended: Phase 3 payment settings

## Deliverables
- Payment adapter layer.
- Gateway configuration.
- Offline payment methods.
- Logs and callbacks foundation.

## Tasks

### 8.1 Payment domain foundation
- [x] `P8-DOMAIN-01` Define payment transaction model
- [x] `P8-DOMAIN-02` Define payment status model/fields
- [x] `P8-DOMAIN-03` Map orders and transactions
- [x] `P8-DOMAIN-04` Define gateway abstraction interface

### 8.2 Gateway adapter architecture
- [x] `P8-ADAPTER-01` Define provider contract/interface
- [x] `P8-ADAPTER-02` Define request/response normalization
- [x] `P8-ADAPTER-03` Define error handling conventions
- [x] `P8-ADAPTER-04` Build webhook/callback handling foundation

### 8.3 CMI Morocco integration foundation
- [x] `P8-CMI-01` Add settings fields
- [x] `P8-CMI-02` Add initiation flow
- [x] `P8-CMI-03` Add response handling
- [x] `P8-CMI-04` Add logging

### 8.4 Payzone Morocco integration foundation
- [x] `P8-PAYZONE-01` Add settings fields
- [x] `P8-PAYZONE-02` Add initiation flow
- [x] `P8-PAYZONE-03` Add response handling
- [x] `P8-PAYZONE-04` Add logging

### 8.5 Stripe integration foundation
- [x] `P8-STRIPE-01` Add settings fields
- [x] `P8-STRIPE-02` Add initiation flow
- [x] `P8-STRIPE-03` Add response handling
- [x] `P8-STRIPE-04` Add logging

### 8.6 Offline payment methods
- [x] `P8-OFFLINE-01` Build offline method CRUD
- [x] `P8-OFFLINE-02` Add bank transfer default
- [x] `P8-OFFLINE-03` Add admin instructions
- [x] `P8-OFFLINE-04` Add checkout rendering
- [x] `P8-OFFLINE-05` Add verification workflow foundation

### 8.7 Gateway settings admin
- [x] `P8-GATEWAY-01` Add test/live modes
- [x] `P8-GATEWAY-02` Add enable/disable controls
- [x] `P8-GATEWAY-03` Add secret storage
- [x] `P8-GATEWAY-04` Add validation

### 8.8 Payment logs and audit
- [x] `P8-LOGS-01` Add request/response logging with safe redaction
- [x] `P8-LOGS-02` Add failure logs
- [x] `P8-LOGS-03` Add status change audit

## Acceptance Criteria
- Payment methods are modular.
- Offline payment methods are configurable.
- Logs and gateway settings exist.

---

# Phase 9 — Shipping, Tracking, Fulfillment, and Shipping Roles

## Phase ID
`P9`

## Objective
Build fulfillment and tracking tools for operations teams and customers.

## Dependencies
- Requires: Phase 7
- Recommended: Phase 3 shipping settings

## Deliverables
- Shipping workflow.
- Tracking support.
- Shipping queues.
- Shipping agent workspace.

## Tasks

### 9.1 Shipping data model
- [x] `P9-MODEL-01` Define shipping methods
- [x] `P9-MODEL-02` Define shipment records or equivalent
- [x] `P9-MODEL-03` Add tracking number
- [x] `P9-MODEL-04` Add carrier/service fields
- [x] `P9-MODEL-05` Add fulfillment status fields

### 9.2 Shipping admin settings
- [x] `P9-SETTINGS-01` Add shipping methods management
- [x] `P9-SETTINGS-02` Add shipping defaults
- [x] `P9-SETTINGS-03` Add carrier metadata foundation

### 9.3 Fulfillment workflow
- [x] `P9-FLOW-01` Add Ready to ship state
- [x] `P9-FLOW-02` Add Packed/prepared if needed
- [x] `P9-FLOW-03` Add Dispatched state
- [x] `P9-FLOW-04` Add Delivered state
- [x] `P9-FLOW-05` Add Failed delivery state
- [x] `P9-FLOW-06` Add Returned parcel state

### 9.4 Shipping agent tools
- [x] `P9-AGENT-01` Build ready-to-ship queue
- [x] `P9-AGENT-02` Add tracking entry/update
- [x] `P9-AGENT-03` Add status update actions
- [x] `P9-AGENT-04` Add delivery issue flags

### 9.5 Customer tracking visibility
- [x] `P9-CUSTOMER-01` Show tracking number in account
- [x] `P9-CUSTOMER-02` Show order detail shipping timeline

### 9.6 Shipping audit and reporting
- [x] `P9-REPORT-01` Add shipment history
- [x] `P9-REPORT-02` Add status changes log
- [x] `P9-REPORT-03` Add failed/returned queues

## Acceptance Criteria
- Tracking numbers can be stored and shown.
- Shipping agents have an adapted workspace.
- Fulfillment states are usable and visible.

---

# Phase 10 — Notifications, Email, SMS, WhatsApp, and Messaging Center

## Phase ID
`P10`

## Objective
Build an event-driven notification system for operational and marketing communications.

## Dependencies
- Requires: Phases 3, 7, 8, 9
- Supports: Support, Marketing, Customer experience

## Deliverables
- Notification event system.
- Channel templates.
- Logs/history.
- Queue-based sending.

## Tasks

### 10.1 Notification architecture
- [x] `P10-ARCH-01` Define notification event mapping
- [x] `P10-ARCH-02` Define channel abstraction
- [x] `P10-ARCH-03` Define template variable system
- [x] `P10-ARCH-04` Define queue job structure

### 10.2 Channels
- [x] `P10-CHANNEL-01` Add Email channel
- [x] `P10-CHANNEL-02` Add SMS channel
- [x] `P10-CHANNEL-03` Add WhatsApp channel

### 10.3 Trigger events
- [x] `P10-TRIGGER-01` Add Order placed trigger
- [x] `P10-TRIGGER-02` Add Payment success trigger
- [x] `P10-TRIGGER-03` Add Payment failed trigger
- [x] `P10-TRIGGER-04` Add Shipped trigger
- [x] `P10-TRIGGER-05` Add Delivered trigger
- [x] `P10-TRIGGER-06` Add Cancelled trigger
- [x] `P10-TRIGGER-07` Add Welcome trigger
- [x] `P10-TRIGGER-08` Add Password setup/login link trigger
- [x] `P10-TRIGGER-09` Add Abandoned cart foundation trigger
- [x] `P10-TRIGGER-10` Add Promotional offers foundation trigger

### 10.4 Templates management
- [x] `P10-TEMPLATE-01` Build template CRUD/listing
- [x] `P10-TEMPLATE-02` Add channel-specific content
- [x] `P10-TEMPLATE-03` Add placeholder preview
- [x] `P10-TEMPLATE-04` Add enable/disable per template/channel

### 10.5 Logs and observability
- [x] `P10-LOGS-01` Add sent/failed history
- [x] `P10-LOGS-02` Add retry foundation
- [x] `P10-LOGS-03` Add error reason capture
- [x] `P10-LOGS-04` Add search/filter for logs

### 10.6 Integration settings
- [x] `P10-INTEGRATION-01` Add SMTP config
- [x] `P10-INTEGRATION-02` Add Twilio config
- [x] `P10-INTEGRATION-03` Add WhatsApp API config foundation

## Acceptance Criteria
- Notifications are queued.
- Templates are manageable.
- Logs exist.
- Core order/payment/shipping messages can be triggered.

---

# Phase 11 — Coupons, Promotions, and Abandoned Cart Foundations

## Phase ID
`P11`

## Objective
Build a robust promotions engine for ecommerce growth and retention.

## Dependencies
- Requires: Phase 7
- Recommended: Phase 10 for recovery messaging

## Deliverables
- Coupon management.
- Rule-based validation.
- Basic promo reporting.
- Abandoned cart data capture foundation.

## Tasks

### 11.1 Coupon data model
- [x] `P11-MODEL-01` Add code uniqueness
- [x] `P11-MODEL-02` Add fixed/percentage types
- [x] `P11-MODEL-03` Add active/inactive
- [x] `P11-MODEL-04` Add start/end dates
- [x] `P11-MODEL-05` Add usage limits
- [x] `P11-MODEL-06` Add per-user limits
- [x] `P11-MODEL-07` Add minimum cart
- [x] `P11-MODEL-08` Add product/category targeting
- [x] `P11-MODEL-09` Add exclusion rules
- [x] `P11-MODEL-10` Add stackability foundation

### 11.2 Coupon admin UX
- [x] `P11-ADMIN-01` Build create/edit/list/search/filter
- [x] `P11-ADMIN-02` Add usage counters
- [x] `P11-ADMIN-03` Add status visibility

### 11.3 Coupon validation engine
- [x] `P11-VALIDATE-01` Add apply/remove logic
- [x] `P11-VALIDATE-02` Add eligibility rules
- [x] `P11-VALIDATE-03` Add error messages
- [x] `P11-VALIDATE-04` Add order snapshot of discount usage

### 11.4 Promo reporting foundation
- [x] `P11-REPORT-01` Add coupon usage counts
- [x] `P11-REPORT-02` Add revenue impact foundation

### 11.5 Abandoned cart foundation
- [x] `P11-ABANDON-01` Define cart capture rules
- [x] `P11-ABANDON-02` Add trigger-ready state
- [x] `P11-ABANDON-03` Add recovery message hooks

## Acceptance Criteria
- Coupons are flexible and validated correctly.
- Coupon usage is trackable.
- Abandoned cart foundation exists.

---

# Phase 12 — CMS, Blog, SEO, Redirects, and Content Tools

## Phase ID
`P12`

## Objective
Build a robust content and SEO layer to support organic traffic, educational content, and campaign landing pages.

## Dependencies
- Requires: Phase 3
- Recommended: Phase 4 for cross-entity SEO

## Deliverables
- Blog/CMS.
- SEO metadata system.
- Redirect-friendly architecture.
- Content management UX.

## Tasks

### 12.1 Blog categories and tags
- [x] `P12-TAX-01` Build category CRUD
- [x] `P12-TAX-02` Build tag CRUD
- [x] `P12-TAX-03` Add slugs
- [x] `P12-TAX-04` Add status/ordering if needed

### 12.2 Article/post management
- [x] `P12-POST-01` Add title
- [x] `P12-POST-02` Add slug
- [x] `P12-POST-03` Add author
- [x] `P12-POST-04` Add featured image
- [x] `P12-POST-05` Add content body
- [x] `P12-POST-06` Add draft/published status
- [x] `P12-POST-07` Add scheduling foundation
- [x] `P12-POST-08` Add search/filter

### 12.3 Article SEO fields
- [x] `P12-SEO-01` Add meta title
- [x] `P12-SEO-02` Add meta description
- [x] `P12-SEO-03` Add canonical
- [x] `P12-SEO-04` Add OG fields if included
- [x] `P12-SEO-05` Add noindex foundation if included

### 12.4 SEO across entities
- [x] `P12-ENTITYSEO-01` Add Product SEO
- [x] `P12-ENTITYSEO-02` Add Category SEO
- [x] `P12-ENTITYSEO-03` Add Blog category SEO
- [x] `P12-ENTITYSEO-04` Add Post SEO

### 12.5 Sitemap and redirect readiness
- [x] `P12-SITEMAP-01` Add sitemap-ready architecture
- [x] `P12-SITEMAP-02` Add redirect-friendly data model or module foundation
- [x] `P12-SITEMAP-03` Add slug update strategy

### 12.6 Content UX
- [x] `P12-UX-01` Add draft/publish workflow
- [x] `P12-UX-02` Add preview if practical
- [x] `P12-UX-03` Add author attribution
- [x] `P12-UX-04` Add media picker integration

## Acceptance Criteria
- Blog content can be created and managed.
- SEO fields exist on core content entities.
- Slug/redirect strategy is considered.

---

# Phase 13 — Finance, Transaction Visibility, Reconciliation Foundations

## Phase ID
`P13`

## Objective
Build finance visibility and reconciliation foundations for operators and finance managers.

## Dependencies
- Requires: Phases 7 and 8
- Supports: Finance dashboard, reporting

## Deliverables
- Finance dashboard support.
- Transaction logs.
- COD reconciliation foundation.
- Refund-ready financial visibility.

## Tasks

### 13.1 Financial data structure
- [x] `P13-DATA-01` Add order financial summary fields
- [x] `P13-DATA-02` Add transaction records
- [x] `P13-DATA-03` Add payment method reporting fields
- [x] `P13-DATA-04` Add currency/base currency handling

### 13.2 Transaction visibility
- [x] `P13-TRANS-01` Build payment transactions list
- [x] `P13-TRANS-02` Add search/filter by method/status/date/order
- [x] `P13-TRANS-03` Link transaction to order

### 13.3 Paid/unpaid reporting
- [x] `P13-REPORT-01` Add order payment state reporting
- [x] `P13-REPORT-02` Add gateway success/failure summary

### 13.4 COD reconciliation foundation
- [x] `P13-COD-01` Add COD collection state fields
- [x] `P13-COD-02` Add COD reconciliation list/report foundation

### 13.5 Refund-ready design
- [x] `P13-REFUND-01` Add refund request/record structure foundation
- [x] `P13-REFUND-02` Add refund totals visibility

### 13.6 Discount/fee reporting
- [x] `P13-FEE-01` Add discount visibility
- [x] `P13-FEE-02` Add payment fee visibility if included

## Acceptance Criteria
- Finance managers can inspect transactions and payment states.
- COD reconciliation foundation exists.
- Base currency logic is preserved.

---

# Phase 14 — Support Tools, Customer Timeline, and Internal Notes

## Phase ID
`P14`

## Objective
Give support staff fast, scoped tools to resolve order, payment, shipping, and customer issues.

## Dependencies
- Requires: Phases 6, 7, 8, 9
- Supports: Support dashboard

## Deliverables
- Customer lookup.
- Order lookup.
- Internal notes.
- Timeline foundation.

## Tasks

### 14.1 Support lookup tools
- [x] `P14-LOOKUP-01` Add search by order ID
- [x] `P14-LOOKUP-02` Add search by email/phone/name where applicable
- [x] `P14-LOOKUP-03` Add fast filters

### 14.2 Customer timeline foundation
- [x] `P14-TIMELINE-01` Add recent orders
- [x] `P14-TIMELINE-02` Add order statuses
- [x] `P14-TIMELINE-03` Add payment/shipping visibility
- [x] `P14-TIMELINE-04` Add support/internal notes

### 14.3 Internal notes
- [x] `P14-NOTES-01` Add order-level notes
- [x] `P14-NOTES-02` Add customer-level notes foundation
- [x] `P14-NOTES-03` Add staff attribution
- [x] `P14-NOTES-04` Add audit visibility

### 14.4 Refund/return issue queue foundation
- [x] `P14-QUEUE-01` Add queue/list for support attention
- [x] `P14-QUEUE-02` Add statuses if implemented

## Acceptance Criteria
- Support agents can quickly find customers and orders.
- Notes and timelines exist in a usable form.

---

# Phase 15 — Reports, Search, Bulk Actions, Imports/Exports

## Phase ID
`P15`

## Objective
Provide operational leverage through reporting, search tools, and admin productivity features.

## Dependencies
- Requires: core commerce modules complete
- Recommended: dashboard and finance foundations available

## Deliverables
- Basic report suite.
- Cross-module search where practical.
- Bulk actions.
- Export/import foundations.

## Tasks

### 15.1 Reports foundation
- [x] `P15-REPORT-01` Add sales summary report
- [x] `P15-REPORT-02` Add orders report
- [x] `P15-REPORT-03` Add inventory report
- [x] `P15-REPORT-04` Add coupon usage report
- [x] `P15-REPORT-05` Add finance summary report

### 15.2 Search and filter enhancements
- [x] `P15-SEARCH-01` Build reusable filter builders
- [x] `P15-SEARCH-02` Add search patterns across admin tables
- [x] `P15-SEARCH-03` Add saved views foundation if practical

### 15.3 Bulk actions
- [x] `P15-BULK-01` Add products bulk actions
- [x] `P15-BULK-02` Add orders bulk actions where safe
- [x] `P15-BULK-03` Add content bulk actions

### 15.4 Export/import foundation
- [x] `P15-EXPORT-01` Add CSV/XLS export for key modules
- [x] `P15-EXPORT-02` Add safe import design for catalog if time allows

## Acceptance Criteria
- Staff can operate large datasets more efficiently.
- Reports and exports exist for core modules.

---

# Phase 16 — Audit Logs, Security Hardening, and System Observability

## Phase ID
`P16`

## Objective
Add the operational trust and traceability needed for a real commerce platform.

## Dependencies
- Requires: core commerce and IAM modules complete
- Supports: production readiness

## Deliverables
- Audit logs.
- Security event logging.
- Sensitive action history.
- Basic observability.

## Tasks

### 16.1 Audit log foundation
- [x] `P16-AUDIT-01` Define audit model/store
- [x] `P16-AUDIT-02` Add sensitive action hooks
- [x] `P16-AUDIT-03` Add actor, target, action, context logging

### 16.2 Sensitive events to audit
- [x] `P16-EVENT-01` Audit role changes
- [x] `P16-EVENT-02` Audit staff creation/deactivation
- [x] `P16-EVENT-03` Audit product price changes
- [x] `P16-EVENT-04` Audit order status overrides
- [x] `P16-EVENT-05` Audit refund updates
- [x] `P16-EVENT-06` Audit stock adjustments
- [x] `P16-EVENT-07` Audit payment configuration changes
- [x] `P16-EVENT-08` Audit impersonation

### 16.3 Security hardening
- [x] `P16-SEC-01` Add forced 2FA if enabled with qr code 
- [x] `P16-SEC-02` Review session security
- [x] `P16-SEC-03` Add rate limiting where appropriate
- [x] `P16-SEC-04` Add safe logging/redaction rules

### 16.4 Basic observability
- [x] `P16-OBS-01` Add notification failures visibility
- [x] `P16-OBS-02` Add payment callback errors visibility
- [x] `P16-OBS-03` Add queue visibility foundation
- [x] `P16-OBS-04` Add slow-operation logging foundation

## Acceptance Criteria
- Sensitive changes are traceable.
- Security posture is materially stronger.

---

# Phase 17 — Community Schema & Boundaries (No Full UI Yet)

## Phase ID
`P17`

## Objective
Prepare the platform for community without allowing it to destabilize commerce delivery.

## Dependencies
- Requires: customer identity and core commerce stable
- Does not block: release of commerce platform

## Deliverables
- Community domain boundaries.
- Schema-ready models/migrations.
- Role and moderation placeholders.

## Not in Scope
- Full community UI
- Real-time chat
- Full video moderation pipeline
- Complex social feed ranking

## Tasks

### 17.1 Community domain design
- [x] `P17-DOMAIN-01` Define Group model
- [x] `P17-DOMAIN-02` Define Default group foundation
- [x] `P17-DOMAIN-03` Define Membership model
- [x] `P17-DOMAIN-04` Define Join request structure if needed
- [x] `P17-DOMAIN-05` Define Post model
- [x] `P17-DOMAIN-06` Define Media attachment structure
- [x] `P17-DOMAIN-07` Define Comment model
- [x] `P17-DOMAIN-08` Define Reaction model
- [x] `P17-DOMAIN-09` Define Report model

### 17.2 Moderation foundation
- [x] `P17-MOD-01` Add Community moderator role hooks
- [x] `P17-MOD-02` Add moderation queue schema
- [x] `P17-MOD-03` Add report states

### 17.3 Customer onboarding connection
- [x] `P17-ONBOARD-01` Add default group invitation flag/foundation for new customers

### 17.4 Storage/performance planning
- [x] `P17-PERF-01` Define media handling rules
- [x] `P17-PERF-02` Define video scalability considerations
- [x] `P17-PERF-03` Add quota/moderation notes in docs

## Acceptance Criteria
- Community schema is prepared.
- Commerce remains decoupled from unfinished community UI.

---

# Phase 18 — Final Stabilization, QA, Testing, and Release Readiness

## Phase ID
`P18`

## Objective
Harden the system for real use and make sure phase 1+2 core commerce capabilities are stable.

## Dependencies
- Requires: core target phases complete
- Supports: release readiness

## Deliverables
- Tested core flows.
- QA pass.
- Docs updated.
- Performance pass.

## Tasks

### 18.1 Testing priorities
- [x] `P18-TEST-01` Add auth and permission tests
- [x] `P18-TEST-02` Add checkout flow tests
- [x] `P18-TEST-03` Add inventory stock/reservation tests
- [x] `P18-TEST-04` Add payment adapter tests/mocks
- [x] `P18-TEST-05` Add order lifecycle tests
- [x] `P18-TEST-06` Add notification trigger tests

### 18.2 QA walkthroughs
- [x] `P18-QA-01` Run Super Admin flow QA
- [x] `P18-QA-02` Run Support flow QA
- [x] `P18-QA-03` Run Shipping flow QA
- [x] `P18-QA-04` Run Finance flow QA
- [x] `P18-QA-05` Run Customer checkout/account flow QA

### 18.3 Performance review
- [x] `P18-PERF-01` Review high-frequency queries
- [x] `P18-PERF-02` Review indexes
- [x] `P18-PERF-03` Review cache usage
- [x] `P18-PERF-04` Review queue usage

### 18.4 Documentation review
- [x] `P18-DOCS-01` Update `PROJECT_PROGRESS.md`
- [x] `P18-DOCS-02` Update `ARCHITECTURE.md`
- [x] `P18-DOCS-03` Update `PHASES.md`
- [x] `P18-DOCS-04` Confirm `phases-tasks.md` still matches scope

### 18.5 Release readiness
- [x] `P18-RELEASE-01` Seed demo/admin data if useful
- [x] `P18-RELEASE-02` Confirm migrations are clean
- [x] `P18-RELEASE-03` Confirm environment variable list is clear
- [x] `P18-RELEASE-04` Confirm fallback/error states are acceptable

## Acceptance Criteria
- Core commerce/admin operations are stable.
- Critical flows are tested.
- Documentation is current.

---

# Cross-Phase AI Execution Checklist

Before starting any phase or module, the AI must:
1. Check `READ_FIRST.md`.
2. Check `PHASES.md`.
3. Check `PROJECT_PROGRESS.md`.
4. Confirm current phase and next module/task IDs.
5. Read relevant sections of `ARCHITECTURE.md` if needed.
6. Read relevant sections of this file for the active task IDs only.
7. Avoid jumping ahead unless a dependency requires it.
8. Keep docs concise.
9. Update progress after meaningful work.

# Phase Dependencies Summary
- Phase 0 is required before everything.
- Phase 1 is required before all scoped admin features.
- Phase 3 settings foundation should be early because other modules depend on it.
- Catalog and Inventory should be stable before Checkout/Orders are finalized.
- Payments and Shipping must be connected to Orders, not built in isolation.
- Notifications depend on Orders, Payments, Shipping, and Customers.
- Finance depends on Orders and Payments.
- Support depends on Customers and Orders.
- Community must not delay core commerce.

# Definition of Done for Any Module
A module is only considered complete when:
- Data model is clean.
- Admin UI exists and respects permissions.
- Validation exists.
- Policies/authorization exist.
- Audit logging exists where needed.
- Basic tests exist for critical flows.
- Performance risks are reviewed.
- `PROJECT_PROGRESS.md` is updated.
