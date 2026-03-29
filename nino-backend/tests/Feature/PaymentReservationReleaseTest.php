<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentReservationReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_stripe_callback_releases_reserved_stock_for_the_order(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => ['secret_key' => 'sk_test_placeholder'],
            'metadata' => ['currency' => 'mad'],
        ]);

        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $product = Product::factory()->published()->create();
        $stockItem = StockItem::factory()->create([
            'product_id' => $product->id,
            'branch_id' => null,
            'quantity' => 9,
            'reserved_quantity' => 2,
            'status' => StockItem::STATUS_LOW_STOCK,
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-CB-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING,
            'currency' => 'MAD',
            'subtotal' => 100,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
            'payment_method' => 'stripe',
            'shipping_method' => 'standard',
        ]);

        $order->lineItems()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 50,
            'quantity' => 2,
            'line_total' => 100,
        ]);

        DB::table('payment_transactions')->insert([
            'order_id' => $order->id,
            'reference' => 'STRIPE-CANCELLED-001',
            'type' => 'payment',
            'gateway' => 'stripe',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'amount' => 100,
            'fee_amount' => 0,
            'net_amount' => 100,
            'currency' => 'MAD',
            'gateway_transaction_id' => 'STRIPE-CANCELLED-001',
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('payment.stripe.callback', [
            'ref' => 'STRIPE-CANCELLED-001',
            'status' => 'cancelled',
        ]))->assertRedirect(route('checkout.failed', [
            'ref' => 'STRIPE-CANCELLED-001',
            'error' => 'Payment was cancelled.',
        ]));

        $this->get(route('checkout.failed', [
            'ref' => 'STRIPE-CANCELLED-001',
            'error' => 'Payment was cancelled.',
        ]))
            ->assertOk()
            ->assertSeeText('We could not complete the payment.')
            ->assertSeeText('Order reference')
            ->assertSeeText($order->reference_number)
            ->assertSeeText('Payment reference')
            ->assertSeeText('STRIPE-CANCELLED-001')
            ->assertSeeText('Check order status')
            ->assertSeeText('Payment was cancelled.');

        $stockItem->refresh();
        $order->refresh();

        $this->assertSame(0, $stockItem->reserved_quantity);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $stockItem->id,
            'type' => 'release',
            'reason' => 'order_cancelled',
            'reference_type' => Order::class,
            'reference_id' => (string) $order->id,
        ]);
    }

    public function test_successful_payzone_callback_marks_the_related_order_as_paid_and_redirects_to_order_status(): void
    {
        NotificationTemplate::create([
            'event' => NotificationEvent::PAYMENT_SUCCESS,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Payment success email',
            'subject' => 'Payment received for {{order_reference}}',
            'body' => 'Paid via {{payment_method}} / {{transaction_id}}',
            'is_enabled' => true,
        ]);

        GatewaySetting::create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'payzone-api-key',
                'secret_key' => 'payzone-secret',
                'merchant_id' => 'merchant-001',
            ],
            'metadata' => ['currency' => 'MAD'],
        ]);

        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-PZ-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::AWAITING_PAYMENT,
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
            'payment_method' => 'payzone',
            'shipping_method' => 'standard',
        ]);

        DB::table('payment_transactions')->insert([
            'order_id' => $order->id,
            'reference' => 'PZ-CALLBACK-001',
            'type' => 'payment',
            'gateway' => 'payzone',
            'status' => 'pending',
            'payment_method' => 'payzone',
            'amount' => 100,
            'fee_amount' => 0,
            'net_amount' => 100,
            'currency' => 'MAD',
            'gateway_transaction_id' => 'PZ-CALLBACK-001',
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('payment.payzone.callback', [
            'ref' => 'PZ-CALLBACK-001',
            'status' => 'approved',
        ]))->assertRedirect(route('checkout.success', [
            'ref' => $order->reference_number,
        ]));

        $order->refresh();

        $this->assertSame(OrderStatus::PAID, $order->status);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::PAYMENT_SUCCESS->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $this->get(route('checkout.success', [
            'ref' => $order->reference_number,
        ]))
            ->assertOk()
            ->assertSeeText($order->reference_number)
            ->assertSeeText('Paid');
    }

    public function test_failed_payzone_callback_marks_the_order_failed_and_logs_a_payment_failed_notification(): void
    {
        NotificationTemplate::create([
            'event' => NotificationEvent::PAYMENT_FAILED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Payment failed email',
            'subject' => 'Payment failed for {{order_reference}}',
            'body' => 'Method {{payment_method}} / {{transaction_id}}',
            'is_enabled' => true,
        ]);

        GatewaySetting::create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'payzone-api-key',
                'secret_key' => 'payzone-secret',
                'merchant_id' => 'merchant-001',
            ],
            'metadata' => ['currency' => 'MAD'],
        ]);

        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email' => 'payment.failed@example.test',
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-PZ-F-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::AWAITING_PAYMENT,
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
            'payment_method' => 'payzone',
            'shipping_method' => 'standard',
        ]);

        DB::table('payment_transactions')->insert([
            'order_id' => $order->id,
            'reference' => 'PZ-FAILED-001',
            'type' => 'payment',
            'gateway' => 'payzone',
            'status' => 'pending',
            'payment_method' => 'payzone',
            'amount' => 100,
            'fee_amount' => 0,
            'net_amount' => 100,
            'currency' => 'MAD',
            'gateway_transaction_id' => 'PZ-FAILED-001',
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('payment.payzone.callback', [
            'ref' => 'PZ-FAILED-001',
            'status' => 'failed',
        ]))->assertRedirect(route('checkout.failed', [
            'ref' => 'PZ-FAILED-001',
            'error' => 'Payment was not completed.',
        ]));

        $order->refresh();

        $this->assertSame(OrderStatus::FAILED, $order->status);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::PAYMENT_FAILED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);
    }
}
