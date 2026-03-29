<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Jobs\SendNotificationJob;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\NotificationDispatcher;
use App\Modules\Notifications\Services\NotificationTriggerService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderLineItem;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_placed_trigger_creates_queued_logs_with_rendered_template_content(): void
    {
        Queue::fake();

        NotificationTemplate::create([
            'event' => NotificationEvent::ORDER_PLACED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Order placed email',
            'subject' => 'Order {{order_reference}} received',
            'body' => 'Hi {{customer_name}}, total {{order_total}} {{order_currency}}.',
            'is_enabled' => true,
        ]);

        $order = $this->createOrder('ORD-NOTIFY-1001', 285.00);
        OrderLineItem::create([
            'order_id' => $order->id,
            'product_name' => 'Trigger Plush',
            'sku' => 'TRIGGER-001',
            'unit_price' => 120.00,
            'quantity' => 2,
            'line_total' => 240.00,
        ]);

        $logs = app(NotificationTriggerService::class)->orderPlaced($order);

        $this->assertCount(1, $logs);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_PLACED->value,
            'channel' => NotificationChannel::EMAIL->value,
            'status' => NotificationStatus::QUEUED->value,
            'recipient' => 'customer@example.test',
            'customer_id' => $order->customer_id,
            'order_reference' => 'ORD-NOTIFY-1001',
        ]);

        /** @var NotificationLog $log */
        $log = NotificationLog::query()->firstOrFail();

        $this->assertSame('Order ORD-NOTIFY-1001 received', $log->subject);
        $this->assertStringContainsString('Hi Yassine', $log->body);
        $this->assertSame('1', (string) ($log->variables['order_items_count'] ?? null));
        $this->assertSame('285.00', $log->variables['order_total'] ?? null);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->queue === config('performance.queues.notifications', 'notifications')
                && $job->afterCommit === true;
        });
    }

    public function test_payment_and_shipping_triggers_queue_expected_logs_with_context(): void
    {
        Queue::fake();

        NotificationTemplate::insert([
            [
                'event' => NotificationEvent::PAYMENT_FAILED->value,
                'channel' => NotificationChannel::EMAIL->value,
                'name' => 'Payment failed email',
                'subject' => 'Payment issue on {{order_reference}}',
                'body' => 'Method {{payment_method}}, transaction {{transaction_id}}.',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event' => NotificationEvent::ORDER_SHIPPED->value,
                'channel' => NotificationChannel::EMAIL->value,
                'name' => 'Order shipped email',
                'subject' => 'Shipment {{tracking_number}} is on the way',
                'body' => '{{carrier_name}} / {{tracking_url}} / {{estimated_delivery}}',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $order = $this->createOrder('ORD-NOTIFY-2001', 199.00);
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'status' => ShipmentStatus::DISPATCHED,
            'carrier_name' => 'Amana',
            'tracking_number' => 'AMANA-123456',
        ]);

        app(NotificationTriggerService::class)->paymentFailed($order, 'stripe', 'txn_001');
        app(NotificationTriggerService::class)->orderShipped($order, $shipment);

        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::PAYMENT_FAILED->value,
            'recipient' => 'customer@example.test',
            'order_reference' => 'ORD-NOTIFY-2001',
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_SHIPPED->value,
            'recipient' => 'customer@example.test',
            'order_reference' => 'ORD-NOTIFY-2001',
        ]);

        $paymentLog = NotificationLog::query()
            ->where('event', NotificationEvent::PAYMENT_FAILED)
            ->firstOrFail();
        $shippingLog = NotificationLog::query()
            ->where('event', NotificationEvent::ORDER_SHIPPED)
            ->firstOrFail();

        $this->assertSame('stripe', $paymentLog->variables['payment_method'] ?? null);
        $this->assertSame('txn_001', $paymentLog->variables['transaction_id'] ?? null);
        $this->assertStringContainsString('Payment issue on ORD-NOTIFY-2001', $paymentLog->subject ?? '');

        $this->assertSame('AMANA-123456', $shippingLog->variables['tracking_number'] ?? null);
        $this->assertSame('Amana', $shippingLog->variables['carrier_name'] ?? null);
        $this->assertStringContainsString('AMANA-123456', $shippingLog->subject ?? '');
        $this->assertStringContainsString('https://www.amana.ma', $shippingLog->body);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->queue === config('performance.queues.notifications', 'notifications')
                && $job->afterCommit === true;
        });
    }

    public function test_global_notification_channel_settings_can_disable_specific_channels(): void
    {
        Queue::fake();

        app(SettingsService::class)->setMany('notifications', [
            ['key' => 'email_enabled', 'value' => true, 'type' => 'boolean'],
            ['key' => 'sms_enabled', 'value' => false, 'type' => 'boolean'],
            ['key' => 'whatsapp_enabled', 'value' => true, 'type' => 'boolean'],
        ]);

        NotificationTemplate::insert([
            [
                'event' => NotificationEvent::ORDER_PLACED->value,
                'channel' => NotificationChannel::EMAIL->value,
                'name' => 'Order placed email',
                'subject' => 'Email {{order_reference}}',
                'body' => 'Email for {{customer_name}}',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event' => NotificationEvent::ORDER_PLACED->value,
                'channel' => NotificationChannel::SMS->value,
                'name' => 'Order placed sms',
                'subject' => null,
                'body' => 'SMS for {{customer_name}}',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $logs = app(NotificationDispatcher::class)->dispatch(
            NotificationEvent::ORDER_PLACED,
            'customer@example.test',
            [
                'customer_name' => 'Yassine',
                'customer_phone' => '+212600000111',
                'order_reference' => 'ORD-NOTIFY-3001',
            ],
        );

        $this->assertCount(1, $logs);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_PLACED->value,
            'channel' => NotificationChannel::EMAIL->value,
            'order_reference' => 'ORD-NOTIFY-3001',
        ]);
        $this->assertDatabaseMissing('notification_logs', [
            'event' => NotificationEvent::ORDER_PLACED->value,
            'channel' => NotificationChannel::SMS->value,
            'order_reference' => 'ORD-NOTIFY-3001',
        ]);
    }

    public function test_notification_global_settings_append_footer_and_signature_to_rendered_body(): void
    {
        Queue::fake();

        app(SettingsService::class)->setMany('notifications', [
            ['key' => 'sender_name', 'value' => 'NinoWorld Ops', 'type' => 'string'],
            ['key' => 'footer_text', 'value' => 'Thank you for shopping with NinoWorld.', 'type' => 'string'],
            ['key' => 'signature', 'value' => 'The NinoWorld Team', 'type' => 'string'],
        ]);

        NotificationTemplate::create([
            'event' => NotificationEvent::WELCOME,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Welcome email',
            'subject' => 'Welcome {{customer_name}}',
            'body' => 'Hello {{customer_name}}',
            'is_enabled' => true,
        ]);

        $logs = app(NotificationDispatcher::class)->dispatch(
            NotificationEvent::WELCOME,
            'customer@example.test',
            [
                'customer_name' => 'Yassine',
                'customer_email' => 'customer@example.test',
            ],
        );

        $this->assertCount(1, $logs);

        $log = NotificationLog::query()->firstOrFail();

        $this->assertStringContainsString('Hello Yassine', $log->body);
        $this->assertStringContainsString('Thank you for shopping with NinoWorld.', $log->body);
        $this->assertStringContainsString('The NinoWorld Team', $log->body);
        $this->assertSame('NinoWorld Ops', $log->variables['notification_sender_name'] ?? null);
        $this->assertSame('Thank you for shopping with NinoWorld.', $log->variables['notification_footer_text'] ?? null);
        $this->assertSame('The NinoWorld Team', $log->variables['notification_signature'] ?? null);
    }

    public function test_notification_queue_settings_apply_to_jobs_and_log_retry_defaults(): void
    {
        Queue::fake();

        app(SettingsService::class)->setMany('notifications', [
            ['key' => 'queue_name', 'value' => 'notifications-critical', 'type' => 'string'],
            ['key' => 'max_attempts', 'value' => 5, 'type' => 'integer'],
            ['key' => 'retry_base_delay_minutes', 'value' => 4, 'type' => 'integer'],
        ]);

        NotificationTemplate::create([
            'event' => NotificationEvent::WELCOME,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Queued welcome email',
            'subject' => 'Welcome {{customer_name}}',
            'body' => 'Hello {{customer_name}}',
            'is_enabled' => true,
        ]);

        $logs = app(NotificationDispatcher::class)->dispatch(
            NotificationEvent::WELCOME,
            'customer@example.test',
            [
                'customer_name' => 'Queue Test',
                'customer_email' => 'customer@example.test',
            ],
        );

        $this->assertCount(1, $logs);
        $this->assertSame(5, $logs[0]->max_attempts);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->queue === 'notifications-critical'
                && $job->afterCommit === true;
        });
    }

    private function createOrder(string $referenceNumber, float $grandTotal): Order
    {
        $customer = User::factory()->create([
            'name' => 'Yassine Bennani',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'email' => 'customer@example.test',
            'phone' => '0612345678',
            'type' => 'customer',
            'status' => 'active',
        ]);

        return Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => OrderStatus::PAID,
            'currency' => 'MAD',
            'subtotal' => $grandTotal - 20.00,
            'tax_total' => 0,
            'shipping_total' => 20.00,
            'discount_total' => 0,
            'grand_total' => $grandTotal,
            'payment_method' => 'card',
            'shipping_method' => 'standard',
        ]);
    }
}
