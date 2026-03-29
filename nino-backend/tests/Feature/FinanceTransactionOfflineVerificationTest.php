<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinanceTransactionOfflineVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_offline_transfer_can_be_verified_and_promotes_order_to_paid(): void
    {
        $staff = $this->makeFinanceUser();
        $order = $this->makeAwaitingPaymentOrder();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'gateway' => 'offline_transfer',
            'amount' => 250,
            'currency' => 'MAD',
            'gateway_reference' => 'OFFLINE-VERIFY-001',
            'metadata' => [],
        ]);

        $this->actingAs($staff)
            ->post(route('admin.finance.transactions.verify-offline', $transaction), [
                'notes' => 'Bank transfer confirmed manually.',
            ])
            ->assertRedirect(route('admin.finance.transactions.show', $transaction));

        $transaction->refresh();
        $order->refresh();

        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertSame($staff->id, $transaction->processed_by);
        $this->assertSame('verified', data_get($transaction->metadata, 'offline_review.decision'));
        $this->assertSame(OrderStatus::PAID, $order->status);
    }

    public function test_pending_offline_transfer_can_be_failed_and_promotes_order_to_failed(): void
    {
        $staff = $this->makeFinanceUser();
        $order = $this->makeAwaitingPaymentOrder();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'gateway' => 'offline_transfer',
            'amount' => 250,
            'currency' => 'MAD',
            'gateway_reference' => 'OFFLINE-FAIL-001',
            'metadata' => [],
        ]);

        $this->actingAs($staff)
            ->post(route('admin.finance.transactions.fail-offline', $transaction), [
                'notes' => 'No incoming transfer was found for the supplied receipt.',
            ])
            ->assertRedirect(route('admin.finance.transactions.show', $transaction));

        $transaction->refresh();
        $order->refresh();

        $this->assertSame(TransactionStatus::FAILED, $transaction->status);
        $this->assertSame($staff->id, $transaction->processed_by);
        $this->assertSame('failed', data_get($transaction->metadata, 'offline_review.decision'));
        $this->assertSame('No incoming transfer was found for the supplied receipt.', $transaction->failure_reason);
        $this->assertSame(OrderStatus::FAILED, $order->status);
    }

    public function test_gateway_credentials_are_encrypted_at_rest(): void
    {
        $gateway = \App\Modules\Payments\Models\GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'live',
            'credentials' => [
                'secret_key' => 'sk_live_super_secret_value',
                'webhook_secret' => 'whsec_super_secret_value',
            ],
            'metadata' => [
                'publishable_key' => 'pk_live_value',
                'currency' => 'MAD',
            ],
        ]);

        $rawCredentials = $gateway->getRawOriginal('credentials');

        $this->assertIsString($rawCredentials);
        $this->assertStringNotContainsString('sk_live_super_secret_value', $rawCredentials);
        $this->assertStringNotContainsString('whsec_super_secret_value', $rawCredentials);
        $this->assertSame('sk_live_super_secret_value', $gateway->fresh()->getCredential('secret_key'));
    }

    private function makeFinanceUser(): User
    {
        Permission::findOrCreate('finance.viewAny', 'web');

        $user = User::factory()->staff()->create([
            'status' => 'active',
        ]);

        $user->givePermissionTo('finance.viewAny');

        return $user;
    }

    private function makeAwaitingPaymentOrder(): Order
    {
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        return Order::create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::AWAITING_PAYMENT,
            'currency' => 'MAD',
            'subtotal' => 200,
            'tax_total' => 0,
            'shipping_total' => 50,
            'discount_total' => 0,
            'grand_total' => 250,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'shipping_method' => 'standard',
        ]);
    }
}
