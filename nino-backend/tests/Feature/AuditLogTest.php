<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Models\Product;
use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Finance\Models\RefundRequest;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_creation_writes_actor_target_and_request_context_to_audit_log(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.staff.store'), [
            'name' => 'Audit Target',
            'email' => 'audit-target@example.test',
            'phone' => '+212600000001',
            'password' => 'SecretPass123',
            'password_confirmation' => 'SecretPass123',
            'status' => 'active',
            'roles' => [],
            'organization_scope' => 'platform',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $auditLog = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('staff.created', $auditLog->action);
        $this->assertSame($superAdmin->id, $auditLog->user_id);
        $this->assertSame($superAdmin->name, $auditLog->actor_name);
        $this->assertSame($superAdmin->email, $auditLog->actor_email);
        $this->assertSame('User: Audit Target', $auditLog->target_label);
        $this->assertSame('admin.staff.store', $auditLog->context['route_name'] ?? null);
        $this->assertSame('POST', $auditLog->context['method'] ?? null);
        $this->assertSame('staff_controller', $auditLog->context['source'] ?? null);
        $this->assertArrayNotHasKey('password', $auditLog->new_values ?? []);
        $this->assertSame('audit-target@example.test', $auditLog->new_values['email'] ?? null);
    }

    public function test_settings_updates_are_logged_with_group_target_and_changed_values(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'group' => 'security',
            'force_2fa' => '1',
            'session_lifetime' => 90,
            'password_min_length' => 10,
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'security']));

        $auditLog = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('settings.security.updated', $auditLog->action);
        $this->assertSame('Settings: Security', $auditLog->target_label);
        $this->assertSame('security', $auditLog->context['group'] ?? null);
        $this->assertSame('admin.settings.update', $auditLog->context['route_name'] ?? null);
        $this->assertSame(90, $auditLog->new_values['session_lifetime'] ?? null);
        $this->assertSame('[REDACTED]', $auditLog->new_values['password_min_length'] ?? null);
    }

    public function test_notification_integration_audit_logs_redact_secret_values(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $staffUser = $this->makeSuperAdmin();

        $integration = IntegrationSetting::create([
            'provider' => 'twilio',
            'name' => 'Twilio SMS',
            'is_enabled' => false,
            'credentials' => [
                'account_sid' => '',
                'auth_token' => '',
                'from_number' => '',
            ],
        ]);

        $response = $this->actingAs($staffUser)->put(route('admin.notifications.integrations.update', $integration), [
            'is_enabled' => '1',
            'credentials' => [
                'account_sid' => 'AC123456789',
                'auth_token' => 'very-secret-token',
                'from_number' => '+212600000002',
            ],
        ]);

        $response->assertRedirect(route('admin.notifications.integrations.index'));

        $auditLog = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('notifications.integration.updated', $auditLog->action);
        $this->assertSame('IntegrationSetting: Twilio SMS', $auditLog->target_label);
        $this->assertSame('twilio', $auditLog->context['provider'] ?? null);
        $this->assertSame('[REDACTED]', $auditLog->new_values['credentials']['auth_token'] ?? null);
        $this->assertSame('AC123456789', $auditLog->new_values['credentials']['account_sid'] ?? null);
    }

    public function test_staff_update_logs_role_changes_and_deactivation_as_explicit_events(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $staff = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);
        $staff->assignRole('Employee');

        $response = $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => '+212600000005',
            'status' => 'inactive',
            'roles' => ['Branch Manager'],
            'organization_scope' => 'platform',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $roleAudit = AuditLog::query()->where('action', 'staff.roles.changed')->latest('id')->first();
        $deactivationAudit = AuditLog::query()->where('action', 'staff.deactivated')->latest('id')->first();

        $this->assertNotNull($roleAudit);
        $this->assertSame(['roles' => ['Employee']], $roleAudit->old_values);
        $this->assertSame(['roles' => ['Branch Manager']], $roleAudit->new_values);

        $this->assertNotNull($deactivationAudit);
        $this->assertSame(['status' => 'active'], $deactivationAudit->old_values);
        $this->assertSame(['status' => 'inactive'], $deactivationAudit->new_values);
    }

    public function test_gateway_updates_are_audited_with_redacted_sensitive_credentials(): void
    {
        $staffUser = $this->makeSuperAdmin();

        $gateway = GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [
                'secret_key' => '',
                'webhook_secret' => '',
            ],
            'metadata' => [
                'publishable_key' => '',
                'currency' => 'MAD',
            ],
        ]);

        $response = $this->actingAs($staffUser)->put(route('admin.gateways.update', $gateway), [
            'is_enabled' => '1',
            'mode' => 'live',
            'credentials' => [
                'secret_key' => 'sk_live_123',
                'webhook_secret' => 'whsec_live_123',
            ],
            'metadata' => [
                'publishable_key' => 'pk_live_123',
                'currency' => 'USD',
            ],
        ]);

        $response->assertRedirect(route('admin.gateways.index', ['tab' => 'providers', 'gateway' => 'stripe']));

        $auditLog = AuditLog::query()->where('action', 'payments.gateway.updated')->latest('id')->firstOrFail();

        $this->assertSame('admin_gateway_setting_controller', $auditLog->context['source'] ?? null);
        $this->assertSame('stripe', $auditLog->context['gateway_id'] ?? null);
        $this->assertSame('live', $auditLog->new_values['mode'] ?? null);
        $this->assertSame('[REDACTED]', $auditLog->new_values['credentials']['secret_key'] ?? null);
        $this->assertSame('[REDACTED]', $auditLog->new_values['credentials']['webhook_secret'] ?? null);
        $this->assertSame('[REDACTED]', $auditLog->new_values['metadata']['publishable_key'] ?? null);
        $this->assertSame('USD', $auditLog->new_values['metadata']['currency'] ?? null);
    }

    public function test_impersonation_events_are_audited(): void
    {
        $impersonator = $this->makeSuperAdmin();
        $impersonated = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        event(new TakeImpersonation($impersonator, $impersonated));
        event(new LeaveImpersonation($impersonator, $impersonated));

        $started = AuditLog::query()->where('action', 'impersonation.started')->latest('id')->first();
        $ended = AuditLog::query()->where('action', 'impersonation.ended')->latest('id')->first();

        $this->assertNotNull($started);
        $this->assertSame($impersonator->id, $started->user_id);
        $this->assertSame('take', $started->context['event'] ?? null);
        $this->assertSame('impersonation', $started->context['source'] ?? null);

        $this->assertNotNull($ended);
        $this->assertSame($impersonator->id, $ended->user_id);
        $this->assertSame('leave', $ended->context['event'] ?? null);
        $this->assertSame('impersonation', $ended->context['source'] ?? null);
    }

    public function test_impersonation_routes_work_and_write_audit_logs(): void
    {
        $impersonator = $this->makeSuperAdmin();
        $impersonated = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($impersonator)
            ->get(route('impersonate', $impersonated->getKey()))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($impersonated);
        $this->assertSame($impersonator->getKey(), session('impersonated_by'));

        $this->get(route('impersonate.leave'))->assertRedirect('/');

        $this->assertAuthenticatedAs($impersonator);
        $this->assertNull(session('impersonated_by'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'impersonation.started',
            'user_id' => $impersonator->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'impersonation.ended',
            'user_id' => $impersonator->getKey(),
        ]);
    }

    public function test_staff_sensitive_account_updates_are_logged_explicitly(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $staff = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
            'email' => 'sensitive.before@example.test',
        ]);
        $staff->assignRole('Employee');

        $response = $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => 'sensitive.after@example.test',
            'phone' => '',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'status' => 'suspended',
            'roles' => ['Employee'],
            'organization_scope' => 'platform',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $sensitiveAudit = AuditLog::query()->where('action', 'staff.account_access.changed')->latest('id')->first();
        $passwordAudit = AuditLog::query()->where('action', 'staff.password.changed')->latest('id')->first();

        $this->assertNotNull($sensitiveAudit);
        $this->assertSame('sensitive.before@example.test', $sensitiveAudit->old_values['email'] ?? null);
        $this->assertSame('sensitive.after@example.test', $sensitiveAudit->new_values['email'] ?? null);
        $this->assertSame(User::STATUS_ACTIVE, $sensitiveAudit->old_values['status'] ?? null);
        $this->assertSame(User::STATUS_SUSPENDED, $sensitiveAudit->new_values['status'] ?? null);

        $this->assertNotNull($passwordAudit);
        $this->assertTrue($passwordAudit->new_values['credentials_rotated'] ?? false);
        $this->assertSame('staff_controller', $passwordAudit->context['source'] ?? null);
    }

    public function test_product_price_changes_are_audited(): void
    {
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Audit Plush',
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'APL-001',
            'price' => 100,
            'sale_price' => 90,
            'cost_price' => 60,
        ]);

        $this->actingAs($staffUser);
        Gate::shouldReceive('authorize')
            ->once()
            ->with('update', \Mockery::type(Product::class))
            ->andReturn(AccessResponse::allow());

        $request = Request::create(route('admin.catalog.products.update', $product), 'PUT', [
            'name' => 'Audit Plush',
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'APL-001',
            'price' => 120,
            'sale_price' => 95,
            'cost_price' => 70,
        ]);

        $response = $this->app->make(ProductController::class)->update($request, $product);

        $this->assertSame(route('admin.catalog.products.index'), $response->getTargetUrl());

        $auditLog = AuditLog::query()->where('action', 'catalog.product.price_changed')->latest('id')->firstOrFail();

        $this->assertEquals(['price' => 100.0, 'sale_price' => 90.0, 'cost_price' => 60.0], $auditLog->old_values);
        $this->assertEquals(['price' => 120.0, 'sale_price' => 95.0, 'cost_price' => 70.0], $auditLog->new_values);
    }

    public function test_bulk_order_status_overrides_are_audited(): void
    {
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-AUDIT-001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
        ]);

        $response = $this->actingAs($staffUser)->post(route('admin.bulk.orders'), [
            'action' => 'mark_processing',
            'ids' => [$order->id],
        ]);

        $response->assertRedirect();

        $auditLog = AuditLog::query()->where('action', 'orders.status_overridden')->latest('id')->firstOrFail();

        $this->assertSame(['status' => 'pending'], $auditLog->old_values);
        $this->assertSame(['status' => 'preparing'], $auditLog->new_values);
        $this->assertTrue($auditLog->context['bulk_action'] ?? false);
    }

    public function test_refund_status_updates_are_audited(): void
    {
        Permission::findOrCreate('payments.refund', 'web');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        $staffUser->givePermissionTo('payments.refund');
        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);
        $order = Order::create([
            'reference_number' => 'ORD-RFD-001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => 200,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 200,
        ]);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => RefundStatus::REQUESTED,
            'amount' => 25,
            'original_order_total' => 200,
            'currency' => 'MAD',
            'reason' => 'Customer requested refund',
        ]);

        $response = $this->actingAs($staffUser)->post(route('admin.finance.refunds.approve', $refund));

        $response->assertRedirect();

        $auditLog = AuditLog::query()->where('action', 'finance.refund.updated')->latest('id')->firstOrFail();

        $this->assertSame('requested', $auditLog->old_values['status'] ?? null);
        $this->assertArrayNotHasKey('amount', $auditLog->old_values ?? []);
        $this->assertSame('approved', $auditLog->new_values['status'] ?? null);
        $this->assertSame('approve', $auditLog->context['transition'] ?? null);
    }

    public function test_stock_adjustments_are_audited_from_inventory_service(): void
    {
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        $product = Product::create([
            'name' => 'Stock Plush',
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'STK-001',
            'price' => 50,
        ]);
        $branch = Organization::create([
            'name' => 'Casablanca Branch',
            'type' => 'branch',
            'status' => 'active',
        ]);
        $stockItem = StockItem::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'status' => 'in_stock',
        ]);

        $this->actingAs($staffUser);

        app(InventoryService::class)->adjustStock(
            $stockItem,
            -2,
            'manual_adjustment',
            $staffUser->id,
            'Cycle count correction'
        );

        $auditLog = AuditLog::query()->where('action', 'inventory.stock_adjusted')->latest('id')->firstOrFail();

        $this->assertSame(5, $auditLog->old_values['quantity'] ?? null);
        $this->assertSame(3, $auditLog->new_values['quantity'] ?? null);
        $this->assertSame(-2, $auditLog->context['quantity_change'] ?? null);
        $this->assertSame('manual_adjustment', $auditLog->context['reason'] ?? null);
    }

    private function makeSuperAdmin(): User
    {
        Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);

        $user->assignRole('Super Admin');

        return $user;
    }
}
