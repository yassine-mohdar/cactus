<?php

namespace App\Modules\Checkout\Services;

use App\Models\User;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Customers\Services\CustomerAccountService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected CustomerAccountService $customerAccountService,
        // protected InventoryService $inventoryService // To be wired to Phase 5
    ) {}

    /**
     * @throws Exception
     */
    public function processCheckout(array $data, ?User $user, ?string $sessionId): Order
    {
        return DB::transaction(function () use ($data, $user, $sessionId) {
            
            // 1. Resolve Cart
            $cart = $this->cartService->getCart($user, $sessionId);
            
            if ($cart->items->isEmpty()) {
                throw new Exception('Cannot process checkout with an empty cart.');
            }

            // 2. Reserve Stock (Foundation link to Phase 5)
            // foreach ($cart->items as $item) {
            //     $this->inventoryService->reserveStock($item->product_id, $item->variant_id, $item->quantity);
            // }

            // 3. Auto-Account generation for guests
            if (!$user) {
                // Ensure array data exists before calling the auto-generator
                $guestData = [
                    'email' => $data['customer_email'],
                    'first_name' => $data['customer_first_name'],
                    'last_name' => $data['customer_last_name'],
                ];
                $user = $this->customerAccountService->createFromCheckout($guestData);
            }

            // 4. Calculate Final Totals from CartService
            $cartSummary = $this->cartService->getSummary($cart);

            // 5. Build Master Order Record
            $order = Order::create([
                'reference_number' => 'ORD-' . strtoupper(uniqid()), // Simple reference foundation
                'customer_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'currency' => $cartSummary['currency'],
                'subtotal' => $cartSummary['totals']['subtotal'],
                'shipping_total' => $cartSummary['totals']['shipping_estimate'],
                'discount_total' => $cartSummary['totals']['discount'],
                'grand_total' => $cartSummary['totals']['grand_total'],
                'payment_method' => $data['payment_method'],
                'shipping_method' => 'standard', // Foundation placeholder
            ]);

            // 6. Snapshot Line Items
            foreach ($cart->items as $item) {
                $order->lineItems()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product->name ?? 'Deleted Product', // Real snapshot
                    'sku' => $item->product->sku ?? null,
                    'unit_price' => $item->product->price ?? 0,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ]);
            }

            // 7. Snapshot Addresses
            $shipping = array_merge($data['shipping_address'], ['type' => 'shipping']);
            $billing = array_merge($data['billing_address'], ['type' => 'billing']);
            
            $order->addresses()->createMany([$shipping, $billing]);

            // 8. Clean up successful Cart
            $cart->delete();

            // Fire generic 'OrderCreated' event if webhooks/emails are established
            
            return $order;
        });
    }
}
