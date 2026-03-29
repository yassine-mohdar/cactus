<?php

namespace App\Modules\Checkout\Services;

use App\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Checkout\Exceptions\CheckoutException;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Customers\Models\Address;
use App\Modules\Customers\Services\CustomerAccountService;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Notifications\Services\NotificationTriggerService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Services\ShippingSettingsService;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected CustomerAccountService $customerAccountService,
        protected ShippingSettingsService $shippingSettings,
        protected InventoryService $inventoryService,
        protected NotificationTriggerService $notificationTriggerService,
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
                throw new CheckoutException(
                    'Cannot process checkout with an empty cart.',
                    'EMPTY_CART',
                    ['recovery' => 'Add at least one item to the cart before checkout.'],
                );
            }

            // 2. Auto-Account generation for guests
            if (!$user) {
                // Ensure array data exists before calling the auto-generator
                $guestData = [
                    'email' => $data['customer_email'],
                    'first_name' => $data['customer_first_name'],
                    'last_name' => $data['customer_last_name'],
                ];
                $user = $this->customerAccountService->createFromCheckout($guestData);
            }

            // 3. Calculate Final Totals from CartService
            $cartSummary = $this->cartService->getSummary($cart);

            // 4. Build Master Order Record
            $order = Order::create([
                'customer_id' => $user->id,
                'status' => $this->resolveInitialOrderStatus($data['payment_method'] ?? null),
                'currency' => $cartSummary['currency'],
                'subtotal' => $cartSummary['totals']['subtotal'],
                'tax_total' => $cartSummary['totals']['tax'],
                'shipping_total' => $cartSummary['totals']['shipping_estimate'],
                'discount_total' => $cartSummary['totals']['discount'],
                'grand_total' => $cartSummary['totals']['grand_total'],
                'payment_method' => $data['payment_method'],
                'shipping_method' => $this->shippingSettings->defaultMethodCode(),
            ]);

            // 5. Snapshot Line Items
            foreach ($cart->items as $item) {
                $order->lineItems()->create($this->buildLineItemSnapshot($item));
            }

            // 6. Reserve tracked inventory against the real order record.
            foreach ($cart->items as $item) {
                $stockItem = $this->resolveReservableStockItem($item);

                if (! $stockItem) {
                    continue;
                }

                $this->inventoryService->reserveStock(
                    $stockItem,
                    $item->quantity,
                    userId: $user?->id,
                    referenceType: Order::class,
                    referenceId: (string) $order->id,
                );
            }

            // 7. Snapshot Addresses
            $shipping = $this->buildAddressSnapshot($data, $user, 'shipping');
            $billing = $this->buildAddressSnapshot($data, $user, 'billing');
            
            $order->addresses()->createMany([$shipping, $billing]);

            // 8. Clean up successful Cart
            $cart->delete();

            $this->notificationTriggerService->orderPlaced($order);
            
            return $order;
        });
    }

    protected function resolveInitialOrderStatus(?string $paymentMethod): OrderStatus
    {
        return match ($paymentMethod) {
            null, '', 'cash_on_delivery' => OrderStatus::PENDING,
            default => OrderStatus::AWAITING_PAYMENT,
        };
    }

    protected function resolveReservableStockItem($cartItem): ?StockItem
    {
        $query = StockItem::query()->whereNull('branch_id');

        if ($cartItem->variant_id) {
            return $query
                ->where('product_variant_id', $cartItem->variant_id)
                ->first();
        }

        return $query
            ->where('product_id', $cartItem->product_id)
            ->whereNull('product_variant_id')
            ->first();
    }

    /**
     * @return array<string, int|float|string|null>
     */
    protected function buildLineItemSnapshot(CartItem $cartItem): array
    {
        $product = $cartItem->product;
        $variant = $cartItem->variant;
        $unitPrice = $variant?->effectivePrice()
            ?? ($product?->effectivePrice() ?? (float) ($product?->price ?? 0));

        return [
            'product_id' => $cartItem->product_id,
            'variant_id' => $cartItem->variant_id,
            'product_name' => $product?->name ?? 'Deleted Product',
            'variant_name' => $this->resolveVariantSnapshotName($variant),
            'sku' => $variant?->sku ?? $product?->sku,
            'unit_price' => round($unitPrice, 2),
            'quantity' => $cartItem->quantity,
            'line_total' => round($unitPrice * $cartItem->quantity, 2),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function buildAddressSnapshot(array $data, ?User $user, string $type): array
    {
        return array_merge(
            $this->resolveCheckoutAddress($data, $user, $type),
            ['type' => $type]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function resolveCheckoutAddress(array $data, ?User $user, string $type): array
    {
        $payloadKey = "{$type}_address";
        $addressIdKey = "{$type}_address_id";

        $payload = $data[$payloadKey] ?? null;

        if (is_array($payload)) {
            return $payload;
        }

        if ($user && isset($data[$addressIdKey])) {
            /** @var Address|null $address */
            $address = Address::query()
                ->whereKey($data[$addressIdKey])
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->first();

            if (! $address) {
                throw new CheckoutException(
                    ucfirst($type).' address could not be resolved for this customer.',
                    'CHECKOUT_ADDRESS_NOT_FOUND',
                    ['address_type' => $type],
                );
            }

            return [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'phone' => $address->phone,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
            ];
        }

        throw new CheckoutException(
            ucfirst($type).' address details are required for checkout.',
            'CHECKOUT_ADDRESS_REQUIRED',
            ['address_type' => $type],
        );
    }

    protected function resolveVariantSnapshotName(?ProductVariant $variant): ?string
    {
        if (! $variant) {
            return null;
        }

        $summary = trim($variant->optionSummary());

        if ($summary !== '') {
            return $summary;
        }

        return $variant->sku ?: null;
    }
}
