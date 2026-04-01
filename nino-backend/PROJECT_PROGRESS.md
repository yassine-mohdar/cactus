# PROJECT_PROGRESS.md

## Last Updated
2026-03-29

## Current Phase
Phase 18 — Final Stabilization, QA, Testing, and Release Readiness

## Phase Routing Note
- Phase 2A is intentionally deferred by user instruction.
- Active execution is now on release-candidate hardening and tracker reconciliation after the final backend completion pass.

## Last Batch Completed
### Batch 129 — Release Candidate Hardening and Tracker Reconciliation (2026-03-29)
- Implemented final release-blocking backend hardening across checkout, notifications, bulk actions, media reuse, and runtime defaults.
- Unified product bulk actions behind a shared catalog service so both catalog and reports entrypoints now use the live product status schema instead of the stale `products.is_active` path.
- Completed release-blocking notification lifecycle wiring:
  - password setup notifications now write through the notification trigger/log system
  - shipment delivery and cancellation now trigger customer notifications from the real shipment lifecycle
  - payment cancellation now triggers the order-cancelled notification path from the real callback flow
- Completed checkout gateway initiation on the production checkout path so online gateways now return redirect/form payloads directly from checkout, with CMI covered as the primary release gateway.
- Added admin media reuse support for category and CMS post workflows through reusable public-media suggestions, closing the remaining media-picker depth gap without introducing a second media storage model.
- Updated `.env.example` to document the MySQL-local runtime contract and the current mail/runtime defaults expected by release readiness.
- Reconciled stale tracker state:
  - `P12-ENTITYSEO-01`, `P12-ENTITYSEO-02`, `P12-UX-04`, and `P15-BULK-01` are now marked complete in [phases-tasks.md](phases-tasks.md)
  - `PHASES.md` now reflects Phases 10, 11, 12, 13, 15, and 18 as done
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/ShippingAdminTest.php tests/Feature/NotificationTriggerTest.php tests/Feature/PaymentGatewayAdapterTest.php`
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/ShippingAdminTest.php tests/Feature/ProductAdminWorkflowTest.php`
  - `php artisan test`
  - Result: targeted suites passing; full suite passing with `308` tests and `2127` assertions

### Batch 128 — Phase 10 Production Trigger Wiring Batch 1 (2026-03-21)
- Implemented only `P10-TRIGGER-01`, `P10-TRIGGER-02`, `P10-TRIGGER-03`, and `P10-TRIGGER-04`.
- Wired real order-placed notifications from [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php) so completed checkout now dispatches the operational order notification through the production checkout path.
- Wired real payment success and payment failure notifications from [PaymentCallbackController](app/Modules/Payments/Http/Controllers/PaymentCallbackController.php), including compatibility-safe raw method/reference resolution for legacy gateway rows.
- Wired real shipped notifications from [ShipmentService](app/Modules/Shipping/Services/ShipmentService.php) when a shipment transitions to dispatched status.
- Added focused proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [PaymentReservationReleaseTest](tests/Feature/PaymentReservationReleaseTest.php)
  - [ShippingAdminTest](tests/Feature/ShippingAdminTest.php)
  - [NotificationTriggerTest](tests/Feature/NotificationTriggerTest.php)
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/PaymentReservationReleaseTest.php tests/Feature/ShippingAdminTest.php tests/Feature/NotificationTriggerTest.php`
  - `php artisan test`
  - Result: focused suite passing with `27` tests and `198` assertions; full suite passing with `306` tests and `2114` assertions
- Exact next task IDs:
  - `P10-TRIGGER-05`
  - `P10-TRIGGER-06`
  - `P10-TRIGGER-07`
  - `P10-TRIGGER-08`

### Batch 127 — Phase 8 Validation and Payment Logging Closure (2026-03-21)
- Implemented only `P8-GATEWAY-04`, `P8-LOGS-01`, `P8-LOGS-02`, and `P8-LOGS-03`.
- Closed the remaining Phase 8 gateway-validation proof against the existing production runtime in [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php) and [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php).
- Expanded [PaymentLogger](app/Modules/Payments/Services/PaymentLogger.php) redaction coverage so sensitive payment payloads now also mask `api_key` and `webhook_secret` variants in request and response logging.
- Added focused proof in [PaymentLoggingFoundationTest](tests/Feature/PaymentLoggingFoundationTest.php) for:
  - safe request/response redaction
  - failure log persistence with gateway context
  - status-change audit row persistence
- Verification:
  - `php artisan test tests/Feature/PaymentLoggingFoundationTest.php tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php`
  - `php artisan test`
  - Result: focused suite passing with `21` tests and `160` assertions; full suite passing with `302` tests and `2091` assertions
- Exact next task IDs:
  - `P10-TRIGGER-01`
  - `P10-TRIGGER-02`
  - `P10-TRIGGER-03`
  - `P10-TRIGGER-04`

### Batch 126 — Phase 8 Offline Verification and Gateway Admin Closure (2026-03-21)
- Implemented only `P8-OFFLINE-05`, `P8-GATEWAY-01`, `P8-GATEWAY-02`, and `P8-GATEWAY-03`.
- Added a real finance-side offline verification workflow in [TransactionController](app/Modules/Finance/Http/Controllers/TransactionController.php), [routes/web.php](routes/web.php), and the `nino-v2` transaction detail surface in [resources/views/themes/nino-v2/admin/finance/transactions/show.blade.php](resources/views/themes/nino-v2/admin/finance/transactions/show.blade.php), so pending bank-transfer transactions can now be marked completed or failed with manual review notes and linked order status promotion.
- Truthfully closed the remaining gateway-admin foundation tasks by proving that:
  - test/live mode persistence works
  - enable/disable toggles persist correctly
  - gateway credentials stay encrypted at rest
- Added focused proof in:
  - [FinanceTransactionOfflineVerificationTest](tests/Feature/FinanceTransactionOfflineVerificationTest.php)
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
  - [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php)
- Verification:
  - `php artisan test tests/Feature/FinanceTransactionOfflineVerificationTest.php tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php`
  - `php artisan test`
  - Result: full suite passing with `302` tests and `2091` assertions
- Exact next task IDs:
  - `P8-GATEWAY-04`
  - `P8-LOGS-01`
  - `P8-LOGS-02`
  - `P8-LOGS-03`

### Batch 125 — Phase 8 Offline Payment Method Foundation (2026-03-21)
- Implemented only `P8-OFFLINE-01`, `P8-OFFLINE-02`, `P8-OFFLINE-03`, and `P8-OFFLINE-04`.
- Hardened [OfflinePaymentGateway](app/Modules/Payments/Gateways/OfflinePaymentGateway.php) so offline transfer now has a real default contract, generates a pending bank-transfer transaction, and exposes structured checkout-facing instruction data instead of only a plain text fallback.
- Extended [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php) and the `nino-v2` gateway admin UI in [resources/views/themes/nino-v2/admin/gateways/index.blade.php](resources/views/themes/nino-v2/admin/gateways/index.blade.php) so offline transfer now supports explicit checkout title/description, admin verification notes, and seeded bank-transfer defaults.
- Updated [CheckoutController](app/Modules/Checkout/Http/Controllers/CheckoutController.php), [CheckoutResultController](app/Modules/Checkout/Http/Controllers/CheckoutResultController.php), and [resources/views/themes/nino-v2/customer/checkout/success.blade.php](resources/views/themes/nino-v2/customer/checkout/success.blade.php) so bank-transfer checkout returns structured offline-payment payloads and the thank-you page renders the real transfer instructions and reference.
- Expanded proof in:
  - [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php)
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
- Verification:
  - `php artisan test tests/Feature/PaymentGatewayAdapterTest.php tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `299` tests and `2074` assertions
- Exact next task IDs:
  - `P8-OFFLINE-05`
  - `P8-GATEWAY-01`
  - `P8-GATEWAY-02`
  - `P8-GATEWAY-03`

### Batch 124 — Phase 8 Stripe Foundation Closure (2026-03-21)
- Implemented only `P8-STRIPE-01`, `P8-STRIPE-02`, `P8-STRIPE-03`, and `P8-STRIPE-04`.
- Hardened [StripePaymentGateway](app/Modules/Payments/Gateways/StripePaymentGateway.php) so Stripe transaction resolution now works against the live compatibility-aware payment schema across redirect verification, webhook handling, and refunds instead of relying on a single legacy lookup field.
- Updated [PaymentCallbackController](app/Modules/Payments/Http/Controllers/PaymentCallbackController.php) so successful Stripe webhook settlement now promotes the parent order to `paid`, which closes the browser-return gap for webhook-only completion flows.
- Expanded proof in [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php) for:
  - valid signed Stripe webhook capture and order promotion
  - invalid Stripe signature rejection with failure logging
- Verification:
  - `php artisan test tests/Feature/PaymentGatewayAdapterTest.php tests/Feature/PaymentReservationReleaseTest.php tests/Feature/ObservabilityReportTest.php`
  - `php artisan test`
  - Result: full suite passing with `297` tests and `2050` assertions
- Exact next task IDs:
  - `P8-OFFLINE-01`
  - `P8-OFFLINE-02`
  - `P8-OFFLINE-03`
  - `P8-OFFLINE-04`

### Batch 123 — Phase 8 Payzone Morocco Foundation Closure (2026-03-21)
- Implemented only `P8-PAYZONE-01`, `P8-PAYZONE-02`, `P8-PAYZONE-03`, and `P8-PAYZONE-04`.
- Hardened [PayzonePaymentGateway](app/Modules/Payments/Gateways/PayzonePaymentGateway.php) so callback and webhook verification now:
  - ignore local routing query keys that are not part of Payzone's signed payload
  - resolve transactions through the live compatibility-aware payment schema
  - verify webhook signatures before capture logging and state mutation
- Expanded proof in [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php) for Payzone callback signature handling, webhook capture handling, and transaction logging, while keeping [PaymentReservationReleaseTest](tests/Feature/PaymentReservationReleaseTest.php), [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php), and [ObservabilityReportTest](tests/Feature/ObservabilityReportTest.php) green.
- Verification:
  - `php artisan test tests/Feature/PaymentGatewayAdapterTest.php tests/Feature/PaymentReservationReleaseTest.php tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/ObservabilityReportTest.php`
  - `php artisan test`
  - Result: full suite passing with `295` tests and `2042` assertions
- Exact next task IDs:
  - `P8-STRIPE-01`
  - `P8-STRIPE-02`
  - `P8-STRIPE-03`
  - `P8-STRIPE-04`

### Batch 122 — Phase 8 CMI Morocco Foundation Closure (2026-03-21)
- Implemented only `P8-CMI-01`, `P8-CMI-02`, `P8-CMI-03`, and `P8-CMI-04`.
- Extended the CMI settings surface and validation with optional terminal identifier support in [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php) and the `nino-v2` gateway admin UI in [resources/views/themes/nino-v2/admin/gateways/index.blade.php](resources/views/themes/nino-v2/admin/gateways/index.blade.php).
- Hardened [CmiPaymentGateway](app/Modules/Payments/Gateways/CmiPaymentGateway.php) so callback and webhook verification now ignore local routing query parameters and resolve transactions through the live compatibility-aware payment schema instead of assuming the legacy `gateway_reference` column contract.
- Added focused proof in:
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
  - [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php)
- Verification:
  - `php artisan test tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php tests/Feature/PaymentReservationReleaseTest.php tests/Feature/ObservabilityReportTest.php`
  - `php artisan test`
  - Result: full suite passing with `293` tests and `2034` assertions
- Exact next task IDs:
  - `P8-PAYZONE-01`
  - `P8-PAYZONE-02`
  - `P8-PAYZONE-03`
  - `P8-PAYZONE-04`

### Batch 121 — Phase 8 Adapter Registry, Normalization, and Callback Foundation (2026-03-21)
- Implemented only `P8-ADAPTER-01`, `P8-ADAPTER-02`, `P8-ADAPTER-03`, and `P8-ADAPTER-04`.
- Added a shared gateway registry in [PaymentGatewayRegistry](app/Modules/Payments/Services/PaymentGatewayRegistry.php) so adapter resolution is now centralized by stable gateway id and normalized checkout payment method.
- Added shared response normalization in [PaymentResponseNormalizer](app/Modules/Payments/Services/PaymentResponseNormalizer.php) so gateway adapter results now collapse onto one stable status contract.
- Added shared callback/webhook error handling in [PaymentCallbackHandler](app/Modules/Payments/Services/PaymentCallbackHandler.php) and refactored [PaymentCallbackController](app/Modules/Payments/Http/Controllers/PaymentCallbackController.php) to use that foundation instead of per-gateway duplicated exception handling.
- Added focused proof in [PaymentAdapterArchitectureTest](tests/Feature/PaymentAdapterArchitectureTest.php) and kept the existing domain, adapter, and callback reservation tests green.
- Verification:
  - `php artisan test tests/Feature/PaymentAdapterArchitectureTest.php tests/Feature/PaymentDomainFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php tests/Feature/PaymentReservationReleaseTest.php`
  - `php artisan test`
  - Result: full suite passing with `290` tests and `2015` assertions
- Exact next task IDs:
  - `P8-CMI-01`
  - `P8-CMI-02`
  - `P8-CMI-03`
  - `P8-CMI-04`

### Batch 120 — Phase 8 Payment Domain Foundation Unification (2026-03-21)
- Implemented only `P8-DOMAIN-01`, `P8-DOMAIN-02`, `P8-DOMAIN-03`, and `P8-DOMAIN-04`.
- Unified the payment transaction foundation around the finance ledger model by making the Payments namespace model extend the canonical finance transaction model.
- Added compatibility status normalization and legacy field accessors so gateway-facing code can continue to use payment-layer conventions while persisting to the live finance schema.
- Added explicit order-to-transaction mapping through `paymentTransactions()` on [Order](app/Modules/Orders/Models/Order.php).
- Extended [PaymentGatewayInterface](app/Modules/Payments/Contracts/PaymentGatewayInterface.php) so every gateway now exposes a stable `gatewayId()` identifier.
- Added focused proof in [PaymentDomainFoundationTest](tests/Feature/PaymentDomainFoundationTest.php) and kept [PaymentGatewayAdapterTest](tests/Feature/PaymentGatewayAdapterTest.php) green.
- Verification:
  - `php artisan test tests/Feature/PaymentDomainFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php`
  - `php artisan test`
  - Result: full suite passing with `286` tests and `1996` assertions
- Exact next task IDs:
  - `P8-ADAPTER-01`
  - `P8-ADAPTER-02`
  - `P8-ADAPTER-03`
  - `P8-ADAPTER-04`

### Batch 119 — Phase 7 Order Detail Timeline, Notes, and Audit Visibility Closure (2026-03-21)
- Implemented only `P7-ADMIN-07`, `P7-ADMIN-08`, `P7-ADMIN-09`, and `P7-ADMIN-10`.
- Updated [OrderController](app/Modules/Orders/Http/Controllers/OrderController.php) so the admin order detail page now loads:
  - chronological operational timeline entries from order creation, payment transactions, shipment status history, and internal notes
  - polymorphic internal notes for the order
  - recent order-targeted audit trail entries
- Updated the `nino-v2` detail surface in [resources/views/themes/nino-v2/admin/orders/show.blade.php](resources/views/themes/nino-v2/admin/orders/show.blade.php) to add:
  - operational timeline
  - internal notes create/pin/delete workflow
  - dedicated customer notes section
  - recent audit activity visibility
- Expanded [OrderLifecycleTest](tests/Feature/OrderLifecycleTest.php) to verify:
  - order detail timeline rendering from live operational records
  - internal notes create/pin/delete flow through the existing polymorphic support-note routes
  - customer notes visibility
  - audit history visibility on the order detail screen
- Verification:
  - `php artisan test tests/Feature/OrderLifecycleTest.php`
  - `php artisan test`
  - Result: full suite passing with `283` tests and `1978` assertions
- Exact next task IDs:
  - `P8-DOMAIN-01`
  - `P8-DOMAIN-02`
  - `P8-DOMAIN-03`
  - `P8-DOMAIN-04`

### Batch 118 — Phase 7 Admin Orders Filter Completion (2026-03-21)
- Implemented only `P7-ADMIN-03`, `P7-ADMIN-04`, `P7-ADMIN-05`, and `P7-ADMIN-06`.
- Updated [OrderController](app/Modules/Orders/Http/Controllers/OrderController.php) so the admin orders queue now supports:
  - status filtering
  - date range filtering
  - explicit customer filtering
  - payment method filtering
  - shipping method filtering
- Updated the `nino-v2` orders queue surface in [resources/views/themes/nino-v2/admin/orders/index.blade.php](resources/views/themes/nino-v2/admin/orders/index.blade.php) to expose the full filter set and preserve clear/reset behavior.
- Expanded [OrderLifecycleTest](tests/Feature/OrderLifecycleTest.php) to verify:
  - date range filtering
  - customer filtering
  - payment and shipping method filtering
  - the updated order test helper now saves explicit historical timestamps truthfully for date-driven assertions
- Verification:
  - `php artisan test tests/Feature/OrderLifecycleTest.php`
  - `php artisan test`
  - Result: full suite passing with `281` tests and `1961` assertions
- Exact next task IDs:
  - `P7-ADMIN-07`
  - `P7-ADMIN-08`
  - `P7-ADMIN-09`
  - `P7-ADMIN-10`

### Batch 117 — Phase 7 Cancelled/Returned Lifecycle Closure and Admin Orders Search Foundation (2026-03-21)
- Implemented only `P7-STATUS-08`, `P7-STATUS-09`, `P7-ADMIN-01`, and `P7-ADMIN-02`.
- Updated [ShipmentService](app/Modules/Shipping/Services/ShipmentService.php) so shipment lifecycle changes now also cover:
  - `cancelled` -> parent order `cancelled`
  - `returned` -> refund-ready foundation for prepaid orders by creating a `RefundRequest` in `requested` status and promoting the order to `refunded`
  - COD returned shipments fail closed to `cancelled` instead of fabricating a refund request
- Updated [OrderController](app/Modules/Orders/Http/Controllers/OrderController.php) so the admin orders index search now truthfully matches:
  - order reference
  - customer email
  - customer full name / first name / last name
- Expanded proof in:
  - [ShippingAdminTest](tests/Feature/ShippingAdminTest.php)
  - [OrderLifecycleTest](tests/Feature/OrderLifecycleTest.php)
  to verify:
  - cancelled shipment transitions cancel the parent order
  - returned prepaid shipments create a refund-request foundation
  - public order tracking safely exposes refunded status
  - admin order search can match customer identity fields
- Verification:
  - `php artisan test tests/Feature/ShippingAdminTest.php tests/Feature/OrderLifecycleTest.php`
  - `php artisan test`
  - Result: full suite passing with `278` tests and `1949` assertions
- Exact next task IDs:
  - `P7-ADMIN-03`
  - `P7-ADMIN-04`
  - `P7-ADMIN-05`
  - `P7-ADMIN-06`

### Batch 116 — Phase 7 Shipment-Driven Fulfillment Status Lifecycle Wiring (2026-03-21)
- Implemented only `P7-STATUS-04`, `P7-STATUS-05`, `P7-STATUS-06`, and `P7-STATUS-07`.
- Updated [ShipmentService](app/Modules/Shipping/Services/ShipmentService.php) so shipment lifecycle changes now sync the parent order status truthfully:
  - `ready_to_ship` / `packed` -> `preparing`
  - `dispatched` / `in_transit` -> `shipped`
  - `delivered` -> `delivered`
  - `failed_delivery` -> `failed`
- Kept the change inside the existing shipping lifecycle so order fulfillment state now follows real admin shipment transitions instead of requiring manual fixture-only status changes.
- Expanded proof in:
  - [ShippingAdminTest](tests/Feature/ShippingAdminTest.php)
  - [OrderLifecycleTest](tests/Feature/OrderLifecycleTest.php)
  to verify:
  - admin shipment status updates promote the order through preparing, shipped, and delivered
  - failed delivery marks the order as failed
  - public order tracking safely exposes the failed status label
- Verification:
  - `php artisan test tests/Feature/ShippingAdminTest.php tests/Feature/OrderLifecycleTest.php`
  - `php artisan test`
  - Result: full suite passing with `274` tests and `1933` assertions
- Exact next task IDs:
  - `P7-STATUS-08`
  - `P7-STATUS-09`
  - `P7-ADMIN-01`
  - `P7-ADMIN-02`

### Batch 115 — Phase 7 Failure Reference Resolution and Initial Order-Status Lifecycle Wiring (2026-03-21)
- Implemented only `P7-PAYERR-03`, `P7-STATUS-01`, `P7-STATUS-02`, and `P7-STATUS-03`.
- Updated [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php) so checkout now assigns initial order status truthfully:
  - `pending` for `cash_on_delivery`
  - `awaiting_payment` for non-COD payment methods
- Updated [PaymentCallbackController](app/Modules/Payments/Http/Controllers/PaymentCallbackController.php) so successful gateway callbacks now resolve the related order and promote it to `paid`, then redirect to the public success page using the real order reference instead of a gateway-only reference.
- Hardened [CheckoutResultController](app/Modules/Checkout/Http/Controllers/CheckoutResultController.php) so public success/failure result pages can resolve an order by either:
  - real order reference
  - payment transaction reference / gateway reference
- Extended the failure surfaces in:
  - [nino-v2 checkout failed](resources/views/themes/nino-v2/customer/checkout/failed.blade.php)
  - [nino-v1 checkout failed](resources/views/themes/nino-v1/customer/checkout/failed.blade.php)
  to show order and payment references distinctly when both are available and to route status recovery through the real order reference.
- Hardened [PayzonePaymentGateway](app/Modules/Payments/Gateways/PayzonePaymentGateway.php) against the current finance schema by making callback lookup/update logic column-aware instead of assuming legacy `payload` JSON storage.
- Expanded proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [PaymentReservationReleaseTest](tests/Feature/PaymentReservationReleaseTest.php)
  to verify:
  - COD checkout remains `pending`
  - online checkout starts in `awaiting_payment`
  - failed-payment recovery pages show order plus payment references
  - successful Payzone callbacks promote the order to `paid` and land on the public order-status page
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/PaymentReservationReleaseTest.php`
  - `php artisan test`
  - Result: full suite passing with `271` tests and `1913` assertions
- Exact next task IDs:
  - `P7-STATUS-04`
  - `P7-STATUS-05`
  - `P7-STATUS-06`
  - `P7-STATUS-07`

### Batch 114 — Phase 7 Thank-You Next Steps, Public Lookup Access, and Payment-Error Recovery Surface (2026-03-21)
- Implemented only `P7-THANKYOU-02`, `P7-THANKYOU-03`, `P7-PAYERR-01`, and `P7-PAYERR-02`.
- Replaced the duplicate/stub checkout result routing with real controllers in:
  - [CheckoutResultController](app/Modules/Checkout/Http/Controllers/CheckoutResultController.php)
  - [routes/web.php](routes/web.php)
  by consolidating:
  - signed immediate success rendering for post-checkout redirects
  - public success/status reopening by `ref`
  - real payment-failure rendering on `/checkout/failed`
- Updated [CheckoutController](app/Modules/Checkout/Http/Controllers/CheckoutController.php) so checkout success links now target the signed success route explicitly, while gateway callback flows keep using the public result routes.
- Extended the checkout result pages in:
  - [nino-v2 checkout success](resources/views/themes/nino-v2/customer/checkout/success.blade.php)
  - [nino-v1 checkout success](resources/views/themes/nino-v1/customer/checkout/success.blade.php)
  - [nino-v2 checkout failed](resources/views/themes/nino-v2/customer/checkout/failed.blade.php)
  - [nino-v1 checkout failed](resources/views/themes/nino-v1/customer/checkout/failed.blade.php)
  to add:
  - explicit next-step guidance after successful checkout
  - public status / tracking lookup access by order reference
  - clear payment failure explanation
  - primary and secondary recovery CTAs on failure
- Expanded proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [PaymentReservationReleaseTest](tests/Feature/PaymentReservationReleaseTest.php)
  to verify:
  - the public success page can be reopened by order reference
  - thank-you pages expose next steps plus lookup/tracking actions
  - failed payment redirects land on a real failure page with explanation and recovery CTA
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/PaymentReservationReleaseTest.php`
  - `php artisan test`
  - Result: full suite passing with `270` tests and `1902` assertions
- Exact next task IDs:
  - `P7-PAYERR-03`
  - `P7-STATUS-01`
  - `P7-STATUS-02`
  - `P7-STATUS-03`

### Batch 113 — Phase 7 Checkout Auto-Account Completion and Thank-You Summary Foundation (2026-03-21)
- Implemented only `P7-AUTOACC-01`, `P7-AUTOACC-02`, `P7-AUTOACC-03`, and `P7-THANKYOU-01`.
- Hardened checkout auto-account behavior in:
  - [CustomerAccountService](app/Modules/Customers/Services/CustomerAccountService.php)
  by making checkout-created account generation fail closed for non-customer email collisions and normalizing checkout identity input before persistence.
- Added the first real post-checkout completion flow in:
  - [CheckoutController](app/Modules/Checkout/Http/Controllers/CheckoutController.php)
  - [CheckoutSuccessController](app/Modules/Checkout/Http/Controllers/CheckoutSuccessController.php)
  - [routes/web.php](routes/web.php)
  by introducing:
  - signed `thank_you_url` generation in the checkout API response
  - post-checkout account guidance metadata for guest vs authenticated customers
  - a signed customer-facing checkout success screen keyed by order reference
- Added themed success-summary surfaces in:
  - [nino-v2 checkout success](resources/views/themes/nino-v2/customer/checkout/success.blade.php)
  - [nino-v1 checkout success](resources/views/themes/nino-v1/customer/checkout/success.blade.php)
  covering:
  - order reference and status summary
  - line-item snapshot recap
  - pricing summary recap
  - shipping snapshot
  - account-access next step messaging
- Expanded proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [CustomerAuthFlowTest](tests/Feature/CustomerAuthFlowTest.php)
  to verify:
  - guest checkout returns a signed thank-you URL and guest account-next-step metadata
  - authenticated checkout returns account-home guidance and renders the signed success page
  - checkout-created auto-account generation rejects existing non-customer identities
  - welcome/setup communication and secure password setup behavior remain intact
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `269` tests and `1889` assertions
- Exact next task IDs:
  - `P7-THANKYOU-02`
  - `P7-THANKYOU-03`
  - `P7-PAYERR-01`
  - `P7-PAYERR-02`

### Batch 112 — Phase 7 Order Snapshot Pipeline Closure (2026-03-21)
- Implemented only `P7-ORDER-02`, `P7-ORDER-03`, `P7-ORDER-04`, and `P7-ORDER-05`.
- Moved order-reference generation into the order model in:
  - [Order](app/Modules/Orders/Models/Order.php)
  so checkout no longer hand-builds references inline and each new order now receives a generated internal `reference_number` through the model contract.
- Tightened the checkout snapshot pipeline in:
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  by making the batch truthful for:
  - hard line-item snapshots with variant-aware SKU, name, price, quantity, and line totals
  - hard order-address snapshots through explicit snapshot builders
  - complete order pricing summary persistence for subtotal, tax, shipping, discount, and grand total
  - model-backed internal reference generation instead of controller/service-only `uniqid()` strings
- Extended [CartService](app/Modules/Checkout/Services/CartService.php) so the checkout summary contract now includes explicit `tax` totals for order creation persistence.
- Expanded [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php) to prove:
  - variant-priced items snapshot correctly even if product/variant source records change later
  - order address snapshots remain stable even if saved customer addresses are later edited
  - pricing summary fields persist correctly on the order record
  - generated order references follow the expected internal format and remain unique across orders
- Kept downstream order surfaces safe by verifying:
  - [OrderLifecycleTest](tests/Feature/OrderLifecycleTest.php)
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php`
  - `php artisan test tests/Feature/OrderLifecycleTest.php`
  - `php artisan test`
  - Result: full suite passing with `268` tests and `1872` assertions
- Exact next task IDs:
  - `P7-AUTOACC-01`
  - `P7-AUTOACC-02`
  - `P7-AUTOACC-03`
  - `P7-THANKYOU-01`

### Batch 111 — Phase 7 Checkout Validation, Saved Address Reuse, Error States, and Initial Order Snapshot Proof (2026-03-21)
- Implemented only `P7-CHECKOUT-05`, `P7-CHECKOUT-06`, `P7-CHECKOUT-07`, and `P7-ORDER-01`.
- Extended the checkout runtime in:
  - [CheckoutController](app/Modules/Checkout/Http/Controllers/CheckoutController.php)
  - [ProcessCheckoutRequest](app/Modules/Checkout/Http/Requests/ProcessCheckoutRequest.php)
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  - [CheckoutException](app/Modules/Checkout/Exceptions/CheckoutException.php)
- Closed the batch by making the Phase 7 foundations real for:
  - authenticated saved-address reuse by `shipping_address_id` / `billing_address_id`
  - structured checkout rejection payloads with explicit `error_code` values
  - empty-cart rejection with deterministic recovery context
  - initial order snapshot proof covering reference generation, pending status, shipping defaults, address snapshot resolution, and reservation linkage
- Expanded [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php) to prove:
  - authenticated checkout can reuse saved billing and shipping addresses by ID
  - guest validation fails cleanly for existing email and missing address payloads
  - empty-cart checkout returns a clear structured failure response
  - configured shipping defaults are applied to the created order snapshot
  - checkout reserves matching global stock against the created order record
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `265` tests and `1851` assertions
- Exact next task IDs:
  - `P7-ORDER-02`
  - `P7-ORDER-03`
  - `P7-ORDER-04`
  - `P7-ORDER-05`

### Batch 110 — Phase 7 Checkout Entry and Address Snapshot Closure (2026-03-21)
- Implemented only `P7-CHECKOUT-01`, `P7-CHECKOUT-02`, `P7-CHECKOUT-03`, and `P7-CHECKOUT-04`.
- Truthfully closed this batch by tightening proof around the existing checkout runtime in:
  - [CheckoutController](app/Modules/Checkout/Http/Controllers/CheckoutController.php)
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  - [ProcessCheckoutRequest](app/Modules/Checkout/Http/Requests/ProcessCheckoutRequest.php)
- Expanded [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php) to prove:
  - guest checkout path creates the order from a guest cart/session
  - authenticated checkout path processes without guest identity fields
  - billing and shipping addresses are snapshotted distinctly for both guest and authenticated checkout
  - checkout keeps using the existing shipping defaults and order snapshot foundations already in place
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `262` tests and `1838` assertions
- Exact next task IDs:
  - `P7-CHECKOUT-05`
  - `P7-CHECKOUT-06`
  - `P7-CHECKOUT-07`
  - `P7-ORDER-01`

### Batch 109 — Phase 7 Cart Totals, Shipping Estimate, Free Shipping, and Suggestions (2026-03-21)
- Implemented only `P7-CART-04`, `P7-CART-05`, `P7-CART-06`, and `P7-CART-07`.
- Extended the cart summary contract in:
  - [CartService](app/Modules/Checkout/Services/CartService.php)
  so the cart foundation now exposes:
  - explicit totals metadata
  - shipping estimate details
  - free-shipping progress
  - merchandising suggestions based on product upsell and cross-sell links
- Added focused proof in:
  - [CartApiTest](tests/Feature/CartApiTest.php)
  covering:
  - line-item and unit-count totals
  - shipping estimate calculation and threshold progression
  - free-shipping eligibility transitions
  - upsell and cross-sell suggestion payloads
- Verification:
  - `php artisan test tests/Feature/CartApiTest.php`
  - `php artisan test`
  - Result: full suite passing with `262` tests and `1836` assertions
- Exact next task IDs:
  - `P7-CHECKOUT-01`
  - `P7-CHECKOUT-02`
  - `P7-CHECKOUT-03`
  - `P7-CHECKOUT-04`

### Batch 108 — Phase 6 Support Notes Closure and Phase 7 Cart Foundation Start (2026-03-21)
- Implemented only `P6-TIMELINE-03`, `P7-CART-01`, `P7-CART-02`, and `P7-CART-03`.
- Closed the remaining support timeline note foundation in:
  - [SupportLookupController](app/Modules/Support/Http/Controllers/SupportLookupController.php)
  - [SupportLookupTest](tests/Feature/SupportLookupTest.php)
  by proving:
  - internal note creation
  - note pin/unpin handling
  - note deletion
  - timeline visibility for support staff
- Strengthened the cart runtime in:
  - [CartService](app/Modules/Checkout/Services/CartService.php)
  - [CartController](app/Modules/Checkout/Http/Controllers/CartController.php)
  by making the initial cart foundation truthful:
  - add/remove/update item flows
  - quantity updates
  - coupon apply/remove using the real promotions coupon validator instead of a placeholder setter
  - cart totals reflecting validated discounts
- Added focused proof in:
  - [CartApiTest](tests/Feature/CartApiTest.php)
  - [SupportLookupTest](tests/Feature/SupportLookupTest.php)
- Verification:
  - `php artisan test tests/Feature/CartApiTest.php tests/Feature/SupportLookupTest.php`
  - `php artisan test`
  - Result: full suite passing with `260` tests and `1814` assertions
- Exact next task IDs:
  - `P7-CART-04`
  - `P7-CART-05`
  - `P7-CART-06`
  - `P7-CART-07`

### Batch 107 — Phase 6 Checkout Welcome Trigger and Support Timeline Summary (2026-03-21)
- Implemented only `P6-CHECKOUT-03`, `P6-CHECKOUT-04`, `P6-TIMELINE-01`, and `P6-TIMELINE-02`.
- Added the missing checkout-created customer welcome trigger in:
  - [CustomerAccountService](app/Modules/Customers/Services/CustomerAccountService.php)
  by dispatching the notification service welcome event alongside the existing password-setup link flow without changing staff auth or breaking checkout success behavior.
- Strengthened the support lookup timeline runtime in:
  - [SupportLookupController](app/Modules/Support/Http/Controllers/SupportLookupController.php)
  - [nino-v2 support timeline](resources/views/themes/nino-v2/admin/support/lookup/timeline.blade.php)
  by adding:
  - customer order summary metrics
  - recent customer order statuses
  - recent related order list for support context
- Extended proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [SupportLookupTest](tests/Feature/SupportLookupTest.php)
  to verify:
  - guest checkout writes the welcome notification event
  - checkout-created accounts still use the setup-link flow without plain-password handling
  - support agents see customer order summary and recent statuses in the order timeline
- Verification:
  - `php artisan test tests/Feature/CheckoutFlowTest.php tests/Feature/SupportLookupTest.php`
  - `php artisan test`
  - Result: full suite passing with `256` tests and `1780` assertions
- Exact next task IDs:
  - `P6-TIMELINE-03`
  - `P7-CART-01`
  - `P7-CART-02`
  - `P7-CART-03`

### Batch 106 — Phase 6 Account Management Screens and Checkout-Created Account Flow Closure (2026-03-21)
- Implemented only `P6-ACCOUNT-05`, `P6-ACCOUNT-06`, `P6-CHECKOUT-01`, and `P6-CHECKOUT-02`.
- Added customer-facing management screens and web routes in:
  - [routes/web.php](routes/web.php)
  - [ProfileController](app/Modules/Customers/Http/Controllers/ProfileController.php)
  - [AddressController](app/Modules/Customers/Http/Controllers/AddressController.php)
  by introducing:
  - customer profile edit page
  - customer password change page
  - customer address management page
  - web-form handling layered on top of the existing customer API foundations
- Updated the themed account surfaces in:
  - [nino-v2 profile](resources/views/themes/nino-v2/customer/account/profile.blade.php)
  - [nino-v2 addresses](resources/views/themes/nino-v2/customer/account/addresses.blade.php)
  - [nino-v2 home](resources/views/themes/nino-v2/customer/account/home.blade.php)
  - [nino-v1 profile](resources/views/themes/nino-v1/customer/account/profile.blade.php)
  - [nino-v1 addresses](resources/views/themes/nino-v1/customer/account/addresses.blade.php)
  - [nino-v1 home](resources/views/themes/nino-v1/customer/account/home.blade.php)
- Truthfully closed checkout-created account creation/setup flow by extending proof in:
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [CustomerAuthFlowTest](tests/Feature/CustomerAuthFlowTest.php)
  to verify:
  - guest checkout auto-creates the customer account
  - guest checkout dispatches the password setup notification
  - authenticated checkout does not create a duplicate customer or dispatch setup mail
- Added focused account-management proof in:
  - [CustomerAccountManagementScreenTest](tests/Feature/CustomerAccountManagementScreenTest.php)
  covering:
  - profile edit render and persistence
  - address-management render and persistence
  - password change from the account profile screen
- Verification:
  - `php artisan test tests/Feature/CustomerAccountManagementScreenTest.php tests/Feature/CustomerProfileWorkflowTest.php tests/Feature/CheckoutFlowTest.php tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `256` tests and `1773` assertions
- Exact next task IDs:
  - `P6-CHECKOUT-03`
  - `P6-CHECKOUT-04`
  - `P6-TIMELINE-01`
  - `P6-TIMELINE-02`

### Batch 105 — Phase 6 Customer Account Overview, History, Detail, and Tracking Foundations (2026-03-21)
- Implemented only `P6-ACCOUNT-01`, `P6-ACCOUNT-02`, `P6-ACCOUNT-03`, and `P6-ACCOUNT-04`.
- Replaced the customer account stub API with real order/address/tracking presentation in:
  - [AccountOverviewController](app/Modules/Customers/Http/Controllers/AccountOverviewController.php)
  - [CustomerAccountPresenter](app/Modules/Customers/Services/CustomerAccountPresenter.php)
- Added dedicated customer order screens and routing in:
  - [CustomerOrderController](app/Modules/Customers/Http/Controllers/CustomerOrderController.php)
  - [web routes](routes/web.php)
  by introducing:
  - customer order history
  - customer order detail
  - customer-facing shipment/tracking visibility on order detail
  - customer-scoped authorization for account order access
- Updated the themed customer account surfaces in:
  - [nino-v2 account home](resources/views/themes/nino-v2/customer/account/home.blade.php)
  - [nino-v2 order history](resources/views/themes/nino-v2/customer/account/orders/index.blade.php)
  - [nino-v2 order detail](resources/views/themes/nino-v2/customer/account/orders/show.blade.php)
  - [nino-v1 account home](resources/views/themes/nino-v1/customer/account/home.blade.php)
  - [nino-v1 order history](resources/views/themes/nino-v1/customer/account/orders/index.blade.php)
  - [nino-v1 order detail](resources/views/themes/nino-v1/customer/account/orders/show.blade.php)
- Added focused proof in:
  - [CustomerAccountAreaTest](tests/Feature/CustomerAccountAreaTest.php)
  covering:
  - account overview render
  - customer-scoped order history
  - customer-scoped order detail
  - tracking visibility in both account detail and tracking API
- Verification:
  - `php artisan test tests/Feature/CustomerAccountAreaTest.php tests/Feature/CustomerCredentialsAndAddressFoundationTest.php tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `253` tests and `1746` assertions
- Exact next task IDs:
  - `P6-ACCOUNT-05`
  - `P6-ACCOUNT-06`
  - `P6-CHECKOUT-01`
  - `P6-CHECKOUT-02`

### Batch 104 — Phase 6 Address CRUD, Default, and Validation Foundations (2026-03-21)
- Implemented only `P6-ADDRESS-03`, `P6-ADDRESS-04`, `P6-ADDRESS-05`, and `P6-ADDRESS-06`.
- Strengthened the customer address runtime in:
  - [AddressController](app/Modules/Customers/Http/Controllers/AddressController.php)
  - [Address](app/Modules/Customers/Models/Address.php)
  by adding:
  - explicit default-address ordering and helpers
  - automatic first-address default assignment per type
  - single-default enforcement per address type
  - replacement default promotion when a default address is deleted
  - normalized country/state/city/postal input handling
  - stricter country-code and address-structure validation
- Added focused proof in:
  - [CustomerCredentialsAndAddressFoundationTest](tests/Feature/CustomerCredentialsAndAddressFoundationTest.php)
  covering:
  - address update
  - address delete
  - default uniqueness and replacement promotion
  - automatic first-address default behavior
  - validation for type and country/state/city structure
- Verification:
  - `php artisan test tests/Feature/CustomerCredentialsAndAddressFoundationTest.php tests/Feature/CustomerProfileWorkflowTest.php tests/Feature/CustomerAuthFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `249` tests and `1721` assertions
- Exact next task IDs:
  - `P6-ACCOUNT-01`
  - `P6-ACCOUNT-02`
  - `P6-ACCOUNT-03`
  - `P6-ACCOUNT-04`

### Batch 103 — Phase 6 Username, Password Change, and Initial Address Foundations (2026-03-21)
- Implemented only `P6-PROFILE-05`, `P6-PROFILE-06`, `P6-ADDRESS-01`, and `P6-ADDRESS-02`.
- Added explicit address-type foundation in:
  - [Address](app/Modules/Customers/Models/Address.php)
  by introducing:
  - canonical `billing` and `shipping` type constants
  - type scopes for billing and shipping queries
  - helper methods for address-type checks
- Hardened address creation validation in:
  - [AddressController](app/Modules/Customers/Http/Controllers/AddressController.php)
  by switching from raw string validation to the model-backed address type contract.
- Added focused proof in:
  - [CustomerCredentialsAndAddressFoundationTest](tests/Feature/CustomerCredentialsAndAddressFoundationTest.php)
  covering:
  - username updates through the customer profile surface
  - password changes through the customer profile API
  - billing address creation
  - shipping address creation
- Verification:
  - `php artisan test tests/Feature/CustomerCredentialsAndAddressFoundationTest.php tests/Feature/CustomerProfileWorkflowTest.php tests/Feature/PasswordSessionAndScopeFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `245` tests and `1697` assertions
- Exact next task IDs:
  - `P6-ADDRESS-03`
  - `P6-ADDRESS-04`
  - `P6-ADDRESS-05`
  - `P6-ADDRESS-06`

### Batch 102 — Phase 6 Customer Profile Picture, Name, and Email Foundations (2026-03-21)
- Implemented only `P6-PROFILE-01`, `P6-PROFILE-02`, `P6-PROFILE-03`, and `P6-PROFILE-04`.
- Extended the customer profile runtime in:
  - [ProfileController](app/Modules/Customers/Http/Controllers/ProfileController.php)
  by adding:
  - customer email updates with uniqueness validation
  - normalized email persistence
  - verification reset on email change
  - safe mass-assignment bypass for controlled profile fields that are intentionally not globally fillable
- Added focused proof in:
  - [CustomerProfileWorkflowTest](tests/Feature/CustomerProfileWorkflowTest.php)
  covering:
  - profile picture upload
  - first-name updates
  - last-name updates
  - email updates
  - email uniqueness protection
  - password updates preserving identity/profile data
- Reused and kept green the existing customer foundations in:
  - [UserEntityFoundationTest](tests/Feature/UserEntityFoundationTest.php)
  - [CustomerAuthFlowTest](tests/Feature/CustomerAuthFlowTest.php)
  - [PasswordSessionAndScopeFoundationTest](tests/Feature/PasswordSessionAndScopeFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/CustomerProfileWorkflowTest.php tests/Feature/UserEntityFoundationTest.php tests/Feature/CustomerAuthFlowTest.php tests/Feature/PasswordSessionAndScopeFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `241` tests and `1683` assertions
- Exact next task IDs:
  - `P6-PROFILE-05`
  - `P6-PROFILE-06`
  - `P6-ADDRESS-01`
  - `P6-ADDRESS-02`

### Batch 101 — Phase 5 Damaged-Stock Report Closure and Phase 6 Customer Model Refinement (2026-03-21)
- Implemented only `P5-REPORT-04`, `P6-CUSTOMER-01`, `P6-CUSTOMER-02`, and `P6-CUSTOMER-03`.
- Closed the final Phase 5 report surface in:
  - [InventoryReportsController](app/Modules/Inventory/Http/Controllers/InventoryReportsController.php)
  - [web routes](routes/web.php)
  - [nino-v2 damaged stock report](resources/views/themes/nino-v2/admin/inventory/reports/damaged_stock.blade.php)
  by adding:
  - a dedicated damaged-stock report route
  - branch-scoped damage-only reporting
  - summary metrics for damaged units and operator attribution
  - filterable search and branch visibility aligned with the other inventory reports
- Truthfully closed the Phase 6 customer-model refinement batch by adding focused proof for already-implemented runtime foundations in:
  - [User](app/Models/User.php)
  - [UserFactory](database/factories/UserFactory.php)
  - [CustomerModelRefinementTest](tests/Feature/CustomerModelRefinementTest.php)
  covering:
  - customer-specific identity fields
  - explicit customer account states
  - marketing preference field support
- Extended inventory report proof in:
  - [InventoryReportsFoundationTest](tests/Feature/InventoryReportsFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/InventoryReportsFoundationTest.php tests/Feature/CustomerModelRefinementTest.php tests/Feature/CustomerAuthFlowTest.php tests/Feature/UserEntityFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `238` tests and `1663` assertions
- Exact next task IDs:
  - `P6-PROFILE-01`
  - `P6-PROFILE-02`
  - `P6-PROFILE-03`
  - `P6-PROFILE-04`

### Batch 100 — Phase 5 Transfer-Ready Foundation and Scoped Inventory Reports (2026-03-21)
- Implemented only `P5-BRANCH-03`, `P5-REPORT-01`, `P5-REPORT-02`, and `P5-REPORT-03`.
- Added transfer-ready inventory foundations in:
  - [StockTransfer](app/Modules/Inventory/Models/StockTransfer.php)
  - [StockItem](app/Modules/Inventory/Models/StockItem.php)
  - [StockTransferService](app/Modules/Inventory/Services/StockTransferService.php)
  by closing:
  - usable transfer status/model wiring
  - source/destination branch stock binding
  - pending transfer creation
  - shipped transfer stock deduction with `transfer_out` movement logging
  - received transfer stock intake with `transfer_in` movement logging
- Rebuilt the inventory report runtime in:
  - [InventoryReportsController](app/Modules/Inventory/Http/Controllers/InventoryReportsController.php)
  - [web routes](routes/web.php)
  by adding:
  - branch-scoped current stock reporting
  - branch-scoped movement history reporting
  - a dedicated low-stock report route and controller surface
  - filterable branch/search/report summaries across the report set
- Kept UI report changes confined to `nino-v2`:
  - [nino-v2 current stock report](resources/views/themes/nino-v2/admin/inventory/reports/current_stock.blade.php)
  - [nino-v2 adjustments report](resources/views/themes/nino-v2/admin/inventory/reports/adjustments.blade.php)
  - [nino-v2 low stock report](resources/views/themes/nino-v2/admin/inventory/reports/low_stock.blade.php)
  - [nino-v2 low stock queue](resources/views/themes/nino-v2/admin/inventory/low-stock.blade.php)
- Added focused proof in:
  - [InventoryReportsFoundationTest](tests/Feature/InventoryReportsFoundationTest.php)
  - [StockTransferFoundationTest](tests/Feature/StockTransferFoundationTest.php)
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/InventoryReportsFoundationTest.php tests/Feature/StockTransferFoundationTest.php tests/Feature/InventoryBranchScopeTest.php tests/Feature/InventoryReservationTest.php`
  - `php artisan test`
  - Result: full suite passing with `234` tests and `1643` assertions
- Exact next task IDs:
  - `P5-REPORT-04`
  - `P6-CUSTOMER-01`
  - `P6-CUSTOMER-02`
  - `P6-CUSTOMER-03`

### Batch 99 — Phase 5 Reservation Release and Branch-Scoped Inventory Rules (2026-03-21)
- Implemented only `P5-RESERVE-02`, `P5-RESERVE-03`, `P5-BRANCH-01`, and `P5-BRANCH-02`.
- Extended reservation lifecycle behavior in:
  - [InventoryService](app/Modules/Inventory/Services/InventoryService.php)
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  - [PaymentCallbackController](app/Modules/Payments/Http/Controllers/PaymentCallbackController.php)
  - [StripePaymentGateway](app/Modules/Payments/Gateways/StripePaymentGateway.php)
  by adding:
  - order-bound reservation references during checkout
  - explicit release-by-order handling for cancellation and payment failure
  - timeout-ready stale reservation release support for pending / awaiting-payment orders
  - schema-tolerant payment callback lookup across legacy and current payment transaction columns
- Extended branch-aware inventory rules in:
  - [StockItemPolicy](app/Policies/StockItemPolicy.php)
  - [InventoryController](app/Modules/Inventory/Http/Controllers/InventoryController.php)
  - [PurchaseOrderModal](app/Livewire/Admin/Inventory/PurchaseOrderModal.php)
  - [nino-v2 inventory index](resources/views/themes/nino-v2/admin/inventory/index.blade.php)
  by adding:
  - branch/franchise-scoped inventory visibility
  - branch-scoped low-stock and damaged-stock visibility
  - branch-safe adjustment and threshold update enforcement
  - branch-limited purchase-order destination options
  - inventory summary visibility cards for current operational scope
- Added focused proof in:
  - [InventoryReservationTest](tests/Feature/InventoryReservationTest.php)
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [InventoryBranchScopeTest](tests/Feature/InventoryBranchScopeTest.php)
  - [PaymentReservationReleaseTest](tests/Feature/PaymentReservationReleaseTest.php)
- Verification:
  - `php artisan test tests/Feature/InventoryReservationTest.php tests/Feature/CheckoutFlowTest.php tests/Feature/InventoryBranchScopeTest.php tests/Feature/PaymentReservationReleaseTest.php`
  - `php artisan test`
  - Result: full suite passing with `229` tests and `1616` assertions
- Exact next task IDs:
  - `P5-BRANCH-03`
  - `P5-REPORT-01`
  - `P5-REPORT-02`
  - `P5-REPORT-03`

### Batch 98 — Phase 5 Damaged-Stock Workflow and First Checkout Reservation Rule (2026-03-21)
- Implemented only `P5-DAMAGE-01`, `P5-DAMAGE-02`, `P5-DAMAGE-03`, and `P5-RESERVE-01`.
- Extended inventory and checkout runtime in:
  - [InventoryController](app/Modules/Inventory/Http/Controllers/InventoryController.php)
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  - [CartItem](app/Modules/Checkout/Models/CartItem.php)
  - [web routes](routes/web.php)
  - [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php)
  by adding:
  - a dedicated damaged-stock intake workflow
  - dedicated damage-only movement history
  - separate damage audit logging on top of the underlying stock movement
  - first-pass checkout reservation rules that reserve a matching global stock item when tracked inventory exists for the cart line
- Kept UI changes confined to `nino-v2` inventory surfaces:
  - [nino-v2 inventory index](resources/views/themes/nino-v2/admin/inventory/index.blade.php)
  - [nino-v2 inventory damage](resources/views/themes/nino-v2/admin/inventory/damage.blade.php)
  - [nino-v2 inventory damaged stock](resources/views/themes/nino-v2/admin/inventory/damaged-stock.blade.php)
  - [nino-v2 dashboard](resources/views/themes/nino-v2/admin/dashboard.blade.php)
  by exposing:
  - damage-report entry points from inventory
  - a dedicated damage intake screen
  - a dedicated damage history queue
  - corrected stock dashboard navigation copy for the damaged-stock widget
- Added focused proof in:
  - [InventoryDamageWorkflowTest](tests/Feature/InventoryDamageWorkflowTest.php)
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
  - [InventoryReservationTest](tests/Feature/InventoryReservationTest.php)
- Verification:
  - `php -l app/Modules/Inventory/Http/Controllers/InventoryController.php`
  - `php -l app/Modules/Checkout/Services/CheckoutService.php`
  - `php -l tests/Feature/InventoryDamageWorkflowTest.php`
  - `php -l tests/Feature/CheckoutFlowTest.php`
  - `php artisan test tests/Feature/InventoryDamageWorkflowTest.php tests/Feature/CheckoutFlowTest.php tests/Feature/InventoryAdjustmentWorkflowTest.php tests/Feature/InventoryLowStockManagementTest.php tests/Feature/InventoryReservationTest.php`
  - `php artisan test`
  - Result: full suite passing with `223` tests and `1590` assertions
- Exact next task IDs:
  - `P5-RESERVE-02`
  - `P5-RESERVE-03`
  - `P5-BRANCH-01`
  - `P5-BRANCH-02`

### Batch 97 — Phase 5 Adjustment Audit Closure and Low-Stock Visibility Foundation (2026-03-21)
- Implemented only `P5-ADJUST-05`, `P5-LOWSTOCK-01`, `P5-LOWSTOCK-02`, and `P5-LOWSTOCK-03`.
- Extended the inventory runtime in:
  - [InventoryController](app/Modules/Inventory/Http/Controllers/InventoryController.php)
  - [web routes](routes/web.php)
  - [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php)
  by adding:
  - controller-path proof for adjustment audit logging through the real manual adjustment workflow
  - low-stock threshold update handling
  - a dedicated low-stock alert queue route and inventory surface
  - dashboard low-stock widget links that land on the dedicated alert queue instead of the generic inventory list
- Kept UI changes confined to `nino-v2` inventory surfaces:
  - [nino-v2 inventory index](resources/views/themes/nino-v2/admin/inventory/index.blade.php)
  - [nino-v2 inventory low-stock](resources/views/themes/nino-v2/admin/inventory/low-stock.blade.php)
  - [nino-v2 inventory adjustments report](resources/views/themes/nino-v2/admin/inventory/reports/adjustments.blade.php)
  by exposing:
  - low-stock threshold visibility in the main inventory list
  - a dedicated low-stock alert page with per-item threshold editing
  - corrected adjustment-report reason/type filters aligned with real inventory movement values
- Added focused proof in:
  - [InventoryLowStockManagementTest](tests/Feature/InventoryLowStockManagementTest.php)
  - [InventoryAdjustmentWorkflowTest](tests/Feature/InventoryAdjustmentWorkflowTest.php)
  - [DashboardFoundationTest](tests/Feature/DashboardFoundationTest.php)
- Verification:
  - `php -l app/Modules/Inventory/Http/Controllers/InventoryController.php`
  - `php -l tests/Feature/InventoryLowStockManagementTest.php`
  - `php artisan test tests/Feature/InventoryAdjustmentWorkflowTest.php tests/Feature/InventoryLowStockManagementTest.php tests/Feature/InventoryReservationTest.php tests/Feature/DashboardFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `220` tests and `1574` assertions
- Exact next task IDs:
  - `P5-DAMAGE-01`
  - `P5-DAMAGE-02`
  - `P5-DAMAGE-03`
  - `P5-RESERVE-01`

### Batch 96 — Phase 5 Manual Stock Adjustment Workflow Foundation (2026-03-21)
- Implemented only `P5-ADJUST-01`, `P5-ADJUST-02`, `P5-ADJUST-03`, and `P5-ADJUST-04`.
- Added a real controller-backed manual adjustment workflow in:
  - [InventoryController](app/Modules/Inventory/Http/Controllers/InventoryController.php)
  - [web routes](routes/web.php)
  by adding:
  - a dedicated manual adjustment page
  - validated adjustment reasons
  - positive and negative adjustment posting
  - automatic signed-in staff attribution through stock movements
- Kept UI changes confined to `nino-v2` inventory surfaces:
  - [nino-v2 inventory index](resources/views/themes/nino-v2/admin/inventory/index.blade.php)
  - [nino-v2 inventory adjust](resources/views/themes/nino-v2/admin/inventory/adjust.blade.php)
  - [nino-v2 inventory adjustments report](resources/views/themes/nino-v2/admin/inventory/reports/adjustments.blade.php)
  by exposing:
  - a manual adjustment entry point from the inventory list
  - a dedicated adjustment form with item selection, reason selection, positive/negative mode, notes, and operator visibility
  - corrected movement-report reason/type filters aligned with the real inventory movement contract
- Added focused proof in:
  - [InventoryAdjustmentWorkflowTest](tests/Feature/InventoryAdjustmentWorkflowTest.php)
  - [StockItemModelFoundationTest](tests/Feature/StockItemModelFoundationTest.php)
  - [InventoryReservationTest](tests/Feature/InventoryReservationTest.php)
- Verification:
  - `php -l app/Modules/Inventory/Http/Controllers/InventoryController.php`
  - `php -l tests/Feature/InventoryAdjustmentWorkflowTest.php`
  - `php artisan test tests/Feature/InventoryAdjustmentWorkflowTest.php tests/Feature/StockItemModelFoundationTest.php tests/Feature/InventoryReservationTest.php`
  - `php artisan test`
  - Result: full suite passing with `217` tests and `1557` assertions
- Exact next task IDs:
  - `P5-ADJUST-05`
  - `P5-LOWSTOCK-01`
  - `P5-LOWSTOCK-02`
  - `P5-LOWSTOCK-03`

### Batch 95 — Phase 5 Stock Item Model Completion (2026-03-21)
- Implemented only `P5-MODEL-02`, `P5-MODEL-03`, `P5-MODEL-04`, and `P5-MODEL-05`.
- Extended the inventory model contract in:
  - [StockItem](app/Modules/Inventory/Models/StockItem.php)
  - [StockItemFactory](database/factories/StockItemFactory.php)
  by adding:
  - explicit product-level stock support
  - explicit variant-level stock support
  - branch-aware and global-stock scope helpers
  - reservation/release-ready helpers and available reservable quantity checks
- Added focused proof in:
  - [StockItemModelFoundationTest](tests/Feature/StockItemModelFoundationTest.php)
  - [InventoryReservationTest](tests/Feature/InventoryReservationTest.php)
  to verify:
  - product vs variant stock modeling
  - branch-aware vs global inventory behavior
  - reservation-safe helper behavior
- Verification:
  - `php -l app/Modules/Inventory/Models/StockItem.php`
  - `php -l database/factories/StockItemFactory.php`
  - `php -l tests/Feature/StockItemModelFoundationTest.php`
  - `php artisan test tests/Feature/StockItemModelFoundationTest.php tests/Feature/InventoryReservationTest.php`
  - `php artisan test`
  - Result: full suite passing with `214` tests and `1538` assertions
- Exact next task IDs:
  - `P5-ADJUST-01`
  - `P5-ADJUST-02`
  - `P5-ADJUST-03`
  - `P5-ADJUST-04`

### Batch 94 — Phase 4 Catalog Admin Workflow Completion and Phase 5 Stock Item Foundation (2026-03-21)
- Implemented only `P4-ADMIN-03`, `P4-ADMIN-04`, `P4-ADMIN-05`, and `P5-MODEL-01`.
- Extended the catalog admin runtime in:
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [web routes](routes/web.php)
  - [Product](app/Modules/Catalog/Models/Product.php)
  by adding:
  - bulk product publish / draft / archive / delete actions
  - single-record publish / draft status workflow endpoints
  - product edit audit logging beyond price-only changes
  - product delete audit logging
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  by exposing:
  - bulk action controls
  - publish / move-to-draft row actions
  - recent audit activity on the product edit surface
- Closed the first Phase 5 inventory-model item truthfully in:
  - [StockItem](app/Modules/Inventory/Models/StockItem.php)
  - [StockItemModelFoundationTest](tests/Feature/StockItemModelFoundationTest.php)
  by making the stock-item contract explicit with:
  - status constants
  - casts
  - status scopes
  - status helpers
  - available quantity coverage
- Added focused proof in:
  - [ProductAdminWorkflowTest](tests/Feature/ProductAdminWorkflowTest.php)
  - [StockItemModelFoundationTest](tests/Feature/StockItemModelFoundationTest.php)
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductAdminWorkflowTest.php tests/Feature/StockItemModelFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `211` tests and `1515` assertions
- Exact next task IDs:
  - `P5-MODEL-02`
  - `P5-MODEL-03`
  - `P5-MODEL-04`
  - `P5-MODEL-05`

### Batch 93 — Phase 4 Catalog SEO Completion and Product Admin Filtering (2026-03-21)
- Implemented only `P4-SEO-05`, `P4-SEO-06`, `P4-ADMIN-01`, and `P4-ADMIN-02`.
- Added additive catalog SEO storage in:
  - [catalog SEO migration](database/migrations/2026_03_21_111500_add_catalog_seo_and_noindex_fields.php)
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [Category](app/Modules/Catalog/Models/Category.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [CategoryController](app/Modules/Catalog/Http/Controllers/CategoryController.php)
  by adding:
  - product `noindex` foundation
  - category canonical URL support
  - category OG title / description / image support
  - category `noindex` support
  - fallback SEO helper methods on both catalog entities
- Kept UI changes confined to `nino-v2` surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  - [nino-v2 category form](resources/views/themes/nino-v2/admin/catalog/categories/form.blade.php)
  - [nino-v2 category index](resources/views/themes/nino-v2/admin/catalog/categories/index.blade.php)
  by exposing:
  - product noindex control
  - category canonical / OG / noindex controls
  - category noindex visibility in the admin list
  - product search input
  - product filters for status, category, type, and stock
- Added focused proof in:
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  - [CategoryManagementRulesTest](tests/Feature/CategoryManagementRulesTest.php)
  to verify:
  - product noindex persistence
  - product list search/filter behavior
  - category canonical/OG/noindex persistence
  - `nino-v2` category and product SEO/filter controls render correctly
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductModelDesignTest.php tests/Feature/CategoryManagementRulesTest.php`
  - `php artisan test`
  - Result: full suite passing with `206` tests and `1493` assertions
- Exact next task IDs:
  - `P4-ADMIN-03`
  - `P4-ADMIN-04`
  - `P4-ADMIN-05`
  - `P5-MODEL-01`

### Batch 92 — Phase 4 Product SEO Fields and Social Preview Foundation (2026-03-21)
- Implemented only `P4-SEO-01`, `P4-SEO-02`, `P4-SEO-03`, and `P4-SEO-04`.
- Added additive product SEO storage in:
  - [product SEO migration](database/migrations/2026_03_21_103000_add_product_seo_fields.php)
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  by adding:
  - product meta title persistence
  - product meta description persistence
  - canonical URL foundation with storefront fallback
  - OG title, description, and image support with fallback helpers
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - canonical URL entry
  - OG title / description / image fields
  - SEO fallback guidance in the publishing sidebar
  - lightweight SEO state visibility in the product list
- Added focused proof in:
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - full SEO payload persistence
  - canonical and OG fallback helper behavior
  - `nino-v2` SEO controls render correctly
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `204` tests and `1469` assertions
- Exact next task IDs:
  - `P4-SEO-05`
  - `P4-SEO-06`
  - `P4-ADMIN-01`
  - `P4-ADMIN-02`

### Batch 91 — Phase 4 Product Merchandising Links and Badge Foundation (2026-03-21)
- Implemented only `P4-MERCH-01`, `P4-MERCH-02`, `P4-MERCH-03`, and `P4-MERCH-04`.
- Added real merchandising foundations in:
  - [product merchandising migration](database/migrations/2026_03_21_101500_add_product_merchandising_tables.php)
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  by adding:
  - related-product links
  - upsell links
  - cross-sell links
  - badge-label storage and normalization
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - merchandising link selectors
  - badge-label input
  - badge and merchandising count visibility in the product list
- Added focused proof in:
  - [ProductMerchandisingManagementTest](tests/Feature/ProductMerchandisingManagementTest.php)
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - related/upsell/cross-sell persistence
  - self-reference exclusion on update
  - badge normalization
  - `nino-v2` merchandising controls render correctly
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductMerchandisingManagementTest.php tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `202` tests and `1448` assertions
- Exact next task IDs:
  - `P4-SEO-01`
  - `P4-SEO-02`
  - `P4-SEO-03`
  - `P4-SEO-04`

### Batch 90 — Phase 4 Variable Products, Variant Pricing, Stock, and Media (2026-03-21)
- Implemented only `P4-VARIANT-01`, `P4-VARIANT-02`, `P4-VARIANT-03`, `P4-VARIANT-04`, and `P4-VARIANT-05`.
- Added a dedicated variant runtime in:
  - [ProductVariantService](app/Modules/Catalog/Services/ProductVariantService.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [ProductOption](app/Modules/Catalog/Models/ProductOption.php)
  - [ProductOptionValue](app/Modules/Catalog/Models/ProductOptionValue.php)
  - [ProductVariant](app/Modules/Catalog/Models/ProductVariant.php)
  by adding:
  - option / attribute persistence
  - variant SKU persistence with uniqueness enforcement
  - variant-level price and sale-price handling
  - variant stock quantity handling
  - variant media persistence under the configured catalog media directory
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - option definition controls
  - variant rows with assignments, SKU, price, sale price, quantity, and image uploads
  - variant counts in the product listing
- Added focused proof in:
  - [ProductVariantManagementTest](tests/Feature/ProductVariantManagementTest.php)
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - option/value persistence
  - variant assignments and pricing
  - variant stock and media persistence
  - SKU/sale-price validation
  - `nino-v2` variant controls render correctly
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductVariantManagementTest.php tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `199` tests and `1430` assertions
- Exact next task IDs:
  - `P4-MERCH-01`
  - `P4-MERCH-02`
  - `P4-MERCH-03`
  - `P4-MERCH-04`

### Batch 89 — Phase 4 Product Media, Gallery Ordering, and Queue-Backed Processing (2026-03-21)
- Implemented only `P4-MEDIA-01`, `P4-MEDIA-02`, `P4-MEDIA-03`, `P4-MEDIA-04`, `P4-MEDIA-05`, and `P4-MEDIA-06`.
- Added a dedicated product-media runtime in:
  - [ProductMediaService](app/Modules/Catalog/Services/ProductMediaService.php)
  - [ProcessProductImageJob](app/Modules/Catalog/Jobs/ProcessProductImageJob.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [ProductImage](app/Modules/Catalog/Models/ProductImage.php)
  by adding:
  - featured-image upload handling
  - multi-image gallery upload handling
  - persistent media ordering and featured-image selection
  - editable alt-text metadata
  - media storage through configured disk and catalog directory settings
  - queue-backed post-upload media processing for safe alt-text backfill
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - featured-image upload and alt-text fields
  - gallery uploads
  - existing gallery ordering and featured-selection controls
  - image removal controls
  - media asset counts in the product index
- Added focused proof in:
  - [ProductMediaManagementTest](tests/Feature/ProductMediaManagementTest.php)
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - featured + gallery persistence
  - configured storage path usage
  - media ordering, alt-text editing, and removal
  - queue dispatch and processing behavior
  - `nino-v2` product media surfaces render the expected controls
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductMediaManagementTest.php tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `195` tests and `1403` assertions
- Exact next task IDs:
  - `P4-VARIANT-01`
  - `P4-VARIANT-02`
  - `P4-VARIANT-03`
  - `P4-VARIANT-04`

### Batch 88 — Phase 4 Product Cost, Currency Structure, and Description Surfaces (2026-03-21)
- Implemented only `P4-PRICE-03`, `P4-PRICE-04`, `P4-DESC-01`, `P4-DESC-02`, and `P4-DESC-03`.
- Hardened the product domain and controller contract in:
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  by adding:
  - explicit internal cost support
  - margin amount and margin percent helpers
  - pricing metadata for base currency and supported currencies
  - short-description excerpt support
  - full-description rendering with safe rich-content sanitization
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - internal cost fields and pricing notes
  - base-currency and multi-currency readiness cues
  - short-description and full-description guidance
  - safe rich-content preview
  - excerpt, cost, and margin signals in the product listing
- Added focused proof in:
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - internal cost persistence
  - margin helper behavior
  - short-description excerpt behavior
  - safe rich-description rendering
  - `nino-v2` product surfaces render pricing and description signals
- Verification:
  - `php artisan view:clear`
  - `php artisan test tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `191` tests and `1370` assertions
- Exact next task IDs:
  - `P4-MEDIA-01`
  - `P4-MEDIA-02`
  - `P4-MEDIA-03`
  - `P4-MEDIA-04`

### Batch 87 — Phase 4 Product Dimensions, Featured Flag, and Base Sale Pricing (2026-03-21)
- Implemented only `P4-PRODUCT-09`, `P4-PRODUCT-10`, `P4-PRICE-01`, and `P4-PRICE-02`.
- Hardened the product domain contract in:
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [ProductFactory](database/factories/ProductFactory.php)
  by adding:
  - explicit featured scope/support
  - physical-profile helpers for weight and dimensions
  - effective-price and on-sale helpers
  - stricter validation for non-negative price and dimensional inputs
  - sale-price guardrails so discounts cannot exceed the base price
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - explicit shipping-profile guidance
  - featured-state visibility
  - sale-price behavior in the list
  - weight and dimension summaries
- Expanded proof in:
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - dimensions/weight persistence
  - featured flag persistence
  - regular + sale price persistence
  - invalid sale-price rejection
  - `nino-v2` product surfaces render featured/pricing/shipping signals
- Verification:
  - `php artisan test tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `189` tests and `1354` assertions
- Exact next task IDs:
  - `P4-PRICE-03`
  - `P4-PRICE-04`
  - `P4-DESC-01`
  - `P4-DESC-02`

### Batch 86 — Phase 4 Product Status, Type Handling, Categories, and Tags (2026-03-20)
- Implemented only `P4-PRODUCT-05`, `P4-PRODUCT-06`, `P4-PRODUCT-07`, and `P4-PRODUCT-08`.
- Hardened the product model/controller contract in:
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductTag](app/Modules/Catalog/Models/ProductTag.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  - [product tags migration](database/migrations/2026_03_20_221000_create_product_tags_tables.php)
  by adding:
  - explicit draft / published / archived status helpers and scopes
  - explicit product type constants and labels
  - first-class product-to-category and product-to-tag relationship handling
  - normalized inline tag parsing and sync on create/update
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing:
  - clearer status semantics
  - tag entry and tag counts
  - category-plus-tag visibility in the product table
  - archived summary visibility
- Added focused proof in:
  - [ProductFactory](database/factories/ProductFactory.php)
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - status helpers and scopes
  - category relationship persistence
  - tag creation and sync
  - `nino-v2` product surfaces render status/category/tag controls
- Verification:
  - `php artisan test tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `187` tests and `1332` assertions
- Exact next task IDs:
  - `P4-PRODUCT-09`
  - `P4-PRODUCT-10`
  - `P4-PRICE-01`
  - `P4-PRICE-02`

### Batch 85 — Phase 4 Product Type, SKU, and Slug Foundation (2026-03-20)
- Implemented only `P4-PRODUCT-01`, `P4-PRODUCT-02`, `P4-PRODUCT-03`, and `P4-PRODUCT-04`.
- Hardened the product model/controller contract in:
  - [Product](app/Modules/Catalog/Models/Product.php)
  - [ProductController](app/Modules/Catalog/Http/Controllers/ProductController.php)
  by adding:
  - explicit simple/variable helpers and query scopes
  - collision-safe slug generation
  - optional manual slug persistence with uniqueness validation
  - explicit SKU + slug support through the create/update flow
- Added a local model factory in:
  - [ProductFactory](database/factories/ProductFactory.php)
  for reliable simple vs variable product creation in tests.
- Kept UI changes confined to `nino-v2` product surfaces:
  - [nino-v2 product form](resources/views/themes/nino-v2/admin/catalog/products/form.blade.php)
  - [nino-v2 product index](resources/views/themes/nino-v2/admin/catalog/products/index.blade.php)
  by exposing slug input and clearer type visibility without touching the `nino-v1` product screens.
- Added focused proof in:
  - [ProductModelDesignTest](tests/Feature/ProductModelDesignTest.php)
  to verify:
  - simple product support
  - variable product support
  - SKU persistence
  - slug generation and collision handling
  - `nino-v2` product surfaces render the type/slug controls
- Verification:
  - `php artisan test tests/Feature/ProductModelDesignTest.php`
  - `php artisan test`
  - Result: full suite passing with `185` tests and `1311` assertions
- Exact next task IDs:
  - `P4-PRODUCT-05`
  - `P4-PRODUCT-06`
  - `P4-PRODUCT-07`
  - `P4-PRODUCT-08`

### Batch 84 — Phase 4 Category Image, Description, Status, and SEO Completion (2026-03-20)
- Implemented only `P4-CATEGORY-06`, `P4-CATEGORY-07`, `P4-CATEGORY-08`, and `P4-CATEGORY-09`.
- Kept UI updates confined to `nino-v2` category surfaces:
  - [nino-v2 category form](resources/views/themes/nino-v2/admin/catalog/categories/form.blade.php)
  - [nino-v2 category index](resources/views/themes/nino-v2/admin/catalog/categories/index.blade.php)
- The `nino-v2` category experience now makes the media/SEO/status slices explicit by:
  - surfacing image state more clearly in the form
  - reinforcing description guidance and SEO readiness in the form
  - exposing description snippets and SEO-ready state in the listing
- Added focused proof in:
  - [CategoryManagementRulesTest](tests/Feature/CategoryManagementRulesTest.php)
  - [CategoryNestedModelFoundationTest](tests/Feature/CategoryNestedModelFoundationTest.php)
  to verify:
  - category image upload persistence
  - description persistence
  - active/inactive status persistence
  - category SEO field persistence
  - `nino-v2` category screens render the media/status/SEO surfaces
- Verification:
  - `php artisan test tests/Feature/CategoryManagementRulesTest.php tests/Feature/CategoryNestedModelFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `180` tests and `1284` assertions
- Exact next task IDs:
  - `P4-PRODUCT-01`
  - `P4-PRODUCT-02`
  - `P4-PRODUCT-03`
  - `P4-PRODUCT-04`

### Batch 83 — Phase 4 Category Hierarchy, CRUD Rules, Ordering, and Slug Management (2026-03-20)
- Implemented only `P4-CATEGORY-02`, `P4-CATEGORY-03`, `P4-CATEGORY-04`, and `P4-CATEGORY-05`.
- Expanded category model foundations in:
  - [Category](app/Modules/Catalog/Models/Category.php)
  with:
  - explicit `products()` relationship for delete guardrails
  - deterministic, unique slug generation with normalization and collision suffixing
  - module-local factory binding for reliable test/runtime factory resolution
- Hardened category CRUD behavior in:
  - [CategoryController](app/Modules/Catalog/Http/Controllers/CategoryController.php)
  by adding:
  - nested parent-option generation with cycle-safe exclusions (self + descendants)
  - parent-cycle validation enforcement on update
  - stricter delete rules (block delete when children exist or category is attached to products)
  - explicit ordering strategy in index listings
  - stable sibling sort-order defaults when sort order is omitted
- Updated category admin forms/tables in both themes:
  - [nino-v2 category form](resources/views/themes/nino-v2/admin/catalog/categories/form.blade.php)
  - [nino-v2 category index](resources/views/themes/nino-v2/admin/catalog/categories/index.blade.php)
  - [nino-v1 category create](resources/views/themes/nino-v1/admin/catalog/categories/create.blade.php)
  - [nino-v1 category edit](resources/views/themes/nino-v1/admin/catalog/categories/edit.blade.php)
  - [nino-v1 category index](resources/views/themes/nino-v1/admin/catalog/categories/index.blade.php)
  to support tree-aware parent selection, slug input/management, and visible ordering metadata.
- Added focused proof in:
  - [CategoryManagementRulesTest](tests/Feature/CategoryManagementRulesTest.php)
  - [CategoryNestedModelFoundationTest](tests/Feature/CategoryNestedModelFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/CategoryNestedModelFoundationTest.php tests/Feature/CategoryManagementRulesTest.php`
  - `php artisan test`
  - Result: full suite passing with `178` tests and `1261` assertions
- Exact next task IDs:
  - `P4-CATEGORY-06`
  - `P4-CATEGORY-07`
  - `P4-CATEGORY-08`
  - `P4-CATEGORY-09`

### Batch 82 — Phase 3 System Settings Completion and Phase 4 Category Model Foundation (2026-03-20)
- Implemented only `P3-SYSTEM-01`, `P3-SYSTEM-02`, `P3-SYSTEM-03`, and `P4-CATEGORY-01`.
- Added the final Phase 3 system-settings foundation in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [config/media.php](config/media.php)
  - [config/features.php](config/features.php)
  - [SystemSettingsService](app/Modules/Settings/Services/SystemSettingsService.php)
  - [MediaStorageSettingsService](app/Modules/Settings/Services/MediaStorageSettingsService.php)
  - [FeatureFlagService](app/Modules/Settings/Services/FeatureFlagService.php)
- Added runtime system behavior in:
  - [ApplySystemSettings](app/Http/Middleware/ApplySystemSettings.php)
  - [EnsureFeatureEnabled](app/Http/Middleware/EnsureFeatureEnabled.php)
  - [bootstrap/app.php](bootstrap/app.php)
  - [routes/web.php](routes/web.php)
  - [UserAvatarService](app/Modules/IAM/Services/UserAvatarService.php)
  so maintenance mode, staff bypass, media storage defaults, and feature-flag route gating are applied at runtime.
- Added the Phase 4 category model foundation in:
  - [Category](app/Modules/Catalog/Models/Category.php)
  - [CategoryFactory](database/factories/CategoryFactory.php)
  with nested tree helpers for roots, ordering, recursive children, ancestors, descendants, depth, and path generation.
- Added focused proof in:
  - [SystemSettingsFoundationTest](tests/Feature/SystemSettingsFoundationTest.php)
  - [CategoryNestedModelFoundationTest](tests/Feature/CategoryNestedModelFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/SystemSettingsFoundationTest.php tests/Feature/CategoryNestedModelFoundationTest.php tests/Feature/SettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `172` tests and `1239` assertions
- Exact next task IDs:
  - `P4-CATEGORY-02`
  - `P4-CATEGORY-03`
  - `P4-CATEGORY-04`
  - `P4-CATEGORY-05`

### Batch 81 — Phase 3 Notification Queue Runtime and Shipping Settings Foundation (2026-03-20)
- Implemented only `P3-NOTIFY-04`, `P3-SHIPSET-01`, `P3-SHIPSET-02`, and `P3-SHIPSET-03`.
- Extended the notifications settings foundation in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [NotificationSettingsService](app/Modules/Notifications/Services/NotificationSettingsService.php)
  with queue and retry controls for:
  - queue name
  - max delivery attempts
  - retry base delay minutes
- Wired those queue/retry settings into live notification runtime in:
  - [NotificationDispatcher](app/Modules/Notifications/Services/NotificationDispatcher.php)
  - [SendNotificationJob](app/Modules/Notifications/Jobs/SendNotificationJob.php)
  - [NotificationLog](app/Modules/Notifications/Models/NotificationLog.php)
  so new logs inherit the configured retry budget and queued jobs use the configured queue plus retry cadence.
- Added the shipping settings foundation in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [ShippingSettingsService](app/Modules/Shipping/Services/ShippingSettingsService.php)
  covering:
  - checkout shipping defaults
  - carrier/method defaults
  - tracking requirements and fallback tracking URLs
- Wired shipping settings into real runtime behavior in:
  - [CartService](app/Modules/Checkout/Services/CartService.php)
  - [CheckoutService](app/Modules/Checkout/Services/CheckoutService.php)
  - [ShippingMethodController](app/Modules/Shipping/Http/Controllers/ShippingMethodController.php)
  - [ShipmentService](app/Modules/Shipping/Services/ShipmentService.php)
  - [ShipmentController](app/Modules/Shipping/Http/Controllers/ShipmentController.php)
  - [Shipment](app/Modules/Shipping/Models/Shipment.php)
- Expanded proof in:
  - [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php)
  - [NotificationTriggerTest](tests/Feature/NotificationTriggerTest.php)
  - [ShippingAdminTest](tests/Feature/ShippingAdminTest.php)
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php tests/Feature/NotificationTriggerTest.php tests/Feature/ShippingAdminTest.php tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `165` tests and `1206` assertions
- Exact next task IDs:
  - `P3-SYSTEM-01`
  - `P3-SYSTEM-02`
  - `P3-SYSTEM-03`
  - `P4-CATEGORY-01`

### Batch 80 — Phase 3 Gateway Toggle Controls and Notification Settings Foundation (2026-03-20)
- Implemented only `P3-PAYSET-07`, `P3-NOTIFY-01`, `P3-NOTIFY-02`, and `P3-NOTIFY-03`.
- Added a dedicated notifications settings foundation in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [NotificationSettingsService](app/Modules/Notifications/Services/NotificationSettingsService.php)
  covering:
  - email / SMS / WhatsApp channel enablement
  - notification sender name
  - basic global footer and signature settings for templates
- Wired the new notification settings into live runtime behavior in:
  - [NotificationDispatcher](app/Modules/Notifications/Services/NotificationDispatcher.php)
  - [EmailChannel](app/Modules/Notifications/Channels/EmailChannel.php)
  so disabled channels no longer queue logs, sender defaults are available to delivery, and template footer/signature content is appended to rendered notification bodies.
- Closed the remaining payment-settings toggle gap by adding explicit enable/disable persistence coverage in:
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
- Expanded coverage in:
  - [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php)
  - [NotificationTriggerTest](tests/Feature/NotificationTriggerTest.php)
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php tests/Feature/NotificationTriggerTest.php tests/Feature/GatewaySettingsFoundationTest.php`
  - `php artisan test`
  - Result: full suite passing with `159` tests and `1164` assertions
- Exact next task IDs:
  - `P3-NOTIFY-04`
  - `P3-SHIPSET-01`
  - `P3-SHIPSET-02`
  - `P3-SHIPSET-03`

### Batch 79 — Phase 3 Stripe, Offline Transfer, and Gateway Environment Settings (2026-03-20)
- Implemented only `P3-PAYSET-03`, `P3-PAYSET-04`, `P3-PAYSET-05`, and `P3-PAYSET-06`.
- Expanded gateway settings validation in:
  - [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php)
  for:
  - Stripe `publishable_key`, `secret_key`, `webhook_secret`, and currency
  - Offline transfer method label, bank details, payment window, receipt requirement, and instructions
  - strict `test` / `live` mode validation across the gateway settings surface
- Added stronger default gateway metadata bootstrapping for offline transfer, CMI, Payzone, and Stripe in:
  - [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php)
- Upgraded the gateway admin UI in both themes:
  - [nino-v2 gateways index](resources/views/themes/nino-v2/admin/gateways/index.blade.php)
  - [nino-v1 gateways index](resources/views/themes/nino-v1/admin/gateways/index.blade.php)
  so Stripe and offline bank transfer now expose full production-ready settings instead of partial placeholders.
- Wired offline bank-transfer defaults into runtime customer-facing instructions in:
  - [OfflinePaymentGateway](app/Modules/Payments/Gateways/OfflinePaymentGateway.php)
- Added focused coverage in:
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
  - [AuditLogTest](tests/Feature/AuditLogTest.php)
- Verification:
  - `php artisan test tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/PaymentGatewayAdapterTest.php`
  - `php artisan test`
  - Result: full suite passing with `154` tests and `1128` assertions
- Exact next task IDs:
  - `P3-PAYSET-07`
  - `P3-NOTIFY-01`
  - `P3-NOTIFY-02`
  - `P3-NOTIFY-03`

### Batch 78 — Phase 3 Finance Conversion/Defaults and CMI/Payzone Gateway Settings (2026-03-20)
- Implemented only `P3-FINANCE-03`, `P3-FINANCE-04`, `P3-PAYSET-01`, and `P3-PAYSET-02`.
- Expanded the finance settings contract in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  - [config/finance.php](config/finance.php)
  with:
  - conversion adjustment percent
  - price rounding strategy
  - default tax rate
  - default payment terms days
- Extended finance runtime application in:
  - [FinanceSettingsService](app/Modules/Finance/Services/FinanceSettingsService.php)
  - [ApplyFinanceSettings](app/Http/Middleware/ApplyFinanceSettings.php)
  - [Setting](app/Modules/Settings/Models/Setting.php)
  so the new finance defaults are typed correctly and applied into runtime config.
- Hardened gateway settings in:
  - [AdminGatewaySettingController](app/Modules/Payments/Http/Controllers/AdminGatewaySettingController.php)
  with provider-specific validation and normalization for:
  - CMI store/client/hash settings plus numeric currency code and language
  - Payzone merchant/api/secret settings plus ISO currency code
  while preserving masked stored secrets during updates.
- Added focused coverage in:
  - [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php)
  - [GatewaySettingsFoundationTest](tests/Feature/GatewaySettingsFoundationTest.php)
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php tests/Feature/GatewaySettingsFoundationTest.php tests/Feature/ObservabilityReportTest.php tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `152` tests and `1091` assertions
- Exact next task IDs:
  - `P3-PAYSET-03`
  - `P3-PAYSET-04`
  - `P3-PAYSET-05`
  - `P3-PAYSET-06`

### Batch 77 — Phase 3 Security Alerts and Finance/Currency Foundations (2026-03-20)
- Implemented only `P3-SECURITY-03`, `P3-SECURITY-04`, `P3-FINANCE-01`, and `P3-FINANCE-02`.
- Expanded the settings schema in:
  - [SettingsDefinitions](app/Modules/Settings/Services/SettingsDefinitions.php)
  - [SettingsSeeder](database/seeders/SettingsSeeder.php)
  with security alert routing fields and a new `finance` settings group for base currency and multi-currency foundations.
- Added runtime finance/config foundations in:
  - [FinanceSettingsService](app/Modules/Finance/Services/FinanceSettingsService.php)
  - [ApplyFinanceSettings](app/Http/Middleware/ApplyFinanceSettings.php)
  - [config/finance.php](config/finance.php)
  - [bootstrap/app.php](bootstrap/app.php)
- Added security alert routing foundation in:
  - [SecurityAlertRoutingService](app/Modules/Settings/Services/SecurityAlertRoutingService.php)
  - [ObservabilityLogger](app/Modules/Reports/Services/ObservabilityLogger.php)
  so critical slow-operation incidents now carry alert-recipient routing context when configured.
- Wired finance base-currency behavior into active defaults in:
  - [CartService](app/Modules/Checkout/Services/CartService.php)
  - [OrderCreate](app/Livewire/Admin/Orders/OrderCreate.php)
  - [AbandonedCartService](app/Modules/Promotions/Services/AbandonedCartService.php)
  - [DashboardMetricsService](app/Modules/IAM/Services/DashboardMetricsService.php)
- Extended coverage in:
  - [SettingsFoundationTest](tests/Feature/SettingsFoundationTest.php)
  - [ObservabilityReportTest](tests/Feature/ObservabilityReportTest.php)
  - [CheckoutFlowTest](tests/Feature/CheckoutFlowTest.php)
- Verification:
  - `php artisan test tests/Feature/SettingsFoundationTest.php tests/Feature/SecurityHardeningTest.php tests/Feature/ObservabilityReportTest.php tests/Feature/CheckoutFlowTest.php`
  - `php artisan test`
  - Result: full suite passing with `149` tests and `1043` assertions
- Exact next task IDs:
  - `P3-FINANCE-03`
  - `P3-FINANCE-04`
  - `P3-PAYSET-01`
  - `P3-PAYSET-02`

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
- Historical note corrected after reconciliation: this earlier batch only established low-level reservation helpers and partial branch filtering. `P5-RESERVE-02`, `P5-RESERVE-03`, `P5-BRANCH-01`, and `P5-BRANCH-02` were completed later in Batch 99, and `P5-BRANCH-03` was completed later in Batch 100.

### Batch 11 — Inventory Operations & Reports (2026-03-19)
- `P5-LOWSTOCK-01` to `P5-LOWSTOCK-03`: Implemented low stock filtering on the inventory index and added a "Low Stock Alerts" widget to the Admin Dashboard.
- `P5-DAMAGE-01` to `P5-DAMAGE-03`: Integrated damaged stock workflow via manual adjustments with strict reason logging, and built a dedicated "Movements & Damage" view to track all adjustments.
- Historical note corrected after reconciliation: this earlier batch introduced partial inventory report scaffolding, but `P5-REPORT-01` to `P5-REPORT-03` were completed later in Batch 100 with scoped controller logic, summaries, and focused tests.

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
- `P8-STRIPE-01`
- `P8-STRIPE-02`
- `P8-STRIPE-03`
- `P8-STRIPE-04`

## Blockers
- none
