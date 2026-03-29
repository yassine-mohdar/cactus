<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentTransaction;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfflinePaymentGateway implements PaymentGatewayInterface
{
    private const GATEWAY_ID = 'offline_transfer';
    private const DEFAULT_METADATA = [
        'method_label' => 'Bank Transfer',
        'checkout_title' => 'Bank transfer instructions',
        'checkout_description' => 'Complete the transfer using the company account below, then keep your reference for manual verification.',
        'instructions' => 'Please complete the transfer using the bank details below and send the receipt to the finance team.',
        'admin_instructions' => 'Finance should verify the transfer reference, confirm the credited amount, and only then mark the payment as completed.',
        'payment_window_hours' => 48,
        'reference_prefix' => 'NINO',
        'require_receipt' => true,
    ];

    public function gatewayId(): string
    {
        return self::GATEWAY_ID;
    }

    public function initiatePayment(Order $order): PaymentResponse
    {
        $setting = GatewaySetting::query()->firstOrCreate(
            ['gateway_id' => self::GATEWAY_ID],
            [
                'name' => 'Offline Bank Transfer',
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => [],
                'metadata' => self::DEFAULT_METADATA,
            ],
        );
        $config = $this->resolveMetadata($setting);
        $instructions = $this->buildInstructions($config);

        $reference = 'OFFLINE-' . now()->format('Ymd') . '-' . Str::random(6);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'gateway' => self::GATEWAY_ID,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => 'pending', // Awaiting manual admin verification
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => $reference,
            'payload' => [
                'instructions_shown' => $instructions,
                'offline_method' => $this->checkoutDetails($setting, $reference),
            ],
        ]);

        return new PaymentResponse(
            isSuccessful: true,
            status: 'pending', // Not 'captured', as funds aren't technically settled
            gatewayReference: $reference,
            message: $instructions,
            rawPayload: array_merge($transaction->toArray(), [
                'offline_method' => $this->checkoutDetails($setting, $reference),
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutDetails(?GatewaySetting $setting = null, ?string $reference = null): array
    {
        $setting ??= GatewaySetting::query()->where('gateway_id', self::GATEWAY_ID)->first();
        $metadata = $this->resolveMetadata($setting);

        return [
            'gateway' => self::GATEWAY_ID,
            'method_code' => 'bank_transfer',
            'method_label' => $metadata['method_label'],
            'checkout_title' => $metadata['checkout_title'],
            'checkout_description' => $metadata['checkout_description'],
            'instructions' => $this->buildInstructions($metadata),
            'admin_instructions' => $metadata['admin_instructions'],
            'bank_name' => $metadata['bank_name'],
            'account_holder' => $metadata['account_holder'],
            'account_number' => $metadata['account_number'],
            'iban' => $metadata['iban'],
            'swift_code' => $metadata['swift_code'],
            'reference_prefix' => $metadata['reference_prefix'],
            'payment_window_hours' => $metadata['payment_window_hours'],
            'require_receipt' => $metadata['require_receipt'],
            'payment_reference' => $reference,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function buildInstructions(array $metadata): string
    {
        $methodLabel = trim((string) ($metadata['method_label'] ?? 'Bank Transfer'));
        $intro = trim((string) ($metadata['instructions'] ?? 'Please complete your offline payment using the banking details below.'));
        $bankName = trim((string) ($metadata['bank_name'] ?? ''));
        $accountHolder = trim((string) ($metadata['account_holder'] ?? ''));
        $accountNumber = trim((string) ($metadata['account_number'] ?? ''));
        $iban = trim((string) ($metadata['iban'] ?? ''));
        $swiftCode = trim((string) ($metadata['swift_code'] ?? ''));
        $referencePrefix = trim((string) ($metadata['reference_prefix'] ?? ''));
        $paymentWindowHours = max(0, (int) ($metadata['payment_window_hours'] ?? 0));
        $requireReceipt = filter_var($metadata['require_receipt'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $lines = [
            $methodLabel . ': ' . $intro,
        ];

        if ($bankName !== '') {
            $lines[] = 'Bank: ' . $bankName;
        }

        if ($accountHolder !== '') {
            $lines[] = 'Account Holder: ' . $accountHolder;
        }

        if ($accountNumber !== '') {
            $lines[] = 'Account Number: ' . $accountNumber;
        }

        if ($iban !== '') {
            $lines[] = 'IBAN: ' . $iban;
        }

        if ($swiftCode !== '') {
            $lines[] = 'SWIFT: ' . $swiftCode;
        }

        if ($referencePrefix !== '') {
            $lines[] = 'Use payment reference prefix: ' . $referencePrefix;
        }

        if ($paymentWindowHours > 0) {
            $lines[] = 'Payment window: ' . $paymentWindowHours . ' hours';
        }

        if ($requireReceipt) {
            $lines[] = 'Receipt required: Please share proof of payment after the transfer.';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMetadata(?GatewaySetting $setting): array
    {
        $metadata = array_merge(self::DEFAULT_METADATA, $setting?->metadata ?? []);

        return [
            'method_label' => trim((string) ($metadata['method_label'] ?? self::DEFAULT_METADATA['method_label'])),
            'checkout_title' => trim((string) ($metadata['checkout_title'] ?? self::DEFAULT_METADATA['checkout_title'])),
            'checkout_description' => trim((string) ($metadata['checkout_description'] ?? self::DEFAULT_METADATA['checkout_description'])),
            'instructions' => trim((string) ($metadata['instructions'] ?? self::DEFAULT_METADATA['instructions'])),
            'admin_instructions' => trim((string) ($metadata['admin_instructions'] ?? self::DEFAULT_METADATA['admin_instructions'])),
            'bank_name' => trim((string) ($metadata['bank_name'] ?? '')),
            'account_holder' => trim((string) ($metadata['account_holder'] ?? '')),
            'account_number' => trim((string) ($metadata['account_number'] ?? '')),
            'iban' => trim((string) ($metadata['iban'] ?? '')),
            'swift_code' => trim((string) ($metadata['swift_code'] ?? '')),
            'reference_prefix' => trim((string) ($metadata['reference_prefix'] ?? self::DEFAULT_METADATA['reference_prefix'])),
            'payment_window_hours' => max(1, (int) ($metadata['payment_window_hours'] ?? self::DEFAULT_METADATA['payment_window_hours'])),
            'require_receipt' => filter_var($metadata['require_receipt'] ?? self::DEFAULT_METADATA['require_receipt'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function verifyPayment(Request $request): PaymentResponse
    {
        // Offline payments don't have automated browser callbacks. This is a no-op protecting the interface.
        return PaymentResponse::failure('Offline methods do not support automated browser verification.');
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        // Offline payments don't have webhooks.
        return PaymentResponse::failure('Offline methods do not support automated webhooks.');
    }

    public function refund(string $gatewayReference, float $amount): PaymentResponse
    {
        // Refunding an offline payment requires the admin to manually send money back. We just update the DB status elsewhere.
        return PaymentResponse::failure('Offline methods cannot be refunded automatically. Please refund manually and update the status.');
    }
}
