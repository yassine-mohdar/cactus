<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Exceptions\UnsupportedPaymentGatewayException;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use Illuminate\Contracts\Container\Container;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    private const GATEWAY_MAP = [
        'cmi' => CmiPaymentGateway::class,
        'payzone' => PayzonePaymentGateway::class,
        'stripe' => StripePaymentGateway::class,
        'offline_transfer' => OfflinePaymentGateway::class,
    ];

    /**
     * @var array<string, string>
     */
    private const PAYMENT_METHOD_MAP = [
        'cmi' => 'cmi',
        'payzone' => 'payzone',
        'stripe' => 'stripe',
        'offline_transfer' => 'offline_transfer',
        'bank_transfer' => 'offline_transfer',
    ];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * @return list<string>
     */
    public function supportedGatewayIds(): array
    {
        return array_keys(self::GATEWAY_MAP);
    }

    public function has(string $gatewayId): bool
    {
        return array_key_exists($gatewayId, self::GATEWAY_MAP);
    }

    /**
     * Resolve a gateway adapter by stable gateway id.
     */
    public function resolve(string $gatewayId): PaymentGatewayInterface
    {
        $gatewayClass = self::GATEWAY_MAP[$gatewayId] ?? null;

        if (! $gatewayClass) {
            throw UnsupportedPaymentGatewayException::forGateway($gatewayId);
        }

        /** @var PaymentGatewayInterface $gateway */
        $gateway = $this->container->make($gatewayClass);

        return $gateway;
    }

    /**
     * Resolve the adapter that should handle a checkout payment method.
     * Returns null for non-gateway payment methods such as cash on delivery.
     */
    public function resolveForPaymentMethod(?string $paymentMethod): ?PaymentGatewayInterface
    {
        $gatewayId = $this->normalizePaymentMethod($paymentMethod);

        return $gatewayId ? $this->resolve($gatewayId) : null;
    }

    public function normalizePaymentMethod(?string $paymentMethod): ?string
    {
        $paymentMethod = strtolower(trim((string) $paymentMethod));

        if ($paymentMethod === '' || $paymentMethod === 'cash_on_delivery') {
            return null;
        }

        return self::PAYMENT_METHOD_MAP[$paymentMethod] ?? null;
    }
}
