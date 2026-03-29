<?php

use Illuminate\Support\Facades\Route;
use App\Modules\IAM\Http\Controllers\AuthController;
use App\Modules\IAM\Http\Controllers\CustomerAuthController;
use App\Modules\IAM\Http\Controllers\CustomerPasswordSetupController;
use App\Modules\IAM\Http\Controllers\CustomerPasswordResetController;
use App\Modules\IAM\Http\Controllers\DashboardController;
use App\Modules\IAM\Http\Controllers\RoleController;
use App\Modules\IAM\Http\Controllers\StaffAccessLinkController;
use App\Modules\IAM\Http\Controllers\StaffAccessSetupController;
use App\Modules\IAM\Http\Controllers\StaffSessionController;
use App\Modules\IAM\Http\Controllers\TwoFactorSetupController;
use App\Modules\Checkout\Http\Controllers\CheckoutResultController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/staff/access/setup/{token}', [StaffAccessSetupController::class, 'show'])
        ->middleware('signed')
        ->name('staff.access.setup.show');
    Route::post('/staff/access/setup', [StaffAccessSetupController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('staff.access.setup.store');
});

Route::get('/checkout/success/{reference}', [CheckoutResultController::class, 'success'])
    ->middleware('signed')
    ->name('checkout.success.signed');

// Customer auth surface
Route::prefix('account')->name('customer.')->group(function () {
    Route::get('login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [CustomerAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('forgot-password', [CustomerPasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [CustomerPasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reset-password/{token}', [CustomerPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [CustomerPasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.reset.store');

    Route::get('password/setup/{token}', [CustomerPasswordSetupController::class, 'show'])
        ->middleware('signed')
        ->name('password.setup.show');
    Route::post('password/setup', [CustomerPasswordSetupController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.setup.store');

    Route::middleware('customer.only')->group(function () {
        Route::get('/', [CustomerAuthController::class, 'home'])->name('account.home');
        Route::get('orders', [\App\Modules\Customers\Http\Controllers\CustomerOrderController::class, 'index'])->name('account.orders.index');
        Route::get('orders/{order}', [\App\Modules\Customers\Http\Controllers\CustomerOrderController::class, 'show'])->name('account.orders.show');
        Route::get('profile', [\App\Modules\Customers\Http\Controllers\ProfileController::class, 'edit'])->name('account.profile.edit');
        Route::put('profile', [\App\Modules\Customers\Http\Controllers\ProfileController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('password', [\App\Modules\Customers\Http\Controllers\ProfileController::class, 'updatePassword'])->name('account.password.update');
        Route::get('addresses', [\App\Modules\Customers\Http\Controllers\AddressController::class, 'manage'])->name('account.addresses.index');
        Route::post('addresses', [\App\Modules\Customers\Http\Controllers\AddressController::class, 'store'])->name('account.addresses.store');
        Route::put('addresses/{address}', [\App\Modules\Customers\Http\Controllers\AddressController::class, 'update'])->name('account.addresses.update');
        Route::delete('addresses/{address}', [\App\Modules\Customers\Http\Controllers\AddressController::class, 'destroy'])->name('account.addresses.destroy');
        Route::post('logout', [CustomerAuthController::class, 'logout'])->name('logout');
    });
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin
    Route::prefix('admin')->middleware(['staff.only', 'staff.2fa.enforced'])->group(function () {
        Route::prefix('security')->name('admin.security.')->group(function () {
            Route::get('two-factor-setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
            Route::post('two-factor-setup/password', [TwoFactorSetupController::class, 'prepare'])->name('two-factor.prepare');
            Route::post('two-factor-setup/confirm', [TwoFactorSetupController::class, 'confirm'])->name('two-factor.confirm');
        });

        Route::get('/', DashboardController::class)->name('admin.dashboard');

        // Impersonation routes
        Route::impersonate();

        // IAM / Staff
        Route::prefix('staff/roles')->name('admin.staff.roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        Route::post('staff/{staff}/access-link', [StaffAccessLinkController::class, 'store'])->name('admin.staff.access-link.store');
        Route::post('staff/{staff}/revoke-sessions', [StaffSessionController::class, 'store'])->name('admin.staff.sessions.revoke');
        Route::resource('staff', \App\Modules\IAM\Http\Controllers\StaffController::class)
            ->except(['show'])
            ->names('admin.staff');

        // Orders
        Route::get('orders', [\App\Modules\Orders\Http\Controllers\OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('orders/create', \App\Livewire\Admin\Orders\OrderCreate::class)->name('admin.orders.create');
        Route::get('orders/{order}', [\App\Modules\Orders\Http\Controllers\OrderController::class, 'show'])->name('admin.orders.show');

        // Customers
        Route::resource('customers', \App\Modules\Customers\Http\Controllers\AdminCustomerController::class)->names('admin.customers');

        // Catalog
        Route::prefix('catalog')->name('admin.catalog.')->group(function () {
            Route::resource('categories', \App\Modules\Catalog\Http\Controllers\CategoryController::class)->except(['show']);
            Route::post('products/bulk', [\App\Modules\Catalog\Http\Controllers\ProductController::class, 'bulk'])->name('products.bulk');
            Route::post('products/{product}/status', [\App\Modules\Catalog\Http\Controllers\ProductController::class, 'updateStatus'])->name('products.status');
            Route::resource('products', \App\Modules\Catalog\Http\Controllers\ProductController::class)->except(['show']);
        });

        // Settings & Configurations
        Route::prefix('settings')->middleware('permission.any:settings.manage')->group(function () {
            Route::get('/', [\App\Modules\Settings\Http\Controllers\SettingsController::class, 'index'])->name('admin.settings.index');
            Route::put('/', [\App\Modules\Settings\Http\Controllers\SettingsController::class, 'update'])->name('admin.settings.update');
        });

        // Payment Gateways
        Route::prefix('gateways')->middleware('permission.any:finance.manage_gateways')->group(function () {
            Route::get('/', [\App\Modules\Payments\Http\Controllers\AdminGatewaySettingController::class, 'index'])->name('admin.gateways.index');
            Route::put('/{gateway}', [\App\Modules\Payments\Http\Controllers\AdminGatewaySettingController::class, 'update'])->name('admin.gateways.update');
        });

        // Inventory
        Route::prefix('inventory')->name('admin.inventory.')->group(function () {
            Route::get('/', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'index'])->name('index');
            Route::get('/low-stock', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'lowStock'])->name('low-stock');
            Route::get('/damage/create', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'createDamageReport'])->name('damage.create');
            Route::post('/damage', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'storeDamageReport'])->name('damage.store');
            Route::get('/damage', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'damagedStock'])->name('damage.index');
            Route::get('/adjustments/create', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'createAdjustment'])->name('adjustments.create');
            Route::post('/adjustments', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
            Route::post('/{stockItem}/threshold', [\App\Modules\Inventory\Http\Controllers\InventoryController::class, 'updateThreshold'])->name('threshold.update');
            Route::resource('suppliers', \App\Modules\Organizations\Http\Controllers\AdminSupplierController::class)->names([
                'index' => 'suppliers.index',
                'create' => 'suppliers.create',
                'show' => 'suppliers.show',
                'store' => 'suppliers.store',
                'edit' => 'suppliers.edit',
                'update' => 'suppliers.update',
                'destroy' => 'suppliers.destroy',
            ]);
            Route::resource('branches', \App\Modules\Organizations\Http\Controllers\AdminBranchController::class)->names([
                'index' => 'branches.index',
                'create' => 'branches.create',
                'show' => 'branches.show',
                'store' => 'branches.store',
                'edit' => 'branches.edit',
                'update' => 'branches.update',
                'destroy' => 'branches.destroy',
            ]);

            
            // Reports
            Route::get('/reports/stock', [\App\Modules\Inventory\Http\Controllers\InventoryReportsController::class, 'currentStock'])->name('reports.stock');
            Route::get('/reports/adjustments', [\App\Modules\Inventory\Http\Controllers\InventoryReportsController::class, 'adjustments'])->name('reports.adjustments');
            Route::get('/reports/low-stock', [\App\Modules\Inventory\Http\Controllers\InventoryReportsController::class, 'lowStock'])->name('reports.low-stock');
            Route::get('/reports/damaged-stock', [\App\Modules\Inventory\Http\Controllers\InventoryReportsController::class, 'damagedStock'])->name('reports.damaged-stock');
        });

        // Shipping
        Route::prefix('shipping')->name('admin.shipping.')->group(function () {
            // Shipping Methods CRUD
            Route::resource('methods', \App\Modules\Shipping\Http\Controllers\ShippingMethodController::class)
                ->except(['show'])
                ->names('methods');

            // Shipments
            Route::get('shipments', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'index'])->name('shipments.index');
            Route::post('shipments', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'store'])->name('shipments.store');
            Route::get('shipments/{shipment}', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'show'])->name('shipments.show');
            Route::post('shipments/{shipment}/status', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'updateStatus'])->name('shipments.status');
            Route::post('shipments/{shipment}/tracking', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'updateTracking'])->name('shipments.tracking');
            Route::post('shipments/{shipment}/issue', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'toggleIssue'])->name('shipments.issue');

            // Reports
            Route::get('reports', [\App\Modules\Shipping\Http\Controllers\ShipmentController::class, 'reports'])->name('reports');
        });

        // Notifications
        Route::prefix('notifications')->name('admin.notifications.')->group(function () {
            // Templates CRUD
            Route::resource('templates', \App\Modules\Notifications\Http\Controllers\NotificationTemplateController::class)
                ->except(['show']);
            Route::post('templates/{template}/toggle', [\App\Modules\Notifications\Http\Controllers\NotificationTemplateController::class, 'toggle'])->name('templates.toggle');
            Route::get('templates/{template}/preview', [\App\Modules\Notifications\Http\Controllers\NotificationTemplateController::class, 'preview'])->name('templates.preview');

            // Logs
            Route::get('logs', [\App\Modules\Notifications\Http\Controllers\NotificationLogController::class, 'index'])->name('logs.index');
            Route::get('logs/{log}', [\App\Modules\Notifications\Http\Controllers\NotificationLogController::class, 'show'])->name('logs.show');
            Route::post('logs/{log}/retry', [\App\Modules\Notifications\Http\Controllers\NotificationLogController::class, 'retry'])->name('logs.retry');

            // Integrations
            Route::get('integrations', [\App\Modules\Notifications\Http\Controllers\IntegrationSettingController::class, 'index'])->name('integrations.index');
            Route::put('integrations/{integration}', [\App\Modules\Notifications\Http\Controllers\IntegrationSettingController::class, 'update'])->name('integrations.update');
            Route::get('integrations/{integration}/test', [\App\Modules\Notifications\Http\Controllers\IntegrationSettingController::class, 'test'])->name('integrations.test');
        });

        // Promotions & Coupons
        Route::prefix('promotions')->name('admin.promotions.')->middleware('feature.enabled:promotions')->group(function () {
            Route::resource('coupons', \App\Modules\Promotions\Http\Controllers\CouponController::class)
                ->except(['show']);
            Route::get('reports', [\App\Modules\Promotions\Http\Controllers\CouponController::class, 'reports'])->name('reports');
        });

        // CMS & Blog
        Route::prefix('cms')->name('admin.cms.')->middleware('feature.enabled:cms')->group(function () {
            // Blog Categories
            Route::resource('categories', \App\Modules\Cms\Http\Controllers\BlogCategoryController::class)
                ->except(['show']);

            // Blog Tags
            Route::resource('tags', \App\Modules\Cms\Http\Controllers\BlogTagController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            // Blog Posts
            Route::resource('posts', \App\Modules\Cms\Http\Controllers\BlogPostController::class)
                ->except(['show']);
            Route::get('posts/{post}/preview', [\App\Modules\Cms\Http\Controllers\BlogPostController::class, 'preview'])->name('posts.preview');
            Route::post('posts/{post}/toggle', [\App\Modules\Cms\Http\Controllers\BlogPostController::class, 'togglePublish'])->name('posts.toggle');

            // Redirects
            Route::resource('redirects', \App\Modules\Cms\Http\Controllers\RedirectController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

        // Finance
        Route::prefix('finance')->name('admin.finance.')->group(function () {
            Route::get('transactions', [\App\Modules\Finance\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
            Route::get('transactions/{transaction}', [\App\Modules\Finance\Http\Controllers\TransactionController::class, 'show'])->name('transactions.show');
            Route::post('transactions/{transaction}/verify-offline', [\App\Modules\Finance\Http\Controllers\TransactionController::class, 'verifyOffline'])->name('transactions.verify-offline');
            Route::post('transactions/{transaction}/fail-offline', [\App\Modules\Finance\Http\Controllers\TransactionController::class, 'failOffline'])->name('transactions.fail-offline');

            Route::get('refunds', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'index'])->name('refunds.index');
            Route::post('refunds/{refund}/approve', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'approve'])->name('refunds.approve');
            Route::post('refunds/{refund}/reject', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'reject'])->name('refunds.reject');
            Route::post('refunds/{refund}/complete', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'complete'])->name('refunds.complete');

            Route::get('reports', [\App\Modules\Finance\Http\Controllers\FinanceReportController::class, 'index'])->name('reports.index');
            Route::post('reports/cod/{transaction}/collect', [\App\Modules\Finance\Http\Controllers\FinanceReportController::class, 'codCollect'])->name('reports.cod.collect');
            Route::post('reports/cod/{transaction}/deposit', [\App\Modules\Finance\Http\Controllers\FinanceReportController::class, 'codDeposit'])->name('reports.cod.deposit');
            Route::post('reports/cod/{transaction}/reconcile', [\App\Modules\Finance\Http\Controllers\FinanceReportController::class, 'codReconcile'])->name('reports.cod.reconcile');
        });

        // Support
        Route::prefix('support')->name('admin.support.')->group(function () {
            // Lookup
            Route::get('lookup', [\App\Modules\Support\Http\Controllers\SupportLookupController::class, 'index'])->name('lookup');
            Route::get('lookup/order/{order}/timeline', [\App\Modules\Support\Http\Controllers\SupportLookupController::class, 'orderTimeline'])->name('lookup.timeline');

            // Issues
            Route::get('issues', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'index'])->name('issues.index');
            Route::get('issues/create', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'create'])->name('issues.create');
            Route::post('issues', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'store'])->name('issues.store');
            Route::get('issues/{issue}', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'show'])->name('issues.show');
            Route::put('issues/{issue}', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'update'])->name('issues.update');
            Route::post('issues/{issue}/progress', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'progress'])->name('issues.progress');
            Route::post('issues/{issue}/resolve', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'resolve'])->name('issues.resolve');
            Route::post('issues/{issue}/close', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'close'])->name('issues.close');
            Route::post('issues/{issue}/reopen', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'reopen'])->name('issues.reopen');
            Route::post('issues/{issue}/note', [\App\Modules\Support\Http\Controllers\SupportIssueController::class, 'addNote'])->name('issues.note');

            // Notes (polymorphic)
            Route::post('notes', [\App\Modules\Support\Http\Controllers\NoteController::class, 'store'])->name('notes.store');
            Route::post('notes/{note}/pin', [\App\Modules\Support\Http\Controllers\NoteController::class, 'togglePin'])->name('notes.pin');
            Route::delete('notes/{note}', [\App\Modules\Support\Http\Controllers\NoteController::class, 'destroy'])->name('notes.destroy');
        });

        // Reports & Analytics
        Route::prefix('reports')->name('admin.reports.')->middleware('permission.any:reports.viewAny')->group(function () {
            Route::get('sales', [\App\Modules\Reports\Http\Controllers\SalesReportController::class, 'index'])->name('sales');
            Route::get('orders', [\App\Modules\Reports\Http\Controllers\OrdersReportController::class, 'index'])->name('orders');
            Route::get('inventory', [\App\Modules\Reports\Http\Controllers\InventoryReportController::class, 'index'])->name('inventory');
            Route::get('coupons', [\App\Modules\Reports\Http\Controllers\CouponReportController::class, 'index'])->name('coupons');
            Route::get('finance', [\App\Modules\Reports\Http\Controllers\FinanceSummaryReportController::class, 'index'])->name('finance');
            Route::get('observability', [\App\Modules\Reports\Http\Controllers\ObservabilityReportController::class, 'index'])->name('observability');

            // Exports
            Route::prefix('export')->name('export.')->group(function () {
                Route::get('products', [\App\Modules\Reports\Http\Controllers\ExportController::class, 'products'])->name('products');
                Route::get('orders', [\App\Modules\Reports\Http\Controllers\ExportController::class, 'orders'])->name('orders');
                Route::get('transactions', [\App\Modules\Reports\Http\Controllers\ExportController::class, 'transactions'])->name('transactions');
                Route::get('inventory', [\App\Modules\Reports\Http\Controllers\ExportController::class, 'inventory'])->name('inventory');
            });

            // Import
            Route::post('import/catalog', [\App\Modules\Reports\Http\Controllers\ExportController::class, 'importCatalog'])->name('import.catalog');
        });

        // Bulk Actions
        Route::prefix('bulk')->name('admin.bulk.')->group(function () {
            Route::post('products', [\App\Modules\Reports\Http\Controllers\BulkActionController::class, 'productsBulk'])->name('products');
            Route::post('orders', [\App\Modules\Reports\Http\Controllers\BulkActionController::class, 'ordersBulk'])->name('orders');
            Route::post('content', [\App\Modules\Reports\Http\Controllers\BulkActionController::class, 'contentBulk'])->name('content');
        });

        // Saved Filters
        Route::prefix('saved-filters')->name('admin.saved-filters.')->group(function () {
            Route::post('/', [\App\Modules\Reports\Http\Controllers\SavedFilterController::class, 'store'])->name('store');
            Route::delete('{filter}', [\App\Modules\Reports\Http\Controllers\SavedFilterController::class, 'destroy'])->name('destroy');
        });
    });

});

// Customer API
Route::prefix('api/customer')->name('api.customer.')->middleware('customer.only')->group(function () {
    Route::get('overview', [\App\Modules\Customers\Http\Controllers\AccountOverviewController::class, 'index'])->name('overview');
    Route::put('profile', [\App\Modules\Customers\Http\Controllers\ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('password', [\App\Modules\Customers\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    Route::apiResource('addresses', \App\Modules\Customers\Http\Controllers\AddressController::class);

    // Shipping / Tracking
    Route::get('orders/{order}/tracking', [\App\Modules\Shipping\Http\Controllers\CustomerTrackingController::class, 'show'])->name('orders.tracking');
});

// Cart API (Open to Guests & Authenticated via X-Cart-Session-Id/Sanctum)
Route::prefix('api/cart')->group(function () {
    Route::get('/', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'index'])->name('api.cart.index');
    Route::post('/items', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'addItem'])->name('api.cart.items.add');
    Route::put('/items/{item}', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'updateItem'])->name('api.cart.items.update');
    Route::delete('/items/{item}', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'removeItem'])->name('api.cart.items.remove');
    
    Route::post('/coupon', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'applyCoupon'])->name('api.cart.coupon.apply');
    Route::delete('/coupon', [\App\Modules\Checkout\Http\Controllers\CartController::class, 'removeCoupon'])->name('api.cart.coupon.remove');
});

// Checkout Pipeline (Open to Guests & Authenticated)
Route::post('api/checkout/process', [\App\Modules\Checkout\Http\Controllers\CheckoutController::class, 'process'])->name('api.checkout.process');

// Order Tracking (Open to Guests)
Route::get('api/orders/{reference}', [\App\Modules\Orders\Http\Controllers\OrderTrackingController::class, 'show'])->name('api.orders.track');

// ──────────────────────────────────────────────────────────────
// Payment Gateway Callbacks & Webhooks (Public, no auth middleware)
// These endpoints are called by external gateway servers and customer browsers.
// ──────────────────────────────────────────────────────────────
Route::prefix('payment')->name('payment.')->group(function () {
    // CMI Morocco
    Route::match(['get', 'post'], '/cmi/callback', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'cmiCallback'])->name('cmi.callback');
    Route::post('/cmi/webhook', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'cmiWebhook'])->name('cmi.webhook');

    // Payzone Morocco
    Route::get('/payzone/callback', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'payzoneCallback'])->name('payzone.callback');
    Route::post('/payzone/webhook', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'payzoneWebhook'])->name('payzone.webhook');

    // Stripe
    Route::get('/stripe/callback', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'stripeCallback'])->name('stripe.callback');
    Route::post('/stripe/webhook', [\App\Modules\Payments\Http\Controllers\PaymentCallbackController::class, 'stripeWebhook'])->name('stripe.webhook');
});

// Checkout result pages
Route::get('/checkout/success', [CheckoutResultController::class, 'success'])->name('checkout.success');
Route::get('/checkout/failed', [CheckoutResultController::class, 'failed'])->name('checkout.failed');

// Redirect root to admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});
