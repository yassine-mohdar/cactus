# PROJECT_PROGRESS.md

## Last Updated
2026-03-20

## Current Phase
Phase 3 — Settings & System Configuration

## Phase Routing Note
- Phase 2A is intentionally deferred by user instruction.
- Active execution has moved directly to the Phase 3 settings track.

## Last Batch Completed
### Batch 76 — Phase 3 SEO Completion and Security Settings Foundation (2026-03-20)
- Implemented only `P3-SEO-04`, `P3-SEO-05`, `P3-SECURITY-01`, and `P3-SECURITY-02`.
- Completed the SEO settings contract in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php)
- Added a shared password-policy runtime in:
  - [PasswordPolicyService](app/Modules/Settings/Services/PasswordPolicyService.php)
  - [PasswordValidationRules](app/Actions/Fortify/PasswordValidationRules.php)
- Wired the password policy into the active password entry points:
  - [CompleteCustomerPasswordSetupRequest](app/Modules/IAM/Http/Requests/CompleteCustomerPasswordSetupRequest.php)
  - [CompleteStaffAccessSetupRequest](app/Modules/IAM/Http/Requests/CompleteStaffAccessSetupRequest.php)
  - [CustomerPasswordResetController](app/Modules/IAM/Http/Controllers/CustomerPasswordResetController.php)
  - [ProfileController](app/Modules/Customers/Http/Controllers/ProfileController.php)
  - [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php)
  - [AdminCustomerController](app/Modules/Customers/Http/Controllers/AdminCustomerController.php)
- Expanded Phase 3 coverage to prove:
  - TikTok Pixel persistence
  - SEO defaults persistence
  - grouped security settings rendering
  - persistence of `force_2fa` and password-policy settings
  - runtime enforcement of password policy for customer reset and staff access setup
- Updated stale audit test fixtures to satisfy the stricter password policy:
  - [AuditLogTest](tests/Feature/AuditLogTest.php)
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php tests/Feature/SecurityHardeningTest.php`
  - `php artisan test`
  - Result: full suite passing with `144` tests and `1016` assertions
- Exact next task IDs:
  - `P3-SECURITY-03`
  - `P3-SECURITY-04`
  - `P3-FINANCE-01`
  - `P3-FINANCE-02`

### Batch 75 — Phase 3 Safe Connection Testing and SEO Settings Alignment (2026-03-20)
- Implemented only `P3-CONN-04`, `P3-SEO-01`, `P3-SEO-02`, and `P3-SEO-03`.
- Added a safe, non-delivery connection-test utility in:
  - [IntegrationConnectionTestService](app/Modules/Notifications/Services/IntegrationConnectionTestService.php)
  - [IntegrationSettingController](app/Modules/Notifications/Http/Controllers/IntegrationSettingController.php)
  - [routes/web.php](routes/web.php)
- Exposed the safe-test utility in the admin notification/settings surfaces for both themes:
  - [nino-v2 notifications integrations](resources/views/themes/nino-v2/admin/notifications/integrations/index.blade.php)
  - [nino-v1 notifications integrations](resources/views/themes/nino-v1/admin/notifications/integrations/index.blade.php)
  - [nino-v2 settings index](resources/views/themes/nino-v2/admin/settings/index.blade.php)
  - [nino-v1 settings index](resources/views/themes/nino-v1/admin/settings/index.blade.php)
- Aligned SEO settings to the real keys and grouped sections in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
- Extended [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php) to verify:
  - grouped SEO settings rendering
  - persistence of analytics / verification / Facebook pixel fields
  - safe connection-test success and warning flows
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `140` tests and `991` assertions
- Exact next task IDs:
  - `P3-SEO-04`
  - `P3-SEO-05`
  - `P3-SECURITY-01`
  - `P3-SECURITY-02`

### Batch 74 — Phase 3 Locale/Timezone Runtime and Connection Settings Foundation (2026-03-20)
- Implemented only `P3-GENERAL-05`, `P3-CONN-01`, `P3-CONN-02`, and `P3-CONN-03`.
- Added runtime general-settings application in:
  - [ApplyGeneralSettings](app/Http/Middleware/ApplyGeneralSettings.php)
  - [bootstrap/app.php](bootstrap/app.php)
  so locale and timezone now apply from the settings store on web requests.
- Expanded [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php) and [SettingsSeeder](database/seeders/SettingsSeeder.php) to include the `locale` setting in the general group.
- Hardened connection settings by:
  - ensuring default providers exist in the settings-center mail tab
  - adding provider-specific validation in [IntegrationSettingController](app/Modules/Notifications/Http/Controllers/IntegrationSettingController.php)
  - centralizing default provider seeding in [IntegrationSetting](app/Modules/Notifications/Models/IntegrationSetting.php)
- Wired SMTP settings into runtime mail behavior in [EmailChannel](app/Modules/Notifications/Channels/EmailChannel.php), so the stored SMTP credentials now affect the active mail configuration instead of being decorative.
- Extended [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php) to verify:
  - locale/timezone runtime application
  - SMTP/Twilio/WhatsApp rows auto-seed from the settings center
  - provider-specific validation when enabling integrations
  - successful persistence of SMTP/Twilio/WhatsApp settings
  - SMTP runtime config application
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `136` tests and `974` assertions
- Exact next task IDs:
  - `P3-CONN-04`
  - `P3-SEO-01`
  - `P3-SEO-02`
  - `P3-SEO-03`

### Batch 73 — Phase 3 General Settings: Website, Company, Contact, and Branding (2026-03-20)
- Implemented only `P3-GENERAL-01`, `P3-GENERAL-02`, `P3-GENERAL-03`, and `P3-GENERAL-04`.
- Expanded [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php) so the `general` group now includes:
  - website identity fields
  - company information fields
  - contact information fields
  - branding basics fields
- Updated the admin settings UI in:
  - [nino-v2 settings](resources/views/themes/nino-v2/admin/settings/index.blade.php)
  - [nino-v1 settings](resources/views/themes/nino-v1/admin/settings/index.blade.php)
  so the general tab renders clearly grouped operational sections instead of one flat field list.
- Added realistic seeded defaults in [SettingsSeeder](database/seeders/SettingsSeeder.php) for the new general settings keys.
- Extended [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php) to verify:
  - grouped general settings sections render
  - website/company/contact/branding values persist through the real settings controller
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `131` tests and `943` assertions
- Exact next task IDs:
  - `P3-GENERAL-05`
  - `P3-CONN-01`
  - `P3-CONN-02`
  - `P3-CONN-03`

### Batch 72 — Phase 3 Settings Foundation (2026-03-20)
- Implemented only `P3-SETTINGS-01`, `P3-SETTINGS-02`, `P3-SETTINGS-03`, `P3-SETTINGS-04`, and `P3-SETTINGS-05`.
- Verified the production settings foundation already present in:
  - [Setting](app/Modules/Settings/Models/Setting.php)
  - [SettingsService](app/Modules/Settings/Services/SettingsService.php)
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsController](app/Modules/Settings/Http/Controllers/SettingsController.php)
- Added focused Phase 3 coverage in [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php) for:
  - grouped settings page rendering
  - grouped typed retrieval
  - section validation
  - encrypted secret storage
  - cache invalidation on single and bulk writes
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `130` tests and `932` assertions
- Exact next task IDs:
  - `P3-GENERAL-01`
  - `P3-GENERAL-02`
  - `P3-GENERAL-03`
  - `P3-GENERAL-04`

### Batch 71 — Stock Workspace Completion Slice and Community Moderator Placeholder Workspace (2026-03-20)
- Implemented only `P2-STOCK-03`, `P2-STOCK-04`, and `P2-MOD-01`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `stock_workspace.adjustment_summary`
  - `stock_workspace.transfer_ready_metrics`
  - `moderator_workspace.report_queue`
- Kept the widgets grounded in real module state:
  - adjustment summary uses real `inventory_movements` with `manual_adjustment` reason
  - transfer-ready metrics uses real `stock_transfers` pending/shipped rows and in-flight units
  - moderator placeholder uses real `community_reports` and `community_moderation_queue_items` counts without inventing unfinished moderation UI routes
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Stock Manager sees the completed stock workspace slice
  - Community Moderator sees the schema-ready moderation placeholder workspace
  - Platform Admin does not see these role-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `125` tests and `910` assertions
- Exact next task IDs:
  - `P2A-AUDIT-01`
  - `P2A-AUDIT-02`
  - `P2A-AUDIT-03`
  - `P2A-AUDIT-04`

### Batch 70 — Sales Workspace Completion Slice and Stock Workspace Foundation (2026-03-20)
- Implemented only `P2-SALES-03`, `P2-SALES-04`, `P2-STOCK-01`, and `P2-STOCK-02`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `sales_workspace.best_sellers`
  - `sales_workspace.promo_performance`
  - `stock_workspace.low_stock`
  - `stock_workspace.damaged_stock`
- Kept the widgets grounded in real module state:
  - best sellers reuses real order line item revenue aggregation
  - promo performance uses real coupon redemptions, discount totals, active coupons, and recoverable abandoned carts
  - low stock uses real stock item alert state
  - damaged stock uses real inventory movement deductions with `damage` reason
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Sales Manager sees the completed sales workspace slice
  - Stock Manager sees the new stock workspace
  - Platform Admin does not see these role-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `124` tests and `897` assertions
- Exact next task IDs:
  - `P2-STOCK-03`
  - `P2-STOCK-04`
  - `P2-MOD-01`

### Batch 69 — Media Buying Hooks/Placeholder Slice and Sales Workspace Foundation (2026-03-20)
- Implemented only `P2-MEDIA-02`, `P2-MEDIA-03`, `P2-SALES-01`, and `P2-SALES-02`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `media_workspace.campaign_hooks`
  - `media_workspace.traffic_placeholder`
  - `sales_workspace.revenue_trend`
  - `sales_workspace.aov`
- Kept the widgets grounded in real module state:
  - campaign hooks uses real coupon redemptions, discount value, active coupons, and recoverable abandoned-cart exposure
  - traffic placeholder is intentionally honest and uses real configured analytics/pixel settings instead of fabricated traffic numbers
  - revenue trend uses real order-window revenue series
  - AOV uses real revenue and order counts for the active dashboard window
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Media Buying / Marketing Manager sees the completed media workspace slice
  - Sales Manager sees the new `Sales Workspace`
  - Platform Admin does not see these role-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `123` tests and `882` assertions
- Exact next task IDs:
  - `P2-SALES-03`
  - `P2-SALES-04`
  - `P2-STOCK-01`
  - `P2-STOCK-02`

### Batch 68 — Content Workspace Completion Slice and Media Integration Health Widget (2026-03-20)
- Implemented only `P2-SEO-02`, `P2-SEO-03`, `P2-SEO-04`, and `P2-MEDIA-01`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `content_workspace.missing_metadata`
  - `content_workspace.seo_issue_summary`
  - `content_workspace.scheduled_content`
  - `media_workspace.integration_health`
- Kept the widgets grounded in real module state:
  - missing metadata uses real CMS posts, published catalog products, and active categories missing meta title or description
  - SEO issue summary uses real missing-title, missing-description, missing-canonical, and published-noindex counts
  - scheduled content uses real scheduled blog posts, same-day publish counts, and the next scheduled publish time
  - media integration health uses real SEO settings keys for Google Analytics, Facebook Pixel, and TikTok Pixel
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - SEO / Content Manager sees the completed `Content Workspace`
  - Media Buying / Marketing Manager sees the new `Integration Health Widget`
  - Platform Admin does not see these role-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `122` tests and `867` assertions
- Exact next task IDs:
  - `P2-MEDIA-02`
  - `P2-MEDIA-03`
  - `P2-SALES-01`
  - `P2-SALES-02`

### Batch 67 — Finance Workspace Completion Slice and Content Drafts Widget (2026-03-20)
- Implemented only `P2-FINANCE-04`, `P2-FINANCE-05`, `P2-FINANCE-06`, and `P2-SEO-01`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `finance_workspace.cod_reconciliation_summary`
  - `finance_workspace.refund_summary`
  - `finance_workspace.discount_fee_impact`
  - `content_workspace.drafts`
- Kept the widgets grounded in real module state:
  - COD reconciliation uses real COD transaction statuses and outstanding COD volume
  - refund summary uses real requested/approved refund counts and queued value
  - discount/fee impact uses real payment transaction discount and fee totals
  - drafts widget uses real CMS draft and scheduled blog posts with recent draft items
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Finance Manager sees the completed finance workspace slice
  - SEO / Content Manager sees the new `Drafts Widget`
  - Platform Admin does not see these role-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `121` tests and `852` assertions
- Exact next task IDs:
  - `P2-SEO-02`
  - `P2-SEO-03`
  - `P2-SEO-04`
  - `P2-MEDIA-01`

### Batch 66 — Shipping Tracking Queue and Finance Workspace Foundations (2026-03-20)
- Implemented only `P2-SHIPPING-05`, `P2-FINANCE-01`, `P2-FINANCE-02`, and `P2-FINANCE-03`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `shipping_workspace.tracking_queue`
  - `finance_workspace.paid_unpaid_summary`
  - `finance_workspace.payment_method_summary`
  - `finance_workspace.gateway_transactions_summary`
- Kept the data grounded in real module state:
  - tracking queue uses shipments missing tracking numbers across active shipping states
  - paid/unpaid summary uses real payment transaction status counts and volumes
  - payment method summary groups real payment transactions by `payment_method`
  - gateway transactions summary groups real payment transactions by `gateway` with failure counts
- Added small related index filters to make the new widgets actionable:
  - [ShipmentController](app/Modules/Shipping/Http/Controllers/ShipmentController.php) now supports `tracking=missing|present`
  - [TransactionController](app/Modules/Finance/Http/Controllers/TransactionController.php) now supports `gateway=...`
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Shipping Agent sees the new `Tracking Queue Widget`
  - Finance Manager sees the new `Finance Workspace`
  - Platform Admin does not see these role-only dashboard widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `120` tests and `838` assertions
- Exact next task IDs:
  - `P2-FINANCE-04`
  - `P2-FINANCE-05`
  - `P2-FINANCE-06`
  - `P2-SEO-01`

### Batch 65 — Shipping Workspace: Ready to Ship, Shipped, Failed Delivery, and Returned Parcel Queues (2026-03-20)
- Implemented only `P2-SHIPPING-01`, `P2-SHIPPING-02`, `P2-SHIPPING-03`, and `P2-SHIPPING-04`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a dedicated `shipping_workspace` payload covering:
  - `ready_to_ship`
  - `shipped`
  - `failed_delivery`
  - `returned_parcel_queue`
- Kept the data grounded in the real shipment lifecycle and existing shipping routes:
  - ready-to-ship uses `ready_to_ship` and `packed` shipment states
  - shipped uses dispatched/in-transit/delivered parcels already handed to the carrier
  - failed delivery uses real failed-delivery and delivery-issue data
  - returned parcel queue uses real returned shipments and same-day return counts
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Shipping Agent sees the new `Shipping Workspace`
  - Platform Admin does not see shipping-only dashboard widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `119` tests and `824` assertions
- Exact next task IDs:
  - `P2-SHIPPING-05`
  - `P2-FINANCE-01`
  - `P2-FINANCE-02`
  - `P2-FINANCE-03`

### Batch 64 — Support Workspace: Order Lookup, Customer Lookup, Open Cases / Refund Queue, and Recent Issue Timeline (2026-03-20)
- Implemented only `P2-SUPPORT-01`, `P2-SUPPORT-02`, `P2-SUPPORT-03`, and `P2-SUPPORT-04`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a dedicated `support_workspace` payload covering:
  - `order_lookup`
  - `customer_lookup`
  - `open_cases_refund_queue`
  - `recent_issue_timeline`
- Kept the data grounded in the current support and finance modules instead of inventing dashboard-only structures:
  - order lookup reuses real support lookup routes plus recent order references
  - customer lookup reuses real support issue customer identities and lookup queries
  - open cases / refund queue combines active support backlog with live refund exposure
  - recent issue timeline is powered by real `activity_timeline` entries scoped to support issues
- Replaced a MySQL-only `FIELD(...)` sort with a cross-database `CASE` sort so the support queue widget works in both production and the SQLite test suite.
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Customer Support Agent sees the new `Support Workspace`
  - Platform Admin does not see support-only dashboard widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `118` tests and `810` assertions
- Exact next task IDs:
  - `P2-SHIPPING-01`
  - `P2-SHIPPING-02`
  - `P2-SHIPPING-03`
  - `P2-SHIPPING-04`

### Batch 63 — Branch Workspace: Orders to Prepare, Stock Widgets, Dispatch Queue, and Issue Queue (2026-03-20)
- Implemented only `P2-BRANCH-01`, `P2-BRANCH-02`, `P2-BRANCH-03`, and `P2-BRANCH-04`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a dedicated `branch_workspace` payload covering:
  - `orders_to_prepare`
  - `stock_widgets`
  - `dispatch_queue`
  - `issue_queue`
- Kept the branch slice truthful to the current schema:
  - there is still no direct branch ownership on orders or shipments
  - `orders_to_prepare` is implemented from reserved-stock preparation pressure
  - `dispatch_queue` is implemented from outbound branch inventory movements
  - `issue_queue` is implemented from branch stock alerts and recent damage/manual-adjustment issue movements
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Branch Manager sees the new `Branch Workspace`
  - Platform Admin and Franchise Manager do not see branch-only widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `117` tests and `795` assertions
- Exact next task IDs:
  - `P2-SUPPORT-01`
  - `P2-SUPPORT-02`
  - `P2-SUPPORT-03`
  - `P2-SUPPORT-04`

### Batch 62 — Franchise Workspace: Sales Proxy, Stock Overview, Staff Summary, and Branch Order Pressure (2026-03-20)
- Implemented only `P2-FRANCHISE-01`, `P2-FRANCHISE-02`, `P2-FRANCHISE-03`, and `P2-FRANCHISE-04`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a dedicated `franchise_workspace` payload covering:
  - `sales_by_branch`
  - `branch_stock_overview`
  - `staff_performance_summary`
  - `branch_order_metrics`
- Kept the batch truthful to the current schema:
  - direct branch revenue and branch-linked order attribution do not exist yet
  - `sales_by_branch` is implemented as a branch activity proxy built from branch-scoped inventory movements, staffing, and available units
  - `branch_order_metrics` is implemented as a branch order-pressure proxy built from reserved stock, stock alerts, and recent movement volume
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Franchise Manager sees the new `Franchise Workspace`
  - Platform Admin does not see franchise-only dashboard widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `116` tests and `775` assertions
- Exact next task IDs:
  - `P2-BRANCH-01`
  - `P2-BRANCH-02`
  - `P2-BRANCH-03`
  - `P2-BRANCH-04`

### Batch 61 — Platform Admin Workspace: Recent Orders, Stock Alerts, Payment Exceptions, and Content/Promo Highlights (2026-03-20)
- Implemented only `P2-PLATFORM-02`, `P2-PLATFORM-03`, `P2-PLATFORM-04`, and `P2-PLATFORM-05`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a dedicated `platform_workspace` payload covering:
  - `recent_orders`
  - `stock_alerts`
  - `payment_exceptions`
  - `content_promo_highlights`
- Kept the data grounded in the current codebase instead of inventing later-phase reporting:
  - recent orders reuse the real order activity payload
  - stock alerts reuse live stock-threshold items
  - payment exceptions use pending/failed payment transactions
  - content/promo highlights use draft/scheduled posts plus active coupons and recoverable abandoned carts
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Platform Admin sees the new workspace section and all four widget titles
  - Super Admin also sees the Platform Admin workspace while still retaining the Super Admin-only sections
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `115` tests and `761` assertions
- Exact next task IDs:
  - `P2-FRANCHISE-01`
  - `P2-FRANCHISE-02`
  - `P2-FRANCHISE-03`
  - `P2-FRANCHISE-04`

### Batch 60 — Super Admin Command Center Widgets and Platform Action Queue (2026-03-20)
- Implemented only `P2-SUPER-09`, `P2-SUPER-10`, `P2-SUPER-11`, and `P2-PLATFORM-01`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with:
  - `super_admin_command.support_kpi`
  - `super_admin_command.finance_summary`
  - `super_admin_command.system_alerts`
  - `platform_action_queue`
- Kept the data truthful to the current schema and module boundaries:
  - support KPI is built from active, urgent/high-priority, and unassigned support issues
  - finance summary is built from captured revenue, payment-review exposure, and refund-queue exposure
  - system alerts are built from observability severity counts and recent incidents
  - platform action queue is built from real preparing orders, low-stock hotspots, delivery issues, and open support backlog
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Super Admin sees the new `Command Center` widgets
  - Platform Admin sees `Platform Action Queue`
  - Platform Admin still does not see Super Admin-only dashboard slices
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `115` tests and `751` assertions
- Exact next task IDs:
  - `P2-PLATFORM-02`
  - `P2-PLATFORM-03`
  - `P2-PLATFORM-04`
  - `P2-PLATFORM-05`

### Batch 59 — Super Admin Network Health Widgets: Low Stock, Shipping Exceptions, Franchise Summary, and Branch Summary (2026-03-20)
- Implemented only `P2-SUPER-05`, `P2-SUPER-06`, `P2-SUPER-07`, and `P2-SUPER-08`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with a new Super Admin `super_admin_health` payload covering:
  - `Low Stock Alerts`
  - `Shipping Exceptions`
  - `Franchise Performance Summary`
  - `Branch Performance Summary`
- Kept the implementation truthful to the current schema:
  - low-stock alerts are driven from current stock-item thresholds
  - shipping exceptions are driven from delivery-issue flags and failure/return shipment statuses
  - franchise and branch performance use real organization, staffing, tracked SKU, available-unit, and stock-alert data instead of inventing unsupported revenue attribution
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Super Admin sees the new `Network Health` section
  - Platform Admin does not see Super Admin-only network-health widgets
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `115` tests and `741` assertions
- Exact next task IDs:
  - `P2-SUPER-09`
  - `P2-SUPER-10`
  - `P2-SUPER-11`
  - `P2-PLATFORM-01`

### Batch 58 — Super Admin Executive Dashboard Widgets: GMV, Orders Today, Revenue Trend, and Failed Payments (2026-03-20)
- Implemented only `P2-SUPER-01`, `P2-SUPER-02`, `P2-SUPER-03`, and `P2-SUPER-04`.
- Extended [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php) with the first Super Admin executive widget payloads:
  - `GMV Summary`
  - `Orders Today`
  - `Revenue Trend`
  - `Failed Payments`
- Kept scope limited to Super Admin only by exposing the new widget section behind `is_super_admin`, while preserving the shared dashboard foundation for Platform Admin and other roles.
- Updated both theme dashboards:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Expanded [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to prove:
  - Super Admin sees the executive widget section
  - Platform Admin does not see Super Admin-only widgets
  - the generic dashboard contract still works for non-Super-Admin roles
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `115` tests and `731` assertions
- Exact next task IDs:
  - `P2-SUPER-05`
  - `P2-SUPER-06`
  - `P2-SUPER-07`
  - `P2-SUPER-08`

### Batch 57 — Dashboard Framework, Widget Sections, Empty/Loading States, and Date Presets (2026-03-20)
- Implemented only `P2-DASH-01`, `P2-DASH-02`, `P2-DASH-03`, and `P2-DASH-04`.
- Added lightweight preset-aware dashboard building in [DashboardController](app/Modules/IAM/Http/Controllers/DashboardController.php) and [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php). The dashboard now supports `7d`, `30d`, and `90d` windows with invalid values falling back to `30d`, and cache keys/window labels are derived from the active preset.
- Added reusable dashboard foundation components in both theme trees through:
  - [nino-v2 dashboard-layout](resources/views/themes/nino-v2/components/nino/dashboard-layout.blade.php)
  - [nino-v2 dashboard-section](resources/views/themes/nino-v2/components/nino/dashboard-section.blade.php)
  - [nino-v1 dashboard-layout](resources/views/themes/nino-v1/components/nino/dashboard-layout.blade.php)
  - [nino-v1 dashboard-section](resources/views/themes/nino-v1/components/nino/dashboard-section.blade.php)
- Upgraded the v2 chart widget contract in [chart-card](resources/views/themes/nino-v2/components/nino/chart-card.blade.php) and [app.js](resources/js/app.js) so chart widgets now have explicit loading overlays and server-rendered empty states.
- Rebuilt both dashboard views against the same payload contract:
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  - [nino-v1 dashboard](resources/views/themes/nino-v1/admin/dashboard.blade.php)
- Added focused coverage in [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php) to verify presets, widget sections, empty states, and the shared v1/v2 dashboard payload contract.
- Verification:
  - `php artisan test tests/Feature/DashboardFoundationTest.php`
  - `npm run build`
  - `php artisan test`
  - Result: full suite passing with `114` tests and `721` assertions
- Exact next task IDs:
  - `P2-SUPER-01`
  - `P2-SUPER-02`
  - `P2-SUPER-03`
  - `P2-SUPER-04`

### Batch 56 — Staff Authentication Foundation Closure (2026-03-20)
- Implemented only `P1-AUTH-01`.
- Added [StaffAuthenticationFlowTest](tests/Feature/StaffAuthenticationFlowTest.php) to verify the actual admin auth boundary: staff login page render, successful active staff login/logout, and rejection of customer/inactive/suspended accounts on the staff login surface.
- Verified the existing runtime in [AuthController](app/Modules/IAM/Http/Controllers/AuthController.php), [EnsureStaffUser](app/Http/Middleware/EnsureStaffUser.php), and the admin auth views already satisfies the intended production staff-auth contract without widening scope into later phases.
- This closes the final unchecked Phase 1 task, so the real execution point now moves to Phase 2.
- Verification:
  - `php artisan test tests/Feature/StaffAuthenticationFlowTest.php tests/Feature/SecurityHardeningTest.php tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `111` tests and `701` assertions
- Exact next task IDs:
  - `P2-DASH-01`
  - `P2-DASH-02`
  - `P2-DASH-03`
  - `P2-DASH-04`

### Batch 55 — Staff Security Controls, Impersonation Auditability, Sensitive Account Audits, and Role-Adapted Sidebar Priorities (2026-03-20)
- Implemented only `P1-SEC-01`, `P1-SEC-03`, `P1-SEC-04`, and `P1-NAV-01`.
- Closed the staff-security slice truthfully. The 2FA enforcement foundation was already present in the runtime through [EnsureStaffTwoFactorIsConfigured](app/Http/Middleware/EnsureStaffTwoFactorIsConfigured.php), [TwoFactorSetupController](app/Modules/IAM/Http/Controllers/TwoFactorSetupController.php), and the secured admin route group; this batch kept that scope intact and verified it directly in the suite.
- Added explicit sensitive-account audit events in [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php) so staff email/status/scope/organization changes now emit `staff.account_access.changed`, and manual password changes emit `staff.password.changed` with a safe non-secret payload.
- Verified impersonation as a real workflow rather than only synthetic event dispatch. [AuditLogTest](tests/Feature/AuditLogTest.php) now covers taking and leaving impersonation through the actual package routes and asserts the audit records are written.
- Added a role-priority layer to the sidebar builder through [MenuItem](app/Modules/Shared/Navigation/DTOs/MenuItem.php) and [MenuBuilder](app/Modules/Shared/Navigation/Services/MenuBuilder.php). Menu modules now declare role-priority hints so support, shipping, finance, inventory, content, and system sections are surfaced earlier for the operators who use them most, while preserving permission/scope filtering.
- Extended [AdminMenuTest](tests/Feature/AdminMenuTest.php) to prove the menu is now role-adapted in addition to being permission-safe.
- Verification:
  - `php artisan test tests/Feature/SecurityHardeningTest.php tests/Feature/AuditLogTest.php tests/Feature/AdminMenuTest.php`
  - `php artisan test`
  - Result: full suite passing with `108` tests and `680` assertions
- Exact next task IDs:
  - `P1-AUTH-01`

### Batch 54 — Staff Add/Edit Workflow, Role-Scope Assignment, and Activation Lifecycle (2026-03-20)
- Implemented only `P1-STAFF-02`, `P1-STAFF-03`, `P1-STAFF-04`, and `P1-STAFF-05`.
- Verified the add-staff and edit-staff admin surfaces as first-class Phase 1 workflows rather than implicit controller support only. Added [StaffManagementWorkflowTest](tests/Feature/StaffManagementWorkflowTest.php) to cover the create page, edit page, role/scope assignment on store, and status lifecycle updates.
- Confirmed the existing runtime in [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php) and the themed staff forms already satisfied the intended production behavior: add/edit UI, role selection, organization scope selection, organization assignment, and active/inactive status management.
- Kept scope limited to the staff-management batch; no unrelated module work was added.
- Verification:
  - `php artisan test tests/Feature/StaffManagementWorkflowTest.php tests/Feature/OrganizationAssignmentAndStaffIndexTest.php tests/Feature/StaffRoleAccessFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `105` tests and `653` assertions
- Exact next task IDs:
  - `P1-SEC-01`
  - `P1-SEC-03`
  - `P1-SEC-04`
  - `P1-NAV-01`

### Batch 53 — Organization Assignment, Scope Constraints, Branch-Aware Operational Prep, and Staff Roster Filters (2026-03-20)
- Implemented only `P1-ORG-04`, `P1-ORG-05`, `P1-ORG-06`, and `P1-STAFF-01`.
- Added [OrganizationAssignmentService](app/Modules/Organizations/Services/OrganizationAssignmentService.php) so staff accounts are now assigned to real organization units with validated scope-to-organization constraints instead of the earlier `organization_id` TODO path. Primary assignment now syncs both `users.organization_id` and the `organization_user` pivot.
- Added [OperationalScopeResolver](app/Modules/Organizations/Services/OperationalScopeResolver.php) as the branch-aware operational foundation for future stock/order/report scoping. It now resolves allowed organization IDs and branch IDs per actor and can apply branch filters to operational queries.
- Upgraded [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php) so create/update uses the new organization assignment rules, and the staff index now supports real status, scope, and organization filters in addition to search.
- Updated the staff create/edit forms and roster views in both theme trees so organization scope and organization-unit selection are visible to authorized users and the staff list displays organization labels instead of raw IDs.
- Added focused coverage in [OrganizationAssignmentAndStaffIndexTest](tests/Feature/OrganizationAssignmentAndStaffIndexTest.php) for platform assignment, franchise tree constraints, branch-aware scope resolution, and staff index filtering.
- Verification:
  - `php artisan test tests/Feature/OrganizationAssignmentAndStaffIndexTest.php tests/Feature/StaffRoleAccessFlowTest.php tests/Feature/OrganizationFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `101` tests and `634` assertions
- Exact next task IDs:
  - `P1-STAFF-02`
  - `P1-STAFF-03`
  - `P1-STAFF-04`
  - `P1-STAFF-05`

### Batch 52 — Permission Naming Convention and Organization Hierarchy Foundation (2026-03-20)
- Implemented only `P1-POLICY-03`, `P1-ORG-01`, `P1-ORG-02`, and `P1-ORG-03`.
- Added a first-class permission naming contract in [PermissionNaming](app/Modules/IAM/Support/PermissionNaming.php), then wired [Permission](app/Modules/IAM/Models/Permission.php) and [PermissionsSeeder](database/seeders/PermissionsSeeder.php) through that helper so permission generation, grouping, and validation follow one canonical `domain.action` style instead of scattered string conventions.
- Formalized the organization hierarchy foundation in [Organization](app/Modules/Organizations/Models/Organization.php) with explicit platform/franchise/branch constants, active-state helpers, child-type support rules, and a modular factory hook for testable organization creation.
- Added [OrganizationHierarchyService](app/Modules/Organizations/Services/OrganizationHierarchyService.php) to create valid platform, franchise, and branch entities with parent-type validation and deterministic code generation instead of relying on raw `type` strings everywhere.
- Added [OrganizationFactory](database/factories/OrganizationFactory.php) for platform/franchise/branch test setup and [OrganizationFoundationSeeder](database/seeders/OrganizationFoundationSeeder.php) for an idempotent default platform root. [DatabaseSeeder](database/seeders/DatabaseSeeder.php) now includes that foundation seed.
- Added focused regression coverage in [OrganizationFoundationTest](tests/Feature/OrganizationFoundationTest.php) for permission naming validity, organization hierarchy creation, parent validation rules, and default-platform seeding idempotency.
- Verification:
  - `php artisan test tests/Feature/OrganizationFoundationTest.php tests/Feature/IamModelFoundationTest.php tests/Feature/PasswordSessionAndScopeFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `97` tests and `618` assertions
- Exact next task IDs:
  - `P1-ORG-04`
  - `P1-ORG-05`
  - `P1-ORG-06`
  - `P1-STAFF-01`

### Batch 51 — Role-Permission Foundation and Major Authorization Coverage (2026-03-20)
- Implemented only `P1-IAM-03`, `P1-IAM-04`, `P1-POLICY-01`, and `P1-POLICY-02`.
- Formalized first-class role-permission relationships on the local IAM models by extending [Role](app/Modules/IAM/Models/Role.php) and [Permission](app/Modules/IAM/Models/Permission.php) with explicit runtime relationship helpers instead of relying on vendor behavior implicitly.
- Added [RolePresetRegistry](app/Modules/IAM/Services/RolePresetRegistry.php) and rewired [PermissionsSeeder](database/seeders/PermissionsSeeder.php) plus [DatabaseSeeder](database/seeders/DatabaseSeeder.php) so role presets are seeded from one authoritative registry, assigned deterministically, and no longer duplicated across seed paths.
- Added broader authorization coverage for major admin surfaces by introducing [ProductPolicy](app/Policies/ProductPolicy.php), [CategoryPolicy](app/Policies/CategoryPolicy.php), [OrderPolicy](app/Policies/OrderPolicy.php), [GatewaySettingPolicy](app/Policies/GatewaySettingPolicy.php), and [StockItemPolicy](app/Policies/StockItemPolicy.php), then binding them in [AppServiceProvider](app/Providers/AppServiceProvider.php).
- Wired explicit controller authorization into [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php), [CategoryController](app/Modules/Catalog/Http/Controllers/CategoryController.php), [OrderController](app/Modules/Orders/Http/Controllers/OrderController.php), [AdminCustomerController](app/Modules/Customers/Http/Controllers/AdminCustomerController.php), and [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php) so access control is enforced by policies at runtime instead of only by menu visibility.
- Added reusable permission middleware through [EnsureUserHasAnyPermission](app/Http/Middleware/EnsureUserHasAnyPermission.php) and registered the `permission.any` alias in [bootstrap/app.php](bootstrap/app.php). Applied it to settings, gateways, and reports route groups in [routes/web.php](routes/web.php).
- Added focused regression coverage in [IamModelFoundationTest](tests/Feature/IamModelFoundationTest.php) and [AuthorizationPolicyFoundationTest](tests/Feature/AuthorizationPolicyFoundationTest.php), then updated stale fixtures in audit/observability/order/release tests so the suite reflects the new authorization layer truthfully.
- Verification:
  - `php artisan test tests/Feature/IamModelFoundationTest.php tests/Feature/AuthorizationPolicyFoundationTest.php tests/Feature/ReleaseReadinessTest.php tests/Feature/StaffRoleAccessFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `92` tests and `535` assertions
- Exact next task IDs:
  - `P1-POLICY-03`
  - `P1-ORG-01`
  - `P1-ORG-02`
  - `P1-ORG-03`

### Batch 50 — Avatar Support, Last-Login Tracking, and Local IAM Models (2026-03-20)
- Implemented only `P1-USERS-04`, `P1-USERS-06`, `P1-IAM-01`, and `P1-IAM-02`.
- Added real avatar/profile-picture foundation through [UserAvatarService](app/Modules/IAM/Services/UserAvatarService.php) and the existing [ProfileController](app/Modules/Customers/Http/Controllers/ProfileController.php) customer profile endpoint. Avatar uploads are now validated, stored on the public disk, and old files are deleted on replacement.
- Verified last-login tracking as a runtime user foundation instead of leaving it implicit. [UserEntityFoundationTest](tests/Feature/UserEntityFoundationTest.php) now asserts both customer and staff auth surfaces persist `last_login_at` and `last_login_ip` through the existing `recordLogin()` hooks.
- Defined first-class local IAM models in [Permission](app/Modules/IAM/Models/Permission.php) and [Role](app/Modules/IAM/Models/Role.php), then wired the runtime to them via [config/permission.php](config/permission.php), [AppServiceProvider](app/Providers/AppServiceProvider.php), [RolePolicy](app/Policies/RolePolicy.php), [RoleAccessService](app/Modules/IAM/Services/RoleAccessService.php), [RoleController](app/Modules/IAM/Http/Controllers/RoleController.php), [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php), and the permission/role seeders.
- The local IAM models now provide explicit system-role semantics, custom-role querying, and permission grouping metadata instead of scattering those concerns across services and vendor-model imports.
- Added focused regression coverage in [IamModelFoundationTest](tests/Feature/IamModelFoundationTest.php) and extended [UserEntityFoundationTest](tests/Feature/UserEntityFoundationTest.php) for avatar replacement plus staff/customer last-login assertions.
- Verification:
  - `php artisan test tests/Feature/UserEntityFoundationTest.php tests/Feature/IamModelFoundationTest.php tests/Feature/CustomerAuthFlowTest.php tests/Feature/StaffRoleAccessFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `87` tests and `523` assertions
- Exact next task IDs:
  - `P1-IAM-03`
  - `P1-IAM-04`
  - `P1-POLICY-01`
  - `P1-POLICY-02`

### Batch 49 — Core User Entity, User Types, Profile Attributes, and Account States (2026-03-20)
- Implemented only `P1-USERS-01`, `P1-USERS-02`, `P1-USERS-03`, and `P1-USERS-05`.
- Turned [User](app/Models/User.php) into a real core user entity contract instead of a loose auth model by adding explicit staff/customer and active/inactive/suspended constants, richer state helpers/scopes, and identity normalization on save for first name, last name, username, phone, avatar, and derived display name handling.
- Upgraded [UserFactory](database/factories/UserFactory.php) so tests and local data now produce realistic user entities with structured profile fields, explicit type/state defaults, and dedicated `staff()`, `customer()`, `inactive()`, and `suspended()` states.
- Hardened the public Fortify registration path in [CreateNewUser](app/Actions/Fortify/CreateNewUser.php) so it now creates active customer accounts by default, supports both legacy `name` input and structured `first_name`/`last_name` input, normalizes usernames before uniqueness checks, and persists the new profile attributes instead of creating a generic auth record.
- Verified profile consistency through the existing customer account surface: customer profile updates now keep `name` and `username` synchronized via the centralized user-entity normalization instead of relying on controllers to manage identity fields manually.
- Added focused regression coverage in [UserEntityFoundationTest](tests/Feature/UserEntityFoundationTest.php) for user factory defaults/states, Fortify customer registration, structured profile attribute persistence, and customer profile update synchronization.
- Verification:
  - `php artisan test tests/Feature/UserEntityFoundationTest.php tests/Feature/CustomerAuthFlowTest.php tests/Feature/AuthPermissionFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `82` tests and `498` assertions
- Exact next task IDs:
  - `P1-USERS-04`
  - `P1-USERS-06`
  - `P1-IAM-01`
  - `P1-IAM-02`

### Batch 48 — Branch Scope, Own-Record Scope, Permission-Safe Navigation, and Staff Session Revocation (2026-03-20)
- Implemented only `P1-IAM-09`, `P1-IAM-10`, `P1-POLICY-04`, and `P1-SEC-02`.
- Extended [ScopeAuthorizationService](app/Modules/IAM/Services/ScopeAuthorizationService.php) to enforce branch and own-record scope correctly for users and organizations, then bound the modular [OrganizationPolicy](app/Policies/OrganizationPolicy.php) explicitly in [AppServiceProvider](app/Providers/AppServiceProvider.php) so organization checks are actually enforced at runtime.
- Finished the staff-side scope surface by allowing `own` as a real assignable organization scope in [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php), both theme staff forms, and the user IAM migration path. Added [2026_03_20_210000_expand_user_organization_scope_to_include_own.php](database/migrations/2026_03_20_210000_expand_user_organization_scope_to_include_own.php) so existing MySQL databases can adopt the new scope safely.
- Added a production-ready admin forced-logout foundation through [StaffSessionController](app/Modules/IAM/Http/Controllers/StaffSessionController.php), the new `admin.staff.sessions.revoke` route, and [SessionManagementService](app/Modules/IAM/Services/SessionManagementService.php). Session revocation now rotates `remember_token`, supports manual revoke, and is also triggered automatically when a staff account is suspended, deleted, or has its password reset from the staff admin surface.
- Audited session revocation with `staff.sessions.revoked` events from both the explicit revoke action and automatic staff lifecycle transitions.
- Closed the permission-safe UI/navigation gap by correcting the admin menu permission map across catalog, inventory, gateways, CMS, finance, shipping, support, reports, promotions, and notifications, then pruning empty headers in [MenuBuilder](app/Modules/Shared/Navigation/Services/MenuBuilder.php). This also truthfully closes `P1-NAV-03`, because the admin navigation now hides unauthorized modules instead of exposing dead or forbidden links.
- Added regression coverage in [PasswordSessionAndScopeFoundationTest](tests/Feature/PasswordSessionAndScopeFoundationTest.php), [AdminMenuTest](tests/Feature/AdminMenuTest.php), and [StaffRoleAccessFlowTest](tests/Feature/StaffRoleAccessFlowTest.php) for branch scope, own-record scope, real menu filtering, empty-header pruning, manual staff session revocation, and auto-revocation on suspension.
- Verification:
  - `php artisan test tests/Feature/PasswordSessionAndScopeFoundationTest.php tests/Feature/AdminMenuTest.php tests/Feature/StaffRoleAccessFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `78` tests and `465` assertions
- Exact next task IDs:
  - `P1-USERS-01`
  - `P1-USERS-02`
  - `P1-USERS-03`
  - `P1-USERS-05`

### Batch 47 — Customer Password Reset, Session Invalidation, and Platform/Franchise Scope Foundation (2026-03-20)
- Implemented only `P1-AUTH-03`, `P1-AUTH-05`, `P1-IAM-07`, and `P1-IAM-08`.
- Added production-ready customer password reset flow through [CustomerPasswordResetController](app/Modules/IAM/Http/Controllers/CustomerPasswordResetController.php), customer reset views in both theme trees, and type-aware password reset notifications via [CustomerResetPasswordNotification](app/Modules/IAM/Notifications/CustomerResetPasswordNotification.php), [StaffResetPasswordNotification](app/Modules/IAM/Notifications/StaffResetPasswordNotification.php), and the override in [User](app/Models/User.php).
- Preserved existing staff/admin auth and Fortify reset flow while making reset emails route users to the correct surface based on account type. Existing checkout-created customer password setup remained intact and now shares the same post-reset session invalidation behavior.
- Added [SessionManagementService](app/Modules/IAM/Services/SessionManagementService.php) and wired it into customer password reset, customer password setup, staff access setup, authenticated customer password changes, and Fortify password update/reset actions so older sessions are invalidated and `remember_token` is rotated on credential changes.
- Added [ScopeAuthorizationService](app/Modules/IAM/Services/ScopeAuthorizationService.php) and applied it to [UserPolicy](app/Policies/UserPolicy.php), [OrganizationPolicy](app/Policies/OrganizationPolicy.php), and [MenuVisibilityResolver](app/Modules/Shared/Navigation/Services/MenuVisibilityResolver.php), turning platform/franchise scope handling into a reusable hierarchy instead of scattered controller/policy conditionals.
- Verified the new foundation with [PasswordSessionAndScopeFoundationTest](tests/Feature/PasswordSessionAndScopeFoundationTest.php), covering staff reset routing, customer reset completion, session invalidation on password change/reset, and platform/franchise scope enforcement for policies and scoped menu items.
- Also confirmed `P1-AUTH-06` is already satisfied by the existing staff/customer login surfaces and controllers: both admin and customer login forms expose remember-me controls and both auth controllers pass the remember flag into `Auth::attempt(...)`.
- Verification:
  - `php artisan test tests/Feature/PasswordSessionAndScopeFoundationTest.php tests/Feature/CustomerAuthFlowTest.php tests/Feature/AuthPermissionFlowTest.php tests/Feature/AdminMenuTest.php`
  - `php artisan test`
  - Result: full suite passing with `73` tests and `434` assertions
- Exact next task IDs:
  - `P1-IAM-09`
  - `P1-IAM-10`
  - `P1-POLICY-04`
  - `P1-SEC-02`

### Batch 46 — Custom Roles, Permission Overrides, Staff Access Reset, and Role Home Routing (2026-03-20)
- Implemented only `P1-IAM-05`, `P1-IAM-06`, `P1-STAFF-06`, and `P1-NAV-02`.
- Added production-ready custom role management through [RoleController](app/Modules/IAM/Http/Controllers/RoleController.php), [RoleAccessService](app/Modules/IAM/Services/RoleAccessService.php), explicit vendor-model policy binding in [AppServiceProvider](app/Providers/AppServiceProvider.php), and staff role library screens in both theme trees.
- Added direct permission overrides for staff accounts in [StaffController](app/Modules/IAM/Http/Controllers/StaffController.php) and the shared staff forms, with grantable-permission filtering, scope-safe assignment checks, and audit logging for role and direct-permission changes.
- Added reset/setup-link flow for staff access in [StaffAccessSetupService](app/Modules/IAM/Services/StaffAccessSetupService.php), [StaffAccessSetupNotification](app/Modules/IAM/Notifications/StaffAccessSetupNotification.php), [StaffAccessSetupController](app/Modules/IAM/Http/Controllers/StaffAccessSetupController.php), and [StaffAccessLinkController](app/Modules/IAM/Http/Controllers/StaffAccessLinkController.php). The flow uses Laravel's password broker plus a temporary signed URL, preserves existing admin auth, and activates non-suspended staff when setup is completed.
- Added role-based admin home routing in [AdminHomeRouteService](app/Modules/IAM/Services/AdminHomeRouteService.php), then wired it into [AuthController](app/Modules/IAM/Http/Controllers/AuthController.php), [FortifyServiceProvider](app/Providers/FortifyServiceProvider.php), [TwoFactorSetupController](app/Modules/IAM/Http/Controllers/TwoFactorSetupController.php), the staff access-setup completion flow, and both theme sidebars so the "Home" target and post-login redirects honor the staff member's role capability profile.
- Hardened the role-management boundary to fail closed in partially seeded environments by switching staff UI affordances to policy gates and by guarding the `users.manage_roles` lookup in [RolePolicy](app/Policies/RolePolicy.php) and [RoleAccessService](app/Modules/IAM/Services/RoleAccessService.php). This removed the last runtime/test failure caused by direct `hasPermissionTo('users.manage_roles')` calls.
- Verification:
  - `php artisan test tests/Feature/AuditLogTest.php tests/Feature/StaffRoleAccessFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `68` tests and `408` assertions
- Exact next task IDs:
  - `P1-AUTH-03`
  - `P1-AUTH-05`
  - `P1-IAM-07`
  - `P1-IAM-08`

### Batch 45 — Customer Authentication Foundation and Checkout Account Setup Flow (2026-03-20)
- Implemented only `P1-AUTH-02` and `P1-AUTH-04`.
- Added an isolated customer authentication surface under `/account` without altering the existing staff/admin login flow. Customer login/logout and a basic authenticated account landing page are now handled by [CustomerAuthController](app/Modules/IAM/Http/Controllers/CustomerAuthController.php) with dedicated customer views in both theme trees.
- Added explicit route-boundary middleware through `EnsureCustomerUser` and `EnsureStaffUser`, then applied the staff-only guard to the admin route group so customer sessions cannot drift into admin routes and staff sessions are redirected away from customer-only account routes.
- Added production-ready password-setup flow for checkout-created customer accounts through [CustomerPasswordSetupService](app/Modules/IAM/Services/CustomerPasswordSetupService.php), [CustomerPasswordSetupNotification](app/Modules/IAM/Notifications/CustomerPasswordSetupNotification.php), and [CustomerPasswordSetupController](app/Modules/IAM/Http/Controllers/CustomerPasswordSetupController.php). The flow uses Laravel's password broker plus a temporary signed setup URL instead of inventing a custom token store.
- Wired checkout-created customer accounts into the setup-link flow inside [CustomerAccountService](app/Modules/Customers/Services/CustomerAccountService.php) with failure-safe logging so checkout does not break if notification delivery fails.
- Added focused regression coverage in [CustomerAuthFlowTest](tests/Feature/CustomerAuthFlowTest.php) for customer login/logout, staff-vs-customer auth boundaries, and setup-link completion, and re-ran [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php) to confirm checkout account creation still passes.
- Aligned [SupportLookupTest](tests/Feature/SupportLookupTest.php) with the now-enforced staff-only admin boundary by using a real active staff fixture instead of a generic user fixture.
- Verification:
  - `php artisan test tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test tests/Feature/CheckoutFlowTest.php`
  - `php artisan test tests/Feature/SupportLookupTest.php`
  - `php artisan test`
  - Result: full suite passing with `64` tests and `386` assertions
- Exact next task IDs:
  - `P1-IAM-05`
  - `P1-IAM-06`
  - `P1-STAFF-06`
  - `P1-NAV-02`

### Batch 44 — Strict Reconciliation Audit (2026-03-20)
- Audit-only pass. No new feature implementation was performed in this batch.
- Corrected the false `RELEASE_READY` / `Phase 18 complete` tracker state after reconciling the real codebase against `READ_FIRST.md`, `PHASES.md`, `PROJECT_PROGRESS.md`, `ARCHITECTURE.md`, and `phases-tasks.md`.
- Real phase statuses after audit:
  - `DONE`: Phase 0, Phase 9, Phase 14, Phase 16, Phase 17
  - `IN_PROGRESS`: Phase 1, Phase 2, Phase 2A, Phase 3, Phase 4, Phase 5, Phase 6, Phase 7, Phase 8, Phase 10, Phase 11, Phase 12, Phase 13, Phase 15
  - `BLOCKED`: Phase 18
- Key corrections backed by live code inspection:
  - `AccountOverviewController` still returns stubbed order history and tracking data.
  - `CustomerAccountService` still leaves password-setup and welcome communication as comments/hooks, not real flow.
  - `routes/web.php` still serves `/checkout/success` and `/checkout/failed` as stub strings.
  - `CheckoutService` creates orders but does not initiate real gateway payment flow.
  - `NotificationTriggerService` exists, but core order/payment/shipping flows are not wired to it.
  - Product/category polymorphic SEO attachment is missing even though blog/category SEO exists.
  - `PaymentTransaction::customer()` still points to a missing `App\\Modules\\Customers\\Models\\Customer` class.
  - `BulkActionController` still writes `products.is_active`, which is not the current catalog schema.
- Real next work resumes in Phase 1, not Phase 18. Exact next task IDs:
  - `P1-AUTH-02`
  - `P1-AUTH-04`
  - `P1-IAM-05`
  - `P1-IAM-06`
  - then `P1-STAFF-06`, `P1-NAV-02`
- Main release risk:
  - The late QA/performance/test work is real, but it does not override unfinished earlier dependency phases. Release readiness remains blocked until those gaps are closed.

### Batch 43 — Release Readiness and Final Validation (2026-03-20)
- `P18-RELEASE-01`: Added [LocalDemoDataSeeder](database/seeders/LocalDemoDataSeeder.php) and wired it into `DatabaseSeeder` for local environments only. The seeder provisions useful release-validation identities and sample records: support/shipping/finance staff accounts, a QA customer, a standard shipping method, a sample order, shipment, payment transaction, and linked support issue.
- `P18-RELEASE-01`: Applied the local demo seed once to the dev database with `php artisan db:seed --class=LocalDemoDataSeeder --no-interaction`.
- `P18-RELEASE-02`: Confirmed migrations are clean both in the live app (`php artisan migrate:status`) and in automated coverage with the new `ReleaseReadinessTest`, which asserts there are no pending migrations after the test database boots.
- `P18-RELEASE-03`: Updated `.env.example` so the current project-specific release surface is explicit: `ADMIN_THEME=nino-v2`, database socket support, session table/cookie settings, database cache and queue table settings, observability threshold, and performance cache TTLs. `config/performance.php` now reads the dashboard/settings cache TTLs from env.
- `P18-RELEASE-04`: Added `ReleaseReadinessTest` covering fresh-install fallback behavior. It verifies that the dashboard, settings, payment gateways, notification integrations, and observability report render safely on empty data, while gateway and integration defaults still self-seed correctly.
- Verification: `php artisan test tests/Feature/ReleaseReadinessTest.php` passed with `4` tests and `33` assertions. Full-suite verification also passed: `php artisan test` => `61` tests, `359` assertions.

### Batch 42 — Performance Review, Queue Safety, and Architecture Update (2026-03-20)
- `P18-PERF-02`: Reviewed real hot-path indexes through the live schema and added a scoped performance migration for the operational tables that are actually driving the current admin/report load: `orders`, `shipments`, `notification_logs`, `payment_logs`, `observability_events`, `jobs`, `job_batches`, and `failed_jobs`.
- `P18-PERF-02`: Added [2026_03_20_200000_add_phase18_performance_indexes.php](database/migrations/2026_03_20_200000_add_phase18_performance_indexes.php) with explicit indexes for date-range order reporting, shipment aging/status queues, notification failure timelines, payment callback incident slices, slow-request reporting, database-queue reservation lookups, active-batch checks, and failed-job recency queries.
- `P18-PERF-03`: Reviewed cache usage and kept it intentionally narrow. Added [config/performance.php](config/performance.php) so cache TTLs are explicit, made dashboard summary caching configurable in `DashboardMetricsService`, and optimized `SettingsService::setMany()` so settings-group cache invalidation happens once per bulk update instead of once per key.
- `P18-PERF-04`: Reviewed queue usage and tightened notification delivery semantics. `SendNotificationJob` now always targets the dedicated `notifications` queue and explicitly dispatches after commit, preventing queued workers from racing uncommitted `notification_logs`, order, or payment writes.
- `P18-DOCS-02`: Updated `ARCHITECTURE.md` with the current Phase 18 performance decisions: targeted hot-table indexes, safe cache boundaries, database-vs-Redis queue guidance, after-commit queue rules, and current queue naming policy.
- Verification: `php artisan migrate --force` applied the new Phase 18 index migration successfully. `php artisan test` passed with `57` tests and `326` assertions. Targeted regression coverage for the queue change also passed in `NotificationTriggerTest`, `ObservabilityReportTest`, and `OrderLifecycleTest`.

### Batch 41 — Shipping, Finance, Customer QA and Hot-Path Query Review (2026-03-20)
- `P18-QA-03`: Ran authenticated Shipping QA across `/admin/shipping/shipments`, `/admin/shipping/methods`, and `/admin/shipping/reports`. All three routes rendered successfully after the earlier shipment breakdown fix, with queue, methods, and reports loading cleanly.
- `P18-QA-04`: Ran authenticated Finance QA across `/admin/finance/transactions`, `/admin/finance/refunds`, and `/admin/finance/reports`. Transaction, refund, and finance report flows all rendered successfully.
- `P18-QA-05`: Ran real customer commerce QA across guest cart, guest checkout, and authenticated account overview. This surfaced a real blocker on the guest cart/checkout JSON endpoints, then narrowed CSRF exemptions in `bootstrap/app.php` to the intended guest commerce API routes (`api/cart*` and `api/checkout/process`). It also fixed `CheckoutController` so checkout failures return an actual debug message instead of a boolean.
- `P18-QA-05`: Applied the pending local migrations needed to match the completed Phase 16 and 17 work. While doing so, fixed a real MySQL compatibility issue in `2026_03_20_175000_create_community_moderation_tables.php` by shortening the moderation morph index name and making the migration resume-safe after a partial local run.
- `P18-QA-05`: Verified the customer flow end to end: guest cart add-item returned `200`, guest checkout returned `201` with order reference `ORD-69BD3BF0192F4`, and authenticated `GET /api/customer/overview` returned the expected profile/address/order stub JSON for `qa.customer@example.test`.
- `P18-PERF-01`: Reviewed high-frequency queries and consolidated the `DashboardMetricsService` hot path so revenue, order, and customer period comparisons now use aggregated conditional queries instead of separate counts/sums, and the order status board now uses one grouped query instead of six separate status counts.
- Verification: `php artisan migrate --force` completed successfully after the moderation migration fix. `php artisan test` passed with `57` tests and `326` assertions. Additional authenticated HTTP QA passed on `/admin/shipping/shipments`, `/admin/shipping/methods`, `/admin/shipping/reports`, `/admin/finance/transactions`, `/admin/finance/refunds`, `/admin/finance/reports`, `/api/cart`, `/api/checkout/process`, `/api/customer/overview`, and `/admin`.

### Batch 40 — Order Lifecycle, Notification Triggers, and Admin QA Coverage (2026-03-20)
- `P18-TEST-05`: Added `OrderLifecycleTest` covering the admin orders queue summary/filter contract, the admin order detail snapshot, and the public order tracking JSON response.
- `P18-TEST-06`: Added `NotificationTriggerTest` covering order placed, payment failed, and order shipped trigger paths with rendered template content, `notification_logs` persistence, and queued `SendNotificationJob` dispatches.
- `P18-QA-01`: Ran live Super Admin browser QA across dashboard, staff, payment gateways, and observability. This surfaced a real blocker on `/admin/reports/observability`, then hardened `ObservabilityReportController` so the report degrades safely when optional observability or queue tables are missing locally.
- `P18-QA-02`: Ran live Support browser QA across support lookup, order timeline, issues index, and create/show flows. Lookup search, timeline rendering, issue creation, and issue detail all passed in the live app.
- Added regression coverage in `ObservabilityReportTest` for the missing-table fallback discovered during QA.
- Verification: `php artisan test tests/Feature/ObservabilityReportTest.php tests/Feature/OrderLifecycleTest.php tests/Feature/NotificationTriggerTest.php` passed with `9` tests and `62` assertions. Live browser QA passed on `/admin`, `/admin/staff`, `/admin/gateways`, `/admin/reports/observability`, `/admin/support/lookup`, `/admin/support/lookup/order/1/timeline`, `/admin/support/issues`, `/admin/support/issues/create`, and the created issue detail route.

### Batch 39 — Core Stabilization Test Coverage, Part 1 (2026-03-20)
- `P18-TEST-01`: Added `AuthPermissionFlowTest` covering guest auth redirection for admin settings, permission denial for staff without `settings.manage`, and successful settings access for authorized staff.
- `P18-TEST-02`: Added `CheckoutFlowTest` covering guest checkout account creation and order snapshot persistence, plus authenticated customer checkout using Sanctum without guest identity fields.
- `P18-TEST-03`: Added `InventoryReservationTest` covering reservation state updates, insufficient-stock protection, and capped stock release behavior with movement assertions.
- `P18-TEST-04`: Added `PaymentGatewayAdapterTest` covering deterministic payment adapter behavior for offline unsupported automation paths plus pre-network failure-fast behavior for CMI, Payzone, and Stripe gateway configuration errors.
- Verification: `php -l` passed for `tests/Feature/AuthPermissionFlowTest.php`, `tests/Feature/CheckoutFlowTest.php`, `tests/Feature/InventoryReservationTest.php`, and `tests/Feature/PaymentGatewayAdapterTest.php`. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 38 — Community Onboarding Foundation and Performance Planning (2026-03-20)
- `P17-ONBOARD-01`: Added `CommunityOnboardingService`, user-level onboarding flags (`community_auto_invite_to_default_group`, `community_default_group_invited_at`), and default-group invitation wiring for new customers created through checkout and admin customer creation.
- `P17-PERF-01`: Documented community media handling rules in `ARCHITECTURE.md`, including storage abstraction, async derivatives, validation boundaries, and polymorphic attachment constraints.
- `P17-PERF-02`: Documented video scalability considerations in `ARCHITECTURE.md`, covering object storage assumptions, queued processing, and external encoding paths for future scale.
- `P17-PERF-03`: Added quota and moderation planning notes in `ARCHITECTURE.md`, covering upload ceilings, configurable limits, flagged-media holds, and retention/cleanup expectations.
- Expanded `CommunityFoundationTest` with default-group invitation coverage for active customers and opt-out behavior, and updated the checkout/admin customer creation path to use the new onboarding foundation.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 37 — Community Reports and Moderation Foundation (2026-03-20)
- `P17-DOMAIN-09`: Added the `CommunityReport` model, `CommunityReportReason`, `CommunityReportState`, and the `community_reports` table using a polymorphic `reportable` target for posts, comments, and media attachments.
- `P17-MOD-01`: Added `Community Moderator` permission hooks in `PermissionsSeeder`, including community view/report/moderation permissions assigned to the existing role.
- `P17-MOD-02`: Added the `ModerationQueueItem` model and `community_moderation_queue_items` table with assignment, status, priority, and moderatable/report linkage for a future moderation queue.
- `P17-MOD-03`: Added explicit report lifecycle states plus queue-to-review helpers so community reports can move through submitted, queued, in-review, action-taken, and dismissed flows.
- Expanded `CommunityFoundationTest` with moderation/report lifecycle coverage and role-permission seeding assertions for the Community Moderator role.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 36 — Community Content Foundation: Posts, Media, Comments, Reactions (2026-03-20)
- `P17-DOMAIN-05`: Added the foundational `Post` aggregate with `PostStatus`, `community_posts`, group/author ownership, publish helpers, root comment access, and post-level media/reaction relationships.
- `P17-DOMAIN-06`: Added the `MediaAttachment` structure through `MediaAttachmentType` and `community_media_attachments`, using a polymorphic attachable design so posts and comments can share the same upload foundation.
- `P17-DOMAIN-07`: Added the `Comment` model and `community_comments` table with reply threading (`parent_id`), edit tracking, soft deletion, and post/comment attachment and reaction relationships.
- `P17-DOMAIN-08`: Added the `Reaction` model, `ReactionType`, and `community_reactions` table using a polymorphic reactable structure with uniqueness protection per user/reactable/type.
- Expanded `CommunityFoundationTest` to cover post publishing, media attachment casting, threaded comments, and reactions across posts and comments.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 35 — Community Foundation: Groups, Memberships, Join Requests (2026-03-20)
- `P17-DOMAIN-01`: Added the foundational `Group` aggregate with enums for visibility and membership policy, plus the `community_groups` table for future community surfaces.
- `P17-DOMAIN-02`: Added the default community-group foundation through `DefaultCommunityGroupService`, `system_key` support, and `CommunitySeeder`, creating an idempotent `NinoWorld Community` system group.
- `P17-DOMAIN-03`: Added the `GroupMembership` model, membership role/status enums, and the `community_group_memberships` table with user, inviter, join timestamp, and metadata support.
- `P17-DOMAIN-04`: Added the `GroupJoinRequest` model, join-request status enum, and the `community_group_join_requests` table with reviewer, notes, and approval/rejection helpers.
- Added `CommunityFoundationTest` coverage for default-group idempotency plus enum casting across memberships and join requests.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 34 — Basic Observability Foundations (2026-03-20)
- `P16-OBS-01`: Added admin-facing notification failure visibility through the new observability report, including failed/retrying delivery counts, affected channels, and recent incident rows linked back to notification logs.
- `P16-OBS-02`: Added payment callback error visibility with gateway-level incident breakdowns on the observability report and hardened `PaymentCallbackController` to capture callback/webhook exceptions into `payment_logs`.
- `P16-OBS-03`: Added queue visibility foundation via pending/failed job summaries, queue breakdowns, active batch counts, and recent failed job parsing from Laravel's `jobs`, `job_batches`, and `failed_jobs` tables.
- `P16-OBS-04`: Added slow-operation logging foundation with `observability_events`, `ObservabilityLogger`, and `LogSlowOperations` middleware, plus an admin report section showing recent slow requests and duration metrics.
- Added `admin.reports.observability` with theme-aware views in both `nino-v1` and `nino-v2`, plus a Reports menu entry.
- Added `ObservabilityReportTest` coverage for report rendering, slow-request logging, and payment callback exception logging.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 33 — Security Hardening (2026-03-20)
- `P16-SEC-01`: Added enforced staff 2FA enrollment with a dedicated admin setup flow, QR code display, recovery codes, and forced-route middleware via `EnsureStaffTwoFactorIsConfigured` and `TwoFactorSetupController`.
- `P16-SEC-02`: Added runtime session hardening with `ApplySecuritySettings`, binding session lifetime to the `security.session_lifetime` setting and forcing encrypted, HTTP-only sessions. Also hardened `config/session.php` defaults for encryption and production-secure cookies.
- `P16-SEC-03`: Reworked the custom `AuthController` login path to use Fortify-compatible throttling and two-factor challenge behavior, so confirmed 2FA users are redirected to `/two-factor-challenge` and failed logins are rate limited.
- `P16-SEC-04`: Added global exception/input redaction rules in `bootstrap/app.php`, expanded audit redaction coverage for auth, recovery, 2FA, API, and payment secrets, and added `SecurityHardeningTest` coverage for forced 2FA, throttling, session settings, and flash redaction.
- Added theme-aware staff 2FA setup views for both `nino-v1` and `nino-v2`.
- Verification: `php -l` passed on all touched PHP files, including the new middleware, controller, route, and feature test files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 32 — Remaining Sensitive Event Audit Coverage (2026-03-20)
- `P16-EVENT-03`: Added product pricing-change auditing in `ProductController`, capturing `price`, `sale_price`, and `cost_price` diffs only when those values actually change.
- `P16-EVENT-04`: Added order status override auditing in `BulkActionController` for the current admin override path, logging per-order status transitions during bulk mark-processing and mark-shipped actions.
- `P16-EVENT-05`: Added refund lifecycle auditing in `RefundController` for approve, reject, and complete transitions, capturing structured before/after state.
- `P16-EVENT-06`: Added stock-adjustment auditing inside `InventoryService`, so manual adjustments and purchase-order restocks both produce audit events with quantity/status before/after snapshots and movement context.
- Expanded `AuditLogTest` coverage for product pricing, bulk order status overrides, refund transitions, and inventory stock adjustments.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 31 — Sensitive Event Audit Hooks (2026-03-20)
- `P16-EVENT-01`: Added explicit role-change auditing in `StaffController`, including old/new role arrays whenever staff role assignments change.
- `P16-EVENT-02`: Extended staff lifecycle auditing with explicit `staff.deactivated` events when an active staff member is moved to inactive/suspended, while keeping structured `staff.created` coverage.
- `P16-EVENT-07`: Added payment gateway configuration auditing in `AdminGatewaySettingController`, capturing mode/enablement/config changes with credential redaction preserved.
- `P16-EVENT-08`: Registered impersonation lifecycle listeners in `AppServiceProvider` for `TakeImpersonation` and `LeaveImpersonation`, logging impersonation start/end with actor and target attribution.
- Expanded `AuditLogTest` with regression coverage for role changes, staff deactivation, gateway configuration changes, and impersonation events.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 30 — Audit Log Foundation (2026-03-20)
- `P16-AUDIT-01` to `P16-AUDIT-03`: Expanded `audit_logs` into a real audit store with actor snapshots (`actor_type`, `actor_name`, `actor_email`), `target_label`, structured `context`, request metadata capture, redaction, and old/new diff extraction through a centralized `AuditLogger`.
- Replaced raw staff audit writes with structured snapshots in `StaffController`, preserving actor/target/action/context without logging password hashes or noisy full-model payloads.
- Added foundational sensitive-action hooks for grouped admin settings updates and notification integration credential updates, with secret redaction preserved in audit payloads.
- Added `AuditLogTest` coverage for staff creation auditing, settings group auditing, and redacted notification integration auditing.
- Verification: `php -l` passed on all touched PHP files. Full Laravel test execution remains blocked in this shell because CLI PHP is `8.2.27` while Composer dependencies require `>= 8.4.0`.

### Batch 29 — Stitch UI System & TALL Stack Adherence (2026-03-19)
- Conducted full visual UI Audit and implemented the "Stitch Design Language" metrics.
- Hardcoded CSS Theme (`app.css`) with standard brand colors (Cream, Sage, Surface) using Tailwind CSS v4 `@theme`.
- Created `<x-nino.*>` Component Library (`table`, `button`, `card`, `modal`).
- Transformed Admin panel global UX: Enabled Alpine.js interactive collapsible sidebars and `wire:navigate` for SPA-like instant routing.
- Built Role-Adapted Dashboard rendering metrics driven by the user's assigned role.
- Refactored Core Catalog & Inventory interfaces into high-density tables (25+ per page) utilizing custom `<x-nino.table>` and `<x-nino.card>`, pushing spacing rules strict for desktop management efficiency.
- Added dynamic Livewire Stock adjustment component via Alpine.js slide-overs (`QuickEdit`).

### Batch 28 — Reports, Search, Bulk Actions, Imports/Exports (2026-03-19)
- `P15-REPORT-01` to `P15-REPORT-05`: Created 5 comprehensive report dashboards: Sales (revenue, avg order value, sales over time, top products), Orders (fulfillment stats, daily volume, status breakdown), Inventory (stock levels, low/out-of-stock items, value), Coupons (usage stats, top codes), and Finance (gross/net revenue, gateway performance, method breakdown).
- `P15-SEARCH-01` to `P15-SEARCH-03`: Built reusable `Filterable` trait for applying search, exact, boolean, and date filters to Eloquent models. Added `SavedFilter` model and migration for users to save custom query parameter presets per module.
- `P15-BULK-01` to `P15-BULK-03`: Created `BulkActionController` supporting batch operations for Products (activate/deactivate/delete), Orders (mark processing/shipped/export), and CMS Content (publish/unpublish/archive/delete).
- `P15-EXPORT-01` to `P15-EXPORT-02`: Created `ExportController` with memory-efficient chunked CSV exporters for Products, Orders, Transactions, and Inventory. Implemented a foundational Catalog CSV importer for rapid inventory updates.
- Added `ReportsMenu` to the admin sidebar.

### Batch 27 — Support Tools, Customer Timeline, Internal Notes (2026-03-19)
- `P14-LOOKUP-01` to `P14-LOOKUP-03`: Built `SupportLookupController` with unified search (order#/email/phone/name + transaction reference), fast filters with result tables, clickable Timeline links.
- `P14-TIMELINE-01` to `P14-TIMELINE-04`: Built order timeline view showing order summary, payment transaction history, polymorphic `ActivityTimeline` events with performer attribution, and internal notes sidebar with add/pin/delete.
- `P14-NOTES-01` to `P14-NOTES-04`: Created polymorphic `InternalNote` model attachable to any entity (orders, issues, etc). Staff attribution via `created_by` FK. Pin/unpin functionality. Audit visibility through `ActivityTimeline::log()` on every note action.
- `P14-QUEUE-01` to `P14-QUEUE-02`: Created `SupportIssue` model with `IssueStatus` (5 states), `IssueType` (7 categories with icons), `IssuePriority` (4 levels). Priority-weighted listing with 5-filter search. Lifecycle actions (Start Working → Resolve → Close / Reopen) with automatic timeline logging. Issue detail view with contextual action buttons, inline notes, and assignment.
- Created `SupportMenu` sidebar (Lookup, Issues). Wired all routes. Migration ran successfully.

### Batch 26 — Finance, Transaction Visibility, Reconciliation Foundations (2026-03-19)
- `P13-DATA-01` to `P13-DATA-04`: Created `PaymentTransaction` model with order financial snapshot (subtotal/discount/shipping/tax/total), `TransactionType` enum (6 types), `TransactionStatus` (5 states), `PaymentMethod` enum (8 methods with icons/COD helper), `currency` field defaulting to MAD.
- `P13-TRANS-01` to `P13-TRANS-03`: Built `TransactionController` with paginated list, 6-filter search (reference/status/type/method/date range), volume stats cards, clickable rows linking to transaction detail view with linked order info.
- `P13-REPORT-01` to `P13-REPORT-02`: Built `FinanceReportController` with revenue overview (gross/net/fees/refunds/discounts), payment method breakdown table, gateway success/failure summary with success rate percentages.
- `P13-COD-01` to `P13-COD-02`: Created `CodStatus` enum (pending/collected/deposited/reconciled/discrepancy). Added COD lifecycle fields and helpers (`markCodCollected/Deposited/Reconciled`, `hasCodDiscrepancy`, `codDiscrepancyAmount`). Built COD reconciliation tab with unreconciled pipeline and collect/deposit/reconcile actions.
- `P13-REFUND-01` to `P13-REFUND-02`: Created `RefundRequest` model with `RefundStatus` enum (5 states), approval workflow actions (approve/reject/complete), `RefundController` with listing, stats, and action buttons. Refund totals and avg/rejection rate in reports.
- `P13-FEE-01` to `P13-FEE-02`: Added discount and fee visibility tabs in reports dashboard showing totals and averages.
- Created `FinanceMenu` sidebar (Transactions, Refunds, Finance Reports). Wired all routes. Migration ran successfully (replaced legacy payment_transactions table).

### Batch 25 — CMS, Blog, SEO, Redirects & Content Tools (2026-03-19)
- `P12-TAX-01` to `P12-TAX-04`: Created `BlogCategory` model (hierarchy with parent/children, slugs, active/inactive, sort order, SEO fields) and `BlogTag` model (name/slug, many-to-many posts). Full CRUD controllers and views for both.
- `P12-POST-01` to `P12-POST-08`: Created `BlogPost` model with `PostStatus` enum (4 states), author relationship, featured image, body, excerpt. Built `BlogPostController` with CRUD, search/status/category filtering, stats dashboard, tag syncing, `publishScheduled()` for scheduled posts, and publish/unpublish toggle.
- `P12-SEO-01` to `P12-SEO-05`: Added per-post meta title, meta description, canonical URL, OG title/description/image, and noindex toggle. Built resolved fallback helpers.
- `P12-ENTITYSEO-01` to `P12-ENTITYSEO-04`: Created polymorphic `SeoMetadata` model and `seo_metadata` table, attachable to any entity (products, categories, blog categories, posts).
- `P12-SITEMAP-01` to `P12-SITEMAP-03`: Created `Redirect` model and `redirects` table with 301/302 support, hit count tracking, `resolve()` method. Auto-slug generation on all content types.
- `P12-UX-01` to `P12-UX-04`: Built rich preview page (with SEO card), publish/unpublish toggle, author attribution, featured image URL, tag chip selection. Created 7 Blade views with NinoWorld OS theme.
- Wired all routes (CMS prefix) and added `CmsMenu` (Blog Posts, Categories, Tags, Redirects) to sidebar.

### Batch 24 — Coupons, Promotions & Abandoned Cart Foundations (2026-03-19)
- `P11-MODEL-01` to `P11-MODEL-10`: Created `CouponType` and `CouponStatus` enums. Created `Coupon` model (with 30+ fields for targeting, exclusions, stackability, dates, usage limits). Created `CouponUsage` model for per-order snapshots. Created `AbandonedCart` model for tracking recovery state. Handled migrations.
- `P11-ADMIN-01` to `P11-ADMIN-03`: Built `CouponController` with CRUD, search, status/type filters. Developed comprehensive 6-section create/edit form, including targeting logic.
- `P11-VALIDATE-01` to `P11-VALIDATE-04`: Generated `CouponService` as the validation engine featuring 11 chained eligibility rules (active, dates, usage limits, cart constraints, product/category targeting, exclusions). Includes transactional `apply()` and `remove()` methods with error code handling.
- `P11-REPORT-01` to `P11-REPORT-02`: Created promotions reports view featuring revenue impact stats and 3 tabbed sections: Top Coupons, Recent Usages (showing before/after totals), and Abandoned Carts.
- `P11-ABANDON-01` to `P11-ABANDON-03`: Created `AbandonedCartService` with update-or-create capture rules. Integrated recovery processing triggered with 1-hour delay limits, throttles, and recovery hooks into the notification system. Modified checkout process foundation for recovering carts.
- Wired all routes and added `PromotionsMenu` to the sidebar navigation.

### Batch 23 — Notifications, Email, SMS, WhatsApp & Messaging Center (2026-03-19)
- `P10-ARCH-01` to `P10-ARCH-04`: Built event-driven notification architecture with `NotificationEvent` enum (10 events across 5 groups with per-event template variable mapping), `NotificationChannel` enum (Email/SMS/WhatsApp), `NotificationStatus` enum (5 states with badge colors), and `SendNotificationJob` queue job (idempotent with exponential backoff retries on `notifications` queue).
- `P10-CHANNEL-01` to `P10-CHANNEL-03`: Implemented `NotificationChannelDriver` contract and 3 channel drivers: `EmailChannel` (Laravel Mail facade with admin SMTP override), `SmsChannel` (Twilio REST API with basic auth), `WhatsAppChannel` (Meta Cloud API with E.164 phone formatting).
- `P10-TRIGGER-01` to `P10-TRIGGER-10`: Created `NotificationTriggerService` with 10 convenience methods: `orderPlaced`, `orderCancelled`, `paymentSuccess`, `paymentFailed`, `orderShipped`, `orderDelivered`, `welcome`, `passwordSetup`, `abandonedCart`, `promotionalOffer` — each collecting domain model data and dispatching via `NotificationDispatcher`.
- `P10-TEMPLATE-01` to `P10-TEMPLATE-04`: Built `NotificationTemplateController` (CRUD + unique event/channel constraint + preview with sample data + inline toggle), and admin views: grouped index by event category, create/edit form with dynamic variable reference panel and click-to-copy.
- `P10-LOGS-01` to `P10-LOGS-04`: Built `NotificationLogController` with stats cards (sent/failed/queued/total), multi-filter (status/channel/event/search), detail view with rendered body and variable dump, retry action for failed notifications. `NotificationLog` model with exponential backoff retry (2^n minutes) and `markSent`/`markFailed` helpers.
- `P10-INTEGRATION-01` to `P10-INTEGRATION-03`: Built `IntegrationSettingController` with auto-seeding of SMTP/Twilio/WhatsApp defaults, smart secret merging (preserves existing when masked), and provider-specific credential forms with masked password fields.
- Migration created 3 tables (`notification_templates`, `notification_logs`, `integration_settings`) — ran successfully. `NotificationsMenu` sidebar nav added (Templates, Delivery Logs, Integrations).
- **Finished Phase 10 completely.**
### Batch 22 — Shipping, Tracking, Fulfillment & Shipping Roles (2026-03-19)
- `P9-MODEL-01` to `P9-MODEL-05`: Created `ShippingMethod` model (with cost calculation and free-shipping threshold), `Shipment` model (with carrier tracking URL auto-generation for Amana/DHL/Chronopost/FedEx/UPS), and `ShipmentStatusHistory` model. Three-table migration with `shipping_methods`, `shipments` (35+ fields including weight, dimensions, package_count, timestamps per state, issue tracking, staff attribution), and `shipment_status_history` for immutable audit trail.
- `P9-SETTINGS-01` to `P9-SETTINGS-03`: Built `ShippingMethodController` with full CRUD, auto-slug generation, deletion protection for methods with shipments, and admin Blade views (index + create/edit form with carrier/cost/threshold/days fields).
- `P9-FLOW-01` to `P9-FLOW-06`: Implemented `ShipmentStatus` enum with 9 lifecycle states (pending → ready_to_ship → packed → dispatched → in_transit → delivered, with failed_delivery → returned branch), validated transition rules, CSS badge colors, and finality checks. `ShipmentService` handles all transitions transactionally with automatic timestamp population per state.
- `P9-AGENT-01` to `P9-AGENT-04`: Built `ShipmentController` with stats-card dashboard (ready_to_ship/packed/in_transit/issues/returned counts), status/search filtering, radio-based status transition UI with contextual fields (tracking # on dispatch, failure reason on failed delivery), tracking update form, and delivery issue flag/resolve toggle.
- `P9-CUSTOMER-01` to `P9-CUSTOMER-02`: Created `CustomerTrackingController` returning JSON with tracking number, URL, carrier, status, all timestamps, and full status timeline for customer account integration.
- `P9-REPORT-01` to `P9-REPORT-03`: Built 3-tab shipping reports view (Shipment History, Status Changes Log with audit trail, Failed/Returned queue).
- Added `ShippingMenu` sidebar navigation (Shipments, Shipping Methods, Shipping Reports), registered in `AppServiceProvider`, added `Order→shipment/shipments` relationships. Migration ran successfully.
- **Finished Phase 9 completely.**

## Last Batch Completed
### Batch 21 — Gateway Integrations, Payment Logs & Audit (2026-03-19)
- `P8-CMI-01` to `P8-CMI-04`: Built the `CmiPaymentGateway` adapter implementing the full CMI Morocco flow — HMAC-SHA512 hash-signed form redirect, 3D Secure multi-status parsing (mdStatus 1/2/5/6/7/9), response hash verification, and webhook idempotency.
- `P8-PAYZONE-01` to `P8-PAYZONE-04`: Built `PayzonePaymentGateway` using REST API calls via cURL to Payzone's payment intent endpoint, with HMAC-SHA256 request signing, hosted checkout URL redirect, callback/webhook handling, and full refund support.
- `P8-STRIPE-01` to `P8-STRIPE-04`: Built `StripePaymentGateway` using Stripe Checkout Sessions via raw API calls (no SDK dependency), with webhook signature verification including replay attack prevention (5-minute tolerance), payment intent state tracking, and Stripe Refunds API integration.
- `P8-LOGS-01` to `P8-LOGS-03`: Built the `PaymentLogger` service with PCI-compliant automatic redaction of card numbers, CVVs, and secret keys. Created `payment_logs` migration and `PaymentLog` model with forensic indexes. Supports initiation, callback, webhook, status change, failure, and refund logging events. Also logs to Laravel's error channel for ops alerting.
- Built `PaymentCallbackController` with gateway-specific callback/webhook handlers, wired all routes in `web.php`, added Payzone to gateway auto-seeder, expanded the admin gateway settings UI with Payzone-specific credential fields, and added smart credential merge logic to prevent `*******` masking from overwriting real secrets.
- **Finished Phase 8 completely.**

### Batch 20 — Offline Methods & Gateway Settings Admin (2026-03-19)
- `P8-OFFLINE-*` & `P8-GATEWAY-*`: Built the `GatewaySetting` model with encrypted credential storage, `AdminGatewaySettingController` for gateway CRUD, `OfflinePaymentGateway` adapter for bank transfer payments, and the admin UI for configuring payment gateways.

### Batch 19 — Payments Domain & Adapter Architecture (2026-03-19)
- `P8-DOMAIN-*` & `P8-ADAPTER-*`: Built the `PaymentTransaction` scale schema cleanly isolating gateway retries from master `Orders`. Created the abstract `PaymentGatewayInterface` and `PaymentResponse` normalized DTOs ensuring zero coupling between core application scopes and third-party bank SDK quirks.

### Batch 18 — Post-Checkout APIs & Admin Order Tracking (2026-03-19)
- `P7-ADMIN-01` to `P7-ADMIN-10`: Built `OrderController` executing dense pagination, filtering (status, reference search), and building strict UX Blade views (Customer timeline pattern, embedded Line Item grids, Customer and Admin notes).
- `P7-THANKYOU-*` & `P7-PAYERR-*`: Added `OrderTrackingController` fetching immutable snapshot history safely to Guest APIs so SPA frontends can natively render Checkouts Success/Failures dynamically via `api/orders/{reference}`.
- **Finished Phase 7 completely.**

### Batch 17 — Interactive Checkout Pipeline (2026-03-19)
- `P7-CHECKOUT-01` to `P7-CHECKOUT-07` + `P7-AUTOACC-*`: Built `CheckoutService` executing extreme transactional state transfers (Cart -> Order). Includes live mapping of Guest metadata into the `CustomerAccountService` to cleanly bootstrap accounts mid-transaction invisibly.
- Added overarching `ProcessCheckoutRequest` ensuring total address isolation between user-stated Shipping and Billing targets, avoiding payload tampering.
- Added API endpoints directing successful 201 payloads to frontend Payment flow/Thank You destinations.

### Batch 16 — Cart Session API & Service (2026-03-19)
- `P7-CART-01` to `P7-CART-07`: Centralized the Cart state engine using an `X-Cart-Session-Id` header to persist guest sessions inside a database `Cart` entity, avoiding fragile token local storage.
- Built `CartService` dynamically loading snapshot product prices, estimating shipping logic (`shipping_threshold`), checking coupons, and managing quantity scaling securely without stale data mapping.
- Exposed `CartController` delivering a robust summary JSON shape that handles free shipping progress math organically for the SPA.

### Batch 15 — Order Data Model & Status Logic (2026-03-19)
- `P7-ORDER-01` to `P7-ORDER-05`: Created `Order`, `OrderLineItem`, and `OrderAddress` models and migrations capturing immutable snapshots of purchases (pricing, addresses, and product states independently from catalog changes).
- `P7-STATUS-01` to `P7-STATUS-09`: Implemented `OrderStatus` Enum standardizing state loops (Pending, Paid, Shipped, Failed) directly inside the Order entity logic.

### Batch 14 — Customer Account APIs & Checkout Automation (2026-03-19)
- `P6-TIMELINE-01` to `P6-TIMELINE-03`: Built the `CustomerNote` model and `customer_notes` table to allow admins to write internal notes onto specific customers. Also deployed `AdminCustomerController` returning the customer profile with attached notes and addressing stubbed order timelines.
- `P6-CHECKOUT-01` to `P6-CHECKOUT-04`: Created `CustomerAccountService` to securely automate account generation when guests check out, ensuring robust password generation without ever exposing it via payload.
- `P6-ACCOUNT-01` to `P6-ACCOUNT-04`: Added `AccountOverviewController` returning critical account overview states and cleanly mapping empty arrays/strings to order entities until the `Orders` module is complete.

### Batch 13 — Customer Models & Address Schema (2026-03-19)
- `P6-CUSTOMER-01` to `P6-PROFILE-06`: Created the migration to augment the `users` table with decoupled attributes (`first_name`, `last_name`, `username`, `marketing_opt_in`). Also added `ProfileController` to handle profile updates and secure password changes.
- `P6-ADDRESS-01` to `P6-ADDRESS-06`: Created the `Address` model, `addresses` database migration, and `AddressController` complete with core CRUD capabilities, multi-type constraints (billing/shipping), and single-default protection.

### Batch 12 — Stock Reservation & Branch-Aware Architecture (2026-03-19)
- `P5-RESERVE-01` to `P5-RESERVE-03`: Implemented `reserveStock` and `releaseStock` logic in the `InventoryService` with strict atomic locking and an `InsufficientStockException`.
- `P5-BRANCH-01` to `P5-BRANCH-03`: Added Branch filtering to the Inventory UI index, built the `StockTransfer` data model, and created a `transferStock` method ensuring safe, deadlock-free inventory movements between branch instances.

### Batch 11 — Inventory Operations & Reports (2026-03-19)
- `P5-LOWSTOCK-01` to `P5-LOWSTOCK-03`: Implemented low stock filtering on the inventory index and added a "Low Stock Alerts" widget to the Admin Dashboard.
- `P5-DAMAGE-01` to `P5-DAMAGE-03`: Integrated damaged stock workflow via manual adjustments with strict reason logging, and built a dedicated "Movements & Damage" view to track all adjustments.
- `P5-REPORT-01` to `P5-REPORT-02`: Built `InventoryReportsController`, "Stock Report" and "Movements & Damage" views, integrating them into the Inventory menu.

### Batch 10 — Admin Visual Consistency Pass (2026-03-19)
- `P2A-AUDIT-01`: Refactored all major admin data tables (Products, Categories, Staff, Inventory) to match the high- density 'Stitch' layout.
- Abstracted raw form fields into reusable Blade components (`x-admin.input`, `x-admin.textarea`, `x-admin.select`) to ensure consistent focus states and colors across views.
- Auth views (login, password reset) were harmonized with the new layout style.
- The Admin shell (topbar, sidebar, dashboard) was upgraded with the transparent/blur header and bento-box metric layout.

### Batch 9 — Stock Adjustment Workflows (2026-03-19)
- `P5-ADJUST-01` to `P5-ADJUST-05`: Built `InventoryController` and manual adjustment Blade views (`index`, `adjust`) to interact with actual stock items.
- Developed `InventoryService` providing robust transactional boundaries and enforcing strict creation of `InventoryMovement` records on any change (auditing by user, tracking prior/after quantities, supporting relative and absolute overrides).
- Registered the Inventory module locally in the `routes/web.php` and `InventoryMenu`, completing the basic admin foundation for stock operation.

### Batch 8 — General Settings Population (2026-03-19)
- `P3-GENERAL-01` to `P3-GENERAL-05`: Created `SettingsSeeder` to inject sane defaults for General, SEO, Mail, and Security fields directly into the database.
- Integrated `SettingsSeeder` into the master `DatabaseSeeder` so fresh environments get default UI states immediately.

### Batch 7 — Inventory Data Model (2026-03-19)
- `P5-MODEL-01`: Created `create_inventory_tables` migration with `inventory_stock_items` and `inventory_movements`.
- `P5-MODEL-02` & `P5-MODEL-03`: `inventory_stock_items` links to either `product_id` (simple) or `product_variant_id` (variable).
- `P5-MODEL-04`: Added `branch_id` referencing the `organizations` table for granular, branch-aware tracking.
- `P5-MODEL-05`: Extracted stock logic out of core catalog into `quantity` vs `reserved_quantity` to safely reserve stock on checkout.
- Generated `StockItem` and `StockMovement` Eloquent models to provide an immutable audit trail for stock changes.
### Batch 6 — Settings Foundation (2026-03-19)
- `P3-SETTINGS-01`: Defined settings storage strategy — key-value table with `group`, `key`, `value`, `type`, `is_encrypted` columns.
- `P3-SETTINGS-02`: Implemented `SettingsService` with grouped retrieval (`group()`, `get()`, `set()`, `setMany()`).
- `P3-SETTINGS-03`: Created `SettingsDefinitions` with field metadata and validation rules for 4 tabs: General, SEO, Mail, Security.
- `P3-SETTINGS-04`: Added encrypted secret storage via Laravel `Crypt` — secrets (e.g. SMTP password) are transparently encrypted/decrypted.
- `P3-SETTINGS-05`: Added per-group caching (1hr TTL) with automatic invalidation on settings update.
- Built `SettingsController` with tab-based navigation and permission-guarded access (`settings.manage`).
- Created professional admin Blade view with dynamic field rendering, toggle switches, and masked secret inputs.
- Registered Settings routes (`GET/PUT admin/settings`) and integrated into admin navigation menu.

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
- none

## Blockers
- none
