<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderEndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_bank_transfer_order_can_progress_from_checkout_to_delivered_with_finance_shipping_and_inventory_in_sync(): void
    {
        Notification::fake();

        $this->seedLifecycleNotificationTemplates();
        $this->configureOfflineTransferGateway();

        $product = $this->createProduct('atlas-floor-lamp', 220);
        $stockItem = StockItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'branch_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        $sessionId = 'e2e-order-flow-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $checkoutResponse = $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'order.flow@example.com',
                'customer_first_name' => 'Salma',
                'customer_last_name' => 'Workflow',
                'payment_method' => 'bank_transfer',
                'shipping_address' => $this->addressPayload('Salma', 'Workflow'),
                'billing_address' => $this->addressPayload('Salma', 'Workflow', [
                    'address_line_1' => '44 Billing Street',
                    'city' => 'Rabat',
                    'postal_code' => '10010',
                ]),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        );

        $checkoutResponse->assertCreated();
        $checkoutResponse->assertJsonPath('payment.gateway', 'offline_transfer');
        $checkoutResponse->assertJsonPath('payment.method_code', 'bank_transfer');

        $order = Order::query()->with(['customer', 'lineItems'])->firstOrFail();
        $customer = $order->customer;
        $transaction = PaymentTransaction::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertNotNull($customer);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->status);
        $this->assertSame(TransactionStatus::PENDING, $transaction->status);
        $this->assertSame('bank_transfer', $transaction->payment_method);
        $this->assertSame($order->payment_method_label, $transaction->payment_method_label);
        $this->assertSame('offline_transfer', $transaction->gateway);
        $this->assertSame($order->grand_total, $transaction->amount);
        $this->assertSame($order->id, $transaction->order_id);
        $this->assertSame($customer->id, $transaction->customer_id);

        $stockItem->refresh();
        $this->assertSame(10, $stockItem->quantity);
        $this->assertSame(2, $stockItem->reserved_quantity);
        $this->assertSame(8, $stockItem->available_quantity);

        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_PLACED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $financeUser = $this->makeStaffUser(['finance.viewAny', 'payments.viewAny', 'payments.refund']);

        $this->actingAs($financeUser)
            ->post(route('admin.finance.transactions.verify-offline', $transaction), [
                'notes' => 'Bank transfer matched successfully.',
            ])
            ->assertRedirect(route('admin.finance.transactions.show', $transaction));

        $order->refresh();
        $transaction->refresh();

        $this->assertSame(OrderStatus::PAID, $order->status);
        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertSame('verified', data_get($transaction->metadata, 'offline_review.decision'));
        $invoice = Invoice::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($transaction->id, $invoice->transaction_id);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::PAYMENT_SUCCESS->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $previewResponse = $this->actingAs($financeUser)
            ->get(route('admin.orders.invoice.preview', $order))
            ->assertOk()
            ->assertSeeText($invoice->invoice_number)
            ->assertSeeText($order->reference_number);

        $this->assertStringContainsString('Download PDF', $previewResponse->getContent());

        $pdfResponse = $this->actingAs($financeUser)->get(route('admin.orders.invoice', $order));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $pdfResponse->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());

        $shippingMethod = ShippingMethod::create([
            'name' => 'Express Delivery',
            'slug' => 'express-delivery',
            'carrier' => 'Amana',
            'base_cost' => 35,
            'estimated_days' => '1-2',
            'is_enabled' => true,
        ]);

        $shippingUser = $this->makeStaffUser(['shipping.viewAny', 'shipping.update']);

        $this->actingAs($shippingUser)
            ->post(route('admin.shipping.shipments.store'), [
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod->id,
                'carrier_name' => 'Amana',
                'tracking_number' => 'SHIP-E2E-1001',
            ])
            ->assertRedirect();

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->actingAs($shippingUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::READY_TO_SHIP->value,
                'notes' => 'Ready for picking',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);

        $this->actingAs($shippingUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::PACKED->value,
                'notes' => 'Packed and labelled',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->actingAs($shippingUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DISPATCHED->value,
                'notes' => 'Handed to carrier',
                'tracking_number' => 'SHIP-E2E-1001',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $order->refresh();
        $shipment->refresh();
        $stockItem->refresh();

        $this->assertSame(OrderStatus::SHIPPED, $order->status);
        $this->assertSame(8, $stockItem->quantity);
        $this->assertSame(0, $stockItem->reserved_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $stockItem->id,
            'reason' => 'shipment_dispatched',
            'reference_type' => Order::class,
            'reference_id' => (string) $order->id,
            'quantity' => -2,
            'quantity_after' => 8,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_SHIPPED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $this->actingAs($shippingUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DELIVERED->value,
                'notes' => 'Delivered to customer',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $order->refresh();
        $shipment->refresh();

        $this->assertSame(OrderStatus::DELIVERED, $order->status);
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->status);
        $this->assertNotNull($shipment->delivered_at);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_DELIVERED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.account.orders.show', $order))
            ->assertOk()
            ->assertSeeText($order->reference_number)
            ->assertSeeText('SHIP-E2E-1001');

        $this->actingAs($customer)
            ->getJson(route('api.customer.orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('tracking_number', 'SHIP-E2E-1001')
            ->assertJsonPath('status_raw', ShipmentStatus::DELIVERED->value);

        $customerPreviewResponse = $this->actingAs($customer)
            ->get(route('customer.account.orders.invoice.preview', $order))
            ->assertOk()
            ->assertSeeText($invoice->invoice_number)
            ->assertSeeText($order->reference_number);

        $this->assertStringContainsString('Download PDF', $customerPreviewResponse->getContent());

        $customerPdfResponse = $this->actingAs($customer)->get(route('customer.account.orders.invoice', $order));
        $customerPdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $customerPdfResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $customerPdfResponse->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', (string) $customerPdfResponse->getContent());

        $this->actingAs($financeUser)
            ->get(route('admin.finance.transactions.show', $transaction))
            ->assertOk()
            ->assertSeeText($order->reference_number)
            ->assertSeeText($transaction->reference);
    }

    private function seedLifecycleNotificationTemplates(): void
    {
        foreach ([
            NotificationEvent::ORDER_PLACED,
            NotificationEvent::WELCOME,
            NotificationEvent::PASSWORD_SETUP,
            NotificationEvent::PAYMENT_SUCCESS,
            NotificationEvent::ORDER_SHIPPED,
            NotificationEvent::ORDER_DELIVERED,
        ] as $event) {
            NotificationTemplate::create([
                'event' => $event,
                'channel' => NotificationChannel::EMAIL,
                'name' => $event->value.' email',
                'subject' => $event->value.' subject',
                'body' => $event->value.' body',
                'is_enabled' => true,
            ]);
        }
    }

    private function configureOfflineTransferGateway(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => true,
            'mode' => 'live',
            'credentials' => [],
            'metadata' => [
                'method_label' => 'Manual Bank Transfer',
                'checkout_title' => 'Pay by bank transfer',
                'checkout_description' => 'Use the company banking details below to complete payment.',
                'instructions' => 'Transfer the funds and include the reference shown here.',
                'bank_name' => 'Attijariwafa Bank',
                'account_holder' => 'NinoWorld SARL AU',
                'iban' => 'MA64001122334455667788990011',
                'payment_window_hours' => 72,
                'reference_prefix' => 'NW',
                'require_receipt' => true,
            ],
        ]);
    }

    private function createProduct(string $slug, float $price): Product
    {
        return Product::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'type' => 'simple',
            'status' => 'published',
            'sku' => strtoupper(str_replace('-', '_', $slug)),
            'price' => $price,
            'quantity' => 20,
        ]);
    }

    private function makeStaffUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->staff()->create([
            'status' => 'active',
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function addressPayload(string $firstName, string $lastName, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ], $overrides);
    }
}
