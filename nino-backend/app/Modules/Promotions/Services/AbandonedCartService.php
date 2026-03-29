<?php

namespace App\Modules\Promotions\Services;

use App\Modules\Finance\Services\FinanceSettingsService;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Services\NotificationTriggerService;
use App\Modules\Promotions\Models\AbandonedCart;
use Illuminate\Support\Facades\Log;

/**
 * Abandoned Cart Service
 *
 * Captures cart snapshots, identifies recoverable carts,
 * and triggers recovery notifications via the notification system.
 */
class AbandonedCartService
{
    public function __construct(
        private readonly FinanceSettingsService $financeSettings,
    ) {}

    /**
     * Capture a cart as abandoned.
     */
    public function capture(array $data): AbandonedCart
    {
        // Check for existing active abandoned cart for this session/customer
        $existing = AbandonedCart::where(function ($q) use ($data) {
            if (!empty($data['customer_id'])) {
                $q->where('customer_id', $data['customer_id']);
            } elseif (!empty($data['session_id'])) {
                $q->where('session_id', $data['session_id']);
            }
        })->where('status', 'abandoned')->first();

        if ($existing) {
            // Update existing abandoned cart with new snapshot
            $existing->update([
                'cart_items' => $data['cart_items'],
                'cart_total' => $data['cart_total'],
                'items_count' => $data['items_count'],
                'email' => $data['email'] ?? $existing->email,
                'abandoned_at' => now(),
            ]);
            return $existing;
        }

        return AbandonedCart::create([
            'customer_id' => $data['customer_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'email' => $data['email'] ?? null,
            'cart_items' => $data['cart_items'],
            'cart_total' => $data['cart_total'],
            'items_count' => $data['items_count'],
            'currency' => $data['currency'] ?? $this->financeSettings->baseCurrency(),
            'status' => 'abandoned',
            'abandoned_at' => now(),
        ]);
    }

    /**
     * Process recoverable carts — send recovery notifications.
     * Designed to be called from a scheduled job (e.g., hourly).
     */
    public function processRecovery(): int
    {
        $carts = AbandonedCart::recoverable()
            ->where(function ($q) {
                // Only carts older than 1 hour (give customer time to return)
                $q->where('abandoned_at', '<=', now()->subHour());
                // Haven't been notified in the last 24 hours
                $q->where(function ($q2) {
                    $q2->whereNull('last_notified_at')
                        ->orWhere('last_notified_at', '<=', now()->subHours(24));
                });
            })
            ->get();

        $count = 0;

        foreach ($carts as $cart) {
            $email = $cart->email ?? $cart->customer?->email;
            if (!$email) continue;

            try {
                $triggerService = app(NotificationTriggerService::class);
                $triggerService->abandonedCart(
                    $email,
                    $cart->customer?->first_name ?? 'Customer',
                    [
                        'items_count' => $cart->items_count,
                        'total' => number_format($cart->cart_total, 2),
                        'url' => config('app.url') . '/cart',
                    ],
                    $cart->customer_id,
                );

                $cart->markNotified();
                $count++;
            } catch (\Throwable $e) {
                Log::error('[AbandonedCart] Recovery notification failed', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Mark a cart as recovered when the customer completes checkout.
     */
    public function recover(int $customerId, int $orderId): void
    {
        $cart = AbandonedCart::where('customer_id', $customerId)
            ->whereIn('status', ['abandoned', 'notified'])
            ->latest('abandoned_at')
            ->first();

        if ($cart) {
            $cart->markRecovered($orderId);
        }
    }
}
