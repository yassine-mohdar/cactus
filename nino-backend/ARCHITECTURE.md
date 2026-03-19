# ARCHITECTURE.md

## 1. Overview

NinoWorld backend is a **modular monolith ecommerce operating system** built for:
- platform administration
- franchise and branch operations
- customer accounts
- shipping and fulfillment
- finance and reporting
- CMS / SEO
- notifications and marketing operations
- future community features

The architecture must prioritize:
- clean domain boundaries
- high maintainability
- performance
- security
- extensibility
- low operational complexity

This system is intentionally **not** designed as microservices at the current stage.
It should remain a modular monolith with modules that could later be extracted if justified.

---

## 2. Core Architectural Principles

### 2.1 Modular Monolith
The codebase is organized by domain/module, not by controller/model type.

Example direction:
- Modules/IAM
- Modules/Organizations
- Modules/Catalog
- Modules/Inventory
- Modules/Orders
- Modules/Payments
- Modules/Shipping
- Modules/Customers
- Modules/Notifications
- Modules/Coupons
- Modules/CMS
- Modules/Finance
- Modules/Reports
- Modules/Settings
- Modules/Support
- Modules/Community

### 2.2 Thin Controllers
Controllers should only:
- receive requests
- authorize actions
- validate input
- dispatch to services/actions
- return responses/views/resources

Business logic must live in:
- services
- actions
- domain classes
- jobs
- policies
- listeners

### 2.3 Scoped Authorization
Authorization must combine:
- role presets
- permissions
- scope-based access

Scopes include:
- platform
- franchise
- branch
- own records only

### 2.4 Event-Driven Side Effects
Use events/listeners/jobs for:
- notifications
- abandoned cart workflows
- payment callbacks
- order state transitions
- shipping status changes
- media processing
- audit logging where applicable

### 2.5 Performance by Default
The system must assume:
- admin tables can grow large
- order volume can grow
- notifications are async
- media should be optimized
- major tables require indexing
- caching is used where safe

---

## 3. Recommended Stack

- Laravel 12
- PHP 8.3+
- MySQL 8+
- Redis for cache and queues
- Tailwind CSS + Livewire or robust Blade-based admin
- Sanctum / Fortify as needed
- Pest for tests
- Storage abstraction for local/S3-compatible media

---

## 4. Application Surfaces

### 4.1 Admin / Ops Surface
Used by:
- super admin
- platform admin
- franchise manager
- branch manager
- support
- finance
- shipping
- stock
- SEO/content
- marketing/media buying
- moderators later

Characteristics:
- dense UI
- fast tables
- strong filters
- no playful UI
- role-aware navigation

### 4.2 Customer Account Surface
Used by customers for:
- orders
- tracking
- addresses
- profile
- account setup/password changes

### 4.3 Storefront / Checkout Surface
Commerce surface for:
- cart
- checkout
- payment
- thank you / error pages

### 4.4 Community Surface
Phase 3 target:
- groups
- posts
- media
- comments
- moderation

---

## 5. Domain Boundaries

## 5.1 IAM
Responsibilities:
- users
- roles
- permissions
- scopes
- login/session/security foundation
- policies
- impersonation with logging if implemented

Does not own:
- business logic of orders/products/etc.

## 5.2 Organizations
Responsibilities:
- platform
- franchise
- branch hierarchy
- staff association to operational scope

## 5.3 Catalog
Responsibilities:
- categories
- products
- variants
- slugs
- content basics on products
- product SEO metadata
- product media references

Does not own:
- stock movement
- payment logic
- shipping execution

## 5.4 Inventory
Responsibilities:
- stock levels
- stock adjustments
- damaged stock
- low-stock alerts
- variant stock
- branch-aware stock
- transfer-ready architecture
- stock audit trail

## 5.5 Orders
Responsibilities:
- order creation
- order item snapshots
- address snapshots
- status lifecycle
- order timeline
- order search/filtering
- internal notes
- customer notes

## 5.6 Checkout
Responsibilities:
- cart to order conversion
- checkout validation
- guest checkout
- customer account auto-creation workflow
- thank you / error flow

Does not own:
- gateway-specific payment internals

## 5.7 Payments
Responsibilities:
- payment method abstraction
- provider adapters
- gateway configuration
- transaction logs
- webhook/callback handling
- payment status synchronization

Providers to support:
- CMI Morocco
- Payzone Morocco
- Stripe
- offline methods
- bank transfer

## 5.8 Shipping
Responsibilities:
- shipping methods
- tracking numbers
- fulfillment status
- dispatch state
- failed delivery / returns state
- shipping-agent workflows

## 5.9 Customers
Responsibilities:
- customer profile
- addresses
- preferences if later added
- customer account management
- account onboarding security

Important rule:
Never send plain passwords by email/SMS/WhatsApp.

## 5.10 Notifications
Responsibilities:
- email
- SMS
- WhatsApp
- templates
- triggers
- logs
- queued sending
- retries foundation

## 5.11 Coupons / Promotions
Responsibilities:
- coupon definitions
- discount rules
- targeting
- usage limits
- validity windows
- stackability rules foundation

## 5.12 CMS / SEO
Responsibilities:
- blog categories
- tags
- posts/articles
- SEO metadata
- canonical/noindex foundations
- OG fields
- slug management
- SEO for products/categories/posts

## 5.13 Finance
Responsibilities:
- transaction visibility
- paid/unpaid reporting
- refund-ready design
- COD reconciliation foundation
- fee/discount reporting
- base currency governance

## 5.14 Reports / Analytics
Responsibilities:
- role-specific reporting widgets
- KPIs
- operational visibility
- future attribution hooks

## 5.15 Settings / Integrations
Responsibilities:
- global settings
- company info
- integrations
- payment settings
- notification settings
- security settings
- SEO settings
- system settings

## 5.16 Support
Responsibilities:
- fast order/customer lookup
- customer timeline foundation
- internal notes
- support tooling

## 5.17 Community
Phase 3 responsibilities:
- groups
- posts
- media
- comments
- likes/reactions
- reporting
- moderation

---

## 6. Cross-Cutting Concerns

### 6.1 Audit Logging
Must exist for sensitive actions such as:
- role changes
- product price changes
- refunds
- order status overrides
- settings changes
- stock adjustments

### 6.2 Search / Filters / Pagination
All operational tables should support:
- pagination
- filters
- sorting
- scoped visibility

### 6.3 Queues
Use queues for:
- notifications
- webhook processing
- heavy media tasks
- long-running imports/exports later

### 6.4 Caching
Cache where safe:
- settings
- frequently read reference data
- some catalog data if appropriate

### 6.5 Validation
All writes must be validated with:
- request classes
- domain-specific validation
- permission checks
- business rule checks

### 6.6 Testing
Priority coverage:
- checkout
- payment callbacks
- stock adjustments/reservations
- order creation
- permissions/policies
- customer onboarding security

---

## 7. Role Model

Primary role presets:
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

Use role presets + permissions + scope.
Avoid relying only on hardcoded role checks.

---

## 8. UI / Admin Shell Principles

The admin panel must be:
- professional
- dense
- fast
- predictable
- role-aware
- easy to scan

UI rules:
- Nunito typography
- cream/sage/muted neutral palette
- no playful admin styling
- compact tables
- strong filters
- grouped forms
- tabs for complex entities
- bulk actions where relevant

---

## 9. Security Principles

- no plain password delivery
- secure credential storage
- 2FA-capable architecture for staff
- policy-based authorization
- logged impersonation if enabled
- audit logs for sensitive changes
- session/security visibility later if practical

---

## 10. Phasing Summary

### Phase 1
Core commerce and operations

### Phase 2
Advanced operations, finance depth, growth tooling

### Phase 3
Community and richer social/customer engagement

Community must not block revenue-critical delivery.

---

## 11. Immediate Build Order

1. Bootstrap Laravel project
2. Create module structure
3. Create docs:
   - PROJECT_PROGRESS.md
   - ARCHITECTURE.md
   - PHASES.md
4. Implement auth + IAM
5. Implement admin shell + role-aware navigation
6. Implement settings foundation
7. Implement catalog
8. Implement inventory
9. Implement orders
10. Implement checkout and payments foundation
11. Implement customers and addresses
12. Implement shipping
13. Implement notifications
14. Implement coupons
15. Implement CMS / SEO
16. Implement finance/reporting basics
17. Refine tests, performance, and audit coverage
