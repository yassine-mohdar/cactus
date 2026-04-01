<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Jobs\SyncSenditDeliveryUpdateJob;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SenditSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
        Queue::fake();
    }

    public function test_sendit_webhook_fetches_the_latest_delivery_state_for_a_matching_shipment(): void
    {
        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-webhook',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-webhook',
                'secret_key' => 'secret-webhook',
            ],
            'settings' => [
                'pickup_district_id' => 12,
                'webhook_secret' => 'webhook-secret-demo',
                'webhook_api_key' => 'dev-yassine',
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Webhook',
            'slug' => 'sendit-webhook',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $customer = User::factory()->customer()->create(['status' => 'active']);
        $order = $this->makeOrder($customer, 'ORD-SENDIT-WEBHOOK-001');
        $order->addresses()->create($this->addressPayload('shipping'));

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::IN_TRANSIT->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'DH-WEBHOOK-1001',
            'external_reference' => 'DH-WEBHOOK-1001',
            'external_status' => 'TRANSIT',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-webhook-token'],
            ], 200),
            'https://app.sendit.ma/api/v1/deliveries/DH-WEBHOOK-1001' => Http::response([
                'data' => [
                    'code' => 'DH-WEBHOOK-1001',
                    'status' => 'DELIVERED',
                    'labelUrl' => 'https://cdn.sendit.test/labels/DH-WEBHOOK-1001.pdf',
                ],
            ], 200),
        ]);

        $this->postJson(route('shipping.sendit.webhook', [
            'carrier' => $carrier->code,
            'secret' => 'webhook-secret-demo',
            'api_key' => 'dev-yassine',
        ]), [
            'code' => 'DH-WEBHOOK-1001',
            'status' => 'DELIVERED',
        ])->assertOk()->assertJson([
            'success' => true,
            'matched' => true,
            'shipment_id' => $shipment->id,
        ]);

        $shipment->refresh();

        $this->assertSame('DELIVERED', $shipment->external_status);
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->status);
    }

    public function test_sendit_webhook_rejects_requests_with_an_invalid_api_key_when_one_is_configured(): void
    {
        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-webhook-keyed',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-webhook',
                'secret_key' => 'secret-webhook',
            ],
            'settings' => [
                'pickup_district_id' => 12,
                'webhook_secret' => 'webhook-secret-demo',
                'webhook_api_key' => 'dev-yassine',
            ],
        ]);

        $this->postJson(route('shipping.sendit.webhook', [
            'carrier' => $carrier->code,
            'secret' => 'webhook-secret-demo',
            'api_key' => 'wrong-key',
        ]), [
            'code' => 'DH-WEBHOOK-403',
            'status' => 'DELIVERED',
        ])->assertForbidden();
    }

    public function test_cancelling_a_sendit_shipment_before_collection_deletes_the_remote_parcel(): void
    {
        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create(['status' => 'active']);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-cancel-shipment',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-cancel',
                'secret_key' => 'secret-cancel',
            ],
            'settings' => [
                'pickup_district_id' => 15,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Cancel',
            'slug' => 'sendit-cancel',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-CANCEL-001');
        $order->update(['status' => OrderStatus::PREPARING]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'external_reference' => 'DH-CANCEL-1001',
            'tracking_number' => 'DH-CANCEL-1001',
            'external_status' => 'PENDING',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-cancel-token'],
            ], 200),
            'https://app.sendit.ma/api/v1/deliveries/DH-CANCEL-1001' => Http::response([
                'success' => true,
                'message' => 'Colis supprimé avec succes.',
            ], 200),
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::CANCELLED->value,
                'notes' => 'Customer cancelled before pickup',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $shipment->refresh();
        $order->refresh();

        $this->assertSame(ShipmentStatus::CANCELLED, $shipment->status);
        $this->assertSame('DELETED', $shipment->external_status);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    public function test_order_delivery_contact_update_syncs_the_existing_sendit_parcel(): void
    {
        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create(['status' => 'active']);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-contact-update',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-update',
                'secret_key' => 'secret-update',
            ],
            'settings' => [
                'pickup_district_id' => 18,
            ],
        ]);

        $district = ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '501',
            'city' => 'Achakkar',
            'district_name' => 'Achakkar',
            'price' => 45,
            'estimated_delivery' => '24h - 48h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => (string) $district->id,
            'city' => 'Kariat Arekmane',
            'district_name' => 'Kariat Arekmane',
            'price' => 65,
            'estimated_delivery' => '48h - 96h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-contact-update',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-CONTACT-001');
        $order->addresses()->create([
            ...$this->addressPayload('shipping'),
            'city' => 'Achakkar',
            'state' => 'Achakkar',
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => 'cod',
            'amount' => 125,
            'net_amount' => 125,
            'currency' => 'MAD',
            'order_shipping' => 25,
            'order_total' => 125,
            'gateway' => 'cod',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'external_reference' => 'DH-UPDATE-1001',
            'tracking_number' => 'DH-UPDATE-1001',
            'external_status' => 'PENDING',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-update-token'],
            ], 200),
            'https://app.sendit.ma/api/v1/deliveries/DH-UPDATE-1001' => Http::response([
                'data' => [
                    'code' => 'DH-UPDATE-1001',
                    'status' => 'PENDING',
                    'labelUrl' => 'https://cdn.sendit.test/labels/DH-UPDATE-1001.pdf',
                ],
            ], 200),
        ]);

        $this->actingAs($staffUser)
            ->put(route('admin.orders.delivery-contact', $order), [
                'first_name' => 'Lina',
                'last_name' => 'Updated',
                'phone' => '+212612987654',
                'address_line_1' => '99 Boulevard Updated',
                'address_line_2' => 'Apt 8',
                'district_id' => $district->id,
                'postal_code' => '20000',
                'country' => 'MA',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        Queue::assertPushed(SyncSenditDeliveryUpdateJob::class, function (SyncSenditDeliveryUpdateJob $job) use ($shipment): bool {
            if ($job->shipmentId !== $shipment->id) {
                return false;
            }

            app()->call([$job, 'handle']);

            return true;
        });

        $address = $order->fresh()->shippingAddress;
        $order->refresh();
        $transaction = $order->transactions()->latest('id')->firstOrFail();
        $shipment->refresh();

        $this->assertSame('Lina', $address->first_name);
        $this->assertSame('Updated', $address->last_name);
        $this->assertSame('+212612987654', $address->phone);
        $this->assertSame('99 Boulevard Updated', $address->address_line_1);
        $this->assertSame($district->id, $address->shipping_carrier_district_id);
        $this->assertSame(45.0, (float) $order->shipping_total);
        $this->assertSame(145.0, (float) $order->grand_total);
        $this->assertSame(45.0, (float) $transaction->order_shipping);
        $this->assertSame(145.0, (float) $transaction->order_total);
        $this->assertSame(145.0, (float) $transaction->amount);
        $this->assertSame('https://cdn.sendit.test/labels/DH-UPDATE-1001.pdf', $shipment->label_url);
        $this->assertNull($shipment->provider_error);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_ends_with($request->url(), '/deliveries/DH-UPDATE-1001')
                && ($payload['name'] ?? null) === 'Lina Updated'
                && ($payload['phone'] ?? null) === '0612987654'
                && ($payload['district_id'] ?? null) === 501
                && (float) ($payload['amount'] ?? 0) === 145.0
                && str_contains((string) ($payload['address'] ?? ''), '99 Boulevard Updated');
        });
    }

    public function test_manual_sendit_delivery_update_endpoint_queues_a_background_sync_job(): void
    {
        $staffUser = $this->makeShippingUser();

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-update-endpoint',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-endpoint',
                'secret_key' => 'secret-endpoint',
            ],
            'settings' => [
                'pickup_district_id' => 18,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-update-endpoint',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $customer = User::factory()->customer()->create(['status' => 'active']);
        $order = $this->makeOrder($customer, 'ORD-SENDIT-UPDATE-ENDPOINT-001');

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'external_reference' => 'DH-ENDPOINT-1001',
            'tracking_number' => 'DH-ENDPOINT-1001',
            'provider_error' => 'Old sync failure',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.sendit.update', $shipment))
            ->assertRedirect()
            ->assertSessionHas('success', 'Sendit delivery update queued successfully.');

        Queue::assertPushed(SyncSenditDeliveryUpdateJob::class, function (SyncSenditDeliveryUpdateJob $job) use ($shipment): bool {
            return $job->shipmentId === $shipment->id;
        });

        $this->assertNull($shipment->fresh()->provider_error);
    }

    public function test_sendit_delivery_contact_update_requires_an_explicit_district_selection(): void
    {
        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create(['status' => 'active']);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-contact-selection',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-update',
                'secret_key' => 'secret-update',
            ],
            'settings' => [
                'pickup_district_id' => 18,
            ],
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '501',
            'city' => 'Achakkar',
            'district_name' => 'Achakkar',
            'price' => 45,
            'estimated_delivery' => '24h - 48h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-contact-selection',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-CONTACT-002');
        $order->update([
            'shipping_method' => $shippingMethod->name,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_total' => 45,
            'grand_total' => 145,
        ]);
        $order->addresses()->create([
            ...$this->addressPayload('shipping'),
            'city' => 'Achakkar',
            'state' => 'Achakkar',
        ]);

        $this->actingAs($staffUser)
            ->from(route('admin.orders.show', $order))
            ->put(route('admin.orders.delivery-contact', $order), [
                'first_name' => 'Lina',
                'last_name' => 'Updated',
                'phone' => '+212612987654',
                'address_line_1' => '99 Boulevard Updated',
                'address_line_2' => 'Apt 8',
                'district_id' => '',
                'postal_code' => '20000',
                'country' => 'MA',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors(['district_id']);

        $address = $order->fresh()->shippingAddress;

        $this->assertSame('Achakkar', $address->city);
        $this->assertSame('Achakkar', $address->state);
    }

    public function test_order_show_renders_a_collapsed_sendit_delivery_editor_with_district_repricing_controls(): void
    {
        $staffUser = $this->makeShippingUser();
        Permission::findOrCreate('orders.view', 'web');
        $staffUser->givePermissionTo('orders.view');
        $customer = User::factory()->customer()->create(['status' => 'active']);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-order-editor',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-editor',
                'secret_key' => 'secret-editor',
            ],
            'settings' => [
                'pickup_district_id' => 18,
            ],
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '777',
            'city' => 'Casablanca',
            'district_name' => 'Ben msik',
            'price' => 55,
            'estimated_delivery' => '24h - 48h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-order-editor',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-EDITOR-001');
        $order->update([
            'shipping_method' => $shippingMethod->name,
            'shipping_method_id' => $shippingMethod->id,
        ]);
        $order->addresses()->create([
            ...$this->addressPayload('shipping'),
            'city' => 'Casablanca',
            'state' => 'Ben msik',
            'country' => 'MA',
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'external_reference' => 'DH-EDITOR-1001',
            'tracking_number' => 'DH-EDITOR-1001',
            'external_status' => 'PENDING',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.show', $order));

        $response
            ->assertOk()
            ->assertSee('Edit &amp; Reprice', false)
            ->assertSee('Destination District')
            ->assertSee('Projected grand total')
            ->assertSee('Sendit district-aware delivery editor');
    }

    public function test_order_cancellation_deletes_the_sendit_parcel_before_marking_the_order_cancelled(): void
    {
        Permission::findOrCreate('orders.override_status', 'web');
        Permission::findOrCreate('shipping.update', 'web');

        $staffUser = User::factory()->staff()->create([
            'status' => 'active',
        ]);
        $staffUser->givePermissionTo(['orders.override_status', 'shipping.update']);

        $customer = User::factory()->customer()->create(['status' => 'active']);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-order-cancel',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-order-cancel',
                'secret_key' => 'secret-order-cancel',
            ],
            'settings' => [
                'pickup_district_id' => 18,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-order-cancel',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-CANCEL-ORDER-001');
        $order->update(['status' => OrderStatus::PREPARING]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'external_reference' => 'DH-CANCEL-ORDER-1001',
            'tracking_number' => 'DH-CANCEL-ORDER-1001',
            'external_status' => 'TO_PICKUP',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-order-cancel-token'],
            ], 200),
            'https://app.sendit.ma/api/v1/deliveries/DH-CANCEL-ORDER-1001' => Http::response([
                'success' => true,
                'message' => 'Colis supprimé avec succes.',
            ], 200),
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.orders.status', $order), [
                'status' => OrderStatus::CANCELLED->value,
                'notes' => 'Customer cancelled before pickup',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertSame(ShipmentStatus::CANCELLED, $shipment->fresh()->status);
        $this->assertSame('DELETED', $shipment->fresh()->external_status);
    }

    private function makeOrder(User $customer, string $referenceNumber): Order
    {
        return Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 25,
            'discount_total' => 0,
            'grand_total' => 125,
        ]);
    }

    private function addressPayload(string $type): array
    {
        return [
            'type' => $type,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'address_line_1' => '12 Atlas Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
            'phone' => '+212600000001',
        ];
    }

    private function makeShippingUser(bool $canManageCarriers = false): User
    {
        foreach (['shipping.viewAny', 'shipping.update', 'shipping.manage_carriers'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $permissions = ['shipping.viewAny', 'shipping.update'];

        if ($canManageCarriers) {
            $permissions[] = 'shipping.manage_carriers';
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
