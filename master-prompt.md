
You are a Senior Software Architect, Lead Engineer, Product Manager, and Technical Project Manager.

Your mission is to build `nino-backend`, a robust, high-performance, modular monolith ecommerce operating system for NinoWorld.

We already have a landing page in:
- `nino-world-Landing-page`

You must build the backend/admin/customer/ops platform from scratch in:
- `nino-backend`

==================================================
1. ARCHITECTURAL STANDARD (NON-NEGOTIABLE)
==================================================

- Pattern: Modular Monolith
- Organization: Group by Domain, not by technical type
- Layers: Thin Controllers, business logic in Services / Actions
- Use Laravel 12 and PHP 8.3+ features
- No fat controllers
- No scattered permission logic
- No N+1 queries
- Strong type hinting
- Clean, scalable module boundaries so modules could later be extracted if needed

==================================================
2. PROFESSIONAL ADMIN UI/UX STANDARD
==================================================

The backend must be professional, fast, dense, utilitarian, and efficient for staff.

Do NOT make it playful.

UI direction:
- Background: cream / soft neutral
- Primary actions: sage green
- Muted dividers and soft status surfaces
- Typography: Nunito only
- No decorative handwriting fonts
- No spring animations
- No slow transitions
- High data density
- Compact tables
- Strong filters
- Clear status badges
- Role-adapted menus
- Fast and snappy experience

==================================================
3. TECH STACK
==================================================

- Laravel 12
- PHP 8.3+
- MySQL 8+
- Redis for cache and queues
- Tailwind CSS + Livewire or robust Blade-based admin stack
- Laravel Sanctum / Fortify as needed
- Pest for testing
- Storage abstraction for media
- Adapter pattern for integrations

==================================================
4. PROJECT DOCUMENTS (MANDATORY)
==================================================

Create and maintain these files:

- `nino-backend/PROJECT_PROGRESS.md`
- `nino-backend/ARCHITECTURE.md`
- `nino-backend/PHASES.md`

`PROJECT_PROGRESS.md` must stay concise and low-token. It must track:
- Current phase
- Completed
- In progress
- Next tasks
- Blockers / decisions
- Important setup notes

Update it after every meaningful implementation batch.
==================================================
4B. EXECUTION FILE WORKFLOW (MANDATORY)
==================================================

The project uses these planning and tracking files:

- `nino-backend/READ_FIRST.md`
- `nino-backend/PHASES.md`
- `nino-backend/PROJECT_PROGRESS.md`
- `nino-backend/ARCHITECTURE.md`
- `nino-backend/phases-tasks.md`

Purpose of each file:
- `READ_FIRST.md` = AI operating instructions and file read order
- `PHASES.md` = current execution state, completed tasks, in-progress tasks, next tasks, blockers
- `PROJECT_PROGRESS.md` = concise latest implementation batch log
- `ARCHITECTURE.md` = stable architecture blueprint and domain boundaries
- `phases-tasks.md` = full master task plan and detailed implementation roadmap

Mandatory file read order at the start of every session:
1. `READ_FIRST.md`
2. `PHASES.md`
3. `PROJECT_PROGRESS.md`
4. `ARCHITECTURE.md` only if working on architecture-sensitive changes
5. `phases-tasks.md` only for the relevant current phase/task being implemented

Token-efficiency rule:
- Do NOT re-read the full `phases-tasks.md` on every turn if the current task is already clear from `PHASES.md` and `PROJECT_PROGRESS.md`.
- Use `PHASES.md` as the primary execution checkpoint file.

Status tracking rules:
- `PHASES.md` should use concise task IDs and statuses where possible.
- Supported statuses:
  - TODO
  - IN_PROGRESS
  - DONE
  - BLOCKED
  - DEFERRED

Update rules after each meaningful implementation batch:
- update `PHASES.md`
- update `PROJECT_PROGRESS.md`
- keep both concise
- clearly state completed tasks, next tasks, and blockers
- do not rewrite architecture docs unless needed

Implementation discipline:
- work in small batches
- complete one module or submodule at a time
- do not jump across unrelated modules unless required by dependency
- use the task plan in `phases-tasks.md` as the source of truth for scope
==================================================
5. DOMAIN MODULES
==================================================

Create modules/domains for:

- IAM / Roles / Permissions
- Organizations
- Catalog
- Inventory
- Orders
- Checkout
- Payments
- Shipping
- Customers
- Notifications
- Coupons / Promotions
- CMS / Blog / SEO
- Finance
- Reports / Analytics
- Settings / Integrations
- Audit / Security / Logs
- Support
- Community (schema + boundaries first, full UI later)

==================================================
6. PHASED DELIVERY
==================================================

PHASE 1 = CORE COMMERCE + OPERATIONS
Must include:
- auth
- roles & permissions
- role-adapted dashboards
- settings foundation
- categories CRUD
- products CRUD
- product media
- inventory basics
- orders CRUD
- checkout flow
- payment adapters foundation
- customer accounts
- addresses
- shipping/tracking basics
- notifications foundation
- coupon basics
- blog/CMS basic
- SEO basic
- audit logs
- support lookup tools
- basic reports

PHASE 2 = ADVANCED OPERATIONS + GROWTH
- finance reconciliation
- COD reconciliation
- advanced coupons
- advanced notifications
- marketing integrations
- abandoned cart logs/workflows
- richer SEO
- bulk actions
- imports/exports
- advanced reports

PHASE 3 = COMMUNITY
- groups
- default groups
- posts
- photo/video/text
- comments
- likes/reactions
- reporting
- moderation
- community moderator flows

Community must NOT delay phase 1.

==================================================
7. ROLES & PERMISSIONS
==================================================

Implement a robust, advanced, user-friendly RBAC system with scope-based access.

Role presets:
- Super Admin
- Platform Admin
- Franchise Manager
- Branch Manager
- Customer Support Agent
- Shipping Agent
- Employee
- Finance Manager
- SEO / Content Manager
- Media Buying / Marketing Manager
- Sales Manager
- Stock Manager
- Community Moderator

Requirements:
- role presets
- custom roles
- permission overrides
- scoped permissions (platform / franchise / branch / own)
- policies for authorization
- audit of role changes
- impersonation with logging
- session/security visibility if practical

==================================================
8. DASHBOARDS BY ROLE
==================================================

Each role must have an adapted dashboard.

Super Admin:
- GMV
- orders today
- revenue trend
- failed payments
- stock alerts
- shipping exceptions
- franchise and branch performance
- support KPIs
- finance overview
- system alerts

Franchise:
- branch sales
- branch stock
- staff performance
- branch order metrics

Branch:
- orders to prepare
- stock widgets
- dispatch queue
- issue queue

Support:
- order lookup
- customer lookup
- recent orders
- payment/shipping visibility
- refund/return cases
- internal notes

Shipping:
- ready to ship
- shipped
- failed delivery
- returned parcels
- tracking queue

Finance:
- settlements
- paid/unpaid
- gateway logs
- COD reconciliation
- refunds
- fee and discount visibility

SEO / Content:
- posts
- drafts
- missing metadata
- SEO tasks

Media Buying:
- integration health
- campaign/coupon hooks
- attribution-ready widgets if implemented

Sales:
- revenue
- best sellers
- AOV
- promo performance

Stock:
- low stock
- stock adjustments
- damaged stock
- aging stock
- transfer-ready data

Community Moderator:
- flagged content
- reported posts
- pending group requests

==================================================
9. CATALOG
==================================================

Categories CRUD:
- nested categories
- image
- slug
- description
- SEO fields
- active/inactive
- ordering

Products CRUD:
- simple and variable products
- SKU
- slug
- gallery/images
- short/full description
- price / sale price
- internal cost if needed
- stock settings
- categories
- tags
- draft/published/archived
- SEO fields
- dimensions/weight
- featured flag
- upsell/cross-sell relations
- low stock threshold
- audit trail

==================================================
10. INVENTORY
==================================================

Inventory must be stronger than basic WooCommerce inventory.

Required:
- stock per product
- variant stock
- stock adjustments
- stock logs/audit
- low stock alerts
- damaged stock
- branch-aware stock architecture
- transfer-ready design
- reservation/release-ready design around checkout/payment

==================================================
11. ORDERS
==================================================

Orders must include:
- lifecycle statuses
- order timeline
- customer snapshot
- address snapshot
- payment method snapshot
- line item snapshot
- shipping/tax/discount totals
- internal notes
- customer notes
- tracking number
- payment status
- fulfillment status
- cancellation support
- refund-ready architecture
- search/filter/pagination

==================================================
12. CHECKOUT
==================================================

Build a smart cart and smart checkout.

Cart:
- coupon apply
- upsell foundation
- bundle suggestion foundation
- free shipping progress if practical

Checkout:
- guest checkout
- auto-create account after checkout
- never send plain password
- use set-password link / magic link / OTP
- billing + shipping address support
- thank you page
- payment error page
- strong validation and logging

==================================================
13. PAYMENTS
==================================================

Use adapter/provider architecture.

Support:
- CMI Morocco
- Payzone Morocco
- Stripe
- offline payment methods
- bank transfer by default
- super admin can manage offline methods

Payment settings:
- credentials
- test/live mode
- activation/deactivation
- instructions
- webhook/callback foundation
- logs

==================================================
14. SHIPPING
==================================================

Shipping must include:
- shipping status flow
- tracking number
- shipping method management
- ready-to-ship queue
- dispatched / delivered / failed / returned states
- shipping agent workflows
- customer-visible tracking in account

==================================================
15. CUSTOMER ACCOUNT
==================================================

Customer account must include:
- account overview
- order history
- order details
- order tracking
- profile edit
- profile picture
- first name
- last name
- email
- username
- password change
- addresses tab
- billing addresses
- shipping addresses
- add/edit/delete
- primary/default address

==================================================
16. NOTIFICATIONS / MESSAGING
==================================================

Build an event-driven notification center for:
- email
- SMS
- WhatsApp

Events:
- order placed
- payment success
- payment failed
- shipped
- delivered
- cancelled
- abandoned cart
- welcome
- password setup / magic link
- promotional offers

Admin features:
- template management
- placeholders
- trigger mapping
- logs/history
- enable/disable per channel
- queued sending
- preview
- retry foundation

Integrations:
- SMTP
- Twilio SMS
- WhatsApp API foundation

==================================================
17. COUPONS / PROMOTIONS
==================================================

Build an advanced coupon system with:
- fixed or percentage discounts
- active/inactive
- start/end dates
- usage limits
- per-user limits
- minimum cart
- product/category targeting
- exclusions
- uniqueness
- stackability foundation
- reporting hooks

==================================================
18. SETTINGS
==================================================

Create robust settings with tabs:

- General
- Connections
- SEO
- Security
- Finance
- Payment Methods
- Notifications
- Shipping
- Media / System / Maintenance / Feature Flags

Include:
General:
- website info
- company info
- branding

Connections:
- SMTP
- SMS
- WhatsApp API

SEO:
- analytics
- Google verification
- Facebook Pixel
- TikTok Pixel
- SEO defaults

Security:
- force 2FA for staff/admin
- security settings foundation

Finance:
- base currency
- multi-currency foundation
- conversion adjustment settings

Payment Methods:
- manage CMI
- Payzone
- Stripe
- offline methods
- bank transfer default

==================================================
19. CMS / BLOG / SEO
==================================================

Blog/CMS:
- categories
- tags
- articles
- featured image
- slug
- author
- draft/published
- scheduling if practical
- SEO fields

SEO:
- meta title
- meta description
- canonical
- OG fields if possible
- noindex if possible
- sitemap-ready architecture
- redirect-friendly architecture
- SEO for products/categories/posts

==================================================
20. FINANCE
==================================================

Finance module should include:
- transaction logs
- payment method visibility
- paid/unpaid reporting
- refund-ready design
- COD reconciliation foundation
- fee reporting
- discount reporting
- one accounting base currency

==================================================
21. SUPPORT TOOLS
==================================================

Support tools must include:
- fast order lookup
- fast customer lookup
- timeline foundation
- recent orders
- shipping/payment visibility
- internal notes

==================================================
22. COMMUNITY
==================================================

Prepare schema and boundaries for:
- groups
- default groups
- join flow
- posts
- text/photo/video
- comments
- likes/reactions
- reporting
- moderation

Do not fully build community UI before phase 1 is clean.

==================================================
23. PERFORMANCE / SECURITY / QUALITY
==================================================

Non-negotiable:
- queues for async work
- pagination everywhere
- caching where safe
- DB indexes
- audit logs for sensitive actions
- validation everywhere
- secure credential storage
- no plain password sending
- error handling
- clean migrations
- factories/seeders
- tests for critical flows
- reusable filters/search utilities
- optimized media handling

==================================================
24. ADMIN UX
==================================================

Admin must be:
- clean
- fast
- modern
- professional
- easy to navigate
- scalable

Must include:
- search
- filters
- bulk actions where relevant
- clear tables
- grouped forms
- tabs for complex entities
- role-adapted menus

==================================================
25. FIRST STEPS
==================================================

Start now with:
1. create `nino-backend`
2. create and/or validate these files:
   - `READ_FIRST.md`
   - `PHASES.md`
   - `PROJECT_PROGRESS.md`
   - `ARCHITECTURE.md`
   - `phases-tasks.md`
3. read files in the required order
4. bootstrap Laravel 12 project
5. create module/domain structure
6. configure Tailwind/admin shell with professional NinoWorld palette
7. implement auth + IAM + roles/permissions foundation
8. implement admin shell + role-aware navigation
9. continue phase 1 in strict order based on `PHASES.md` and `phases-tasks.md`

At the end of each meaningful batch:
- update `PHASES.md`
- update `PROJECT_PROGRESS.md`
- keep updates concise
- state completed task IDs
- state next task IDs
- state blockers if any


information for database 

localhost
port : 3306
db user : root
password :
database name : nino-backend

SMTP FOR TESTING GMAIL

SMTP Host: smtp.gmail.com
SMTP Port: 587
Encryption: TLS
Username: mohdar@fleure.ma
Password: vdww hctx lvpa tbpl

project name : NinoWorld 