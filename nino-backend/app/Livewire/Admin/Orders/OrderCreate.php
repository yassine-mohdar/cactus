<?php

namespace App\Livewire\Admin\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Customers\Services\CustomerAccountService;
use App\Modules\Finance\Services\FinanceSettingsService;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPostCreationAutomationService;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Promotions\Services\CouponService;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Shipping\Services\ShippingSettingsService;
use App\Support\GlobalAddressDirectory;
use App\Support\InternationalDirectory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrderCreate extends Component
{
    // Customer
    public $customer_id = '';
    public $search_customer = '';
    public $show_create_customer_form = false;
    public $new_customer = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
    ];
    public $customer_form_feedback = null;
    
    // Items
    public $items = []; // ['product_id', 'variant_id', 'quantity', 'price', 'name']
    
    // Addresses
    public $shipping_address = [
        'first_name' => '', 'last_name' => '', 'phone' => '',
        'address_line_1' => '', 'address_line_2' => '',
        'city' => '', 'state' => '', 'postal_code' => '', 'country' => 'MA'
    ];
    public $billing_address = [
        'first_name' => '', 'last_name' => '', 'phone' => '',
        'address_line_1' => '', 'address_line_2' => '',
        'city' => '', 'state' => '', 'postal_code' => '', 'country' => 'MA'
    ];
    public $same_as_shipping = true;

    // Methods & Notes
    public $payment_method = 'cod';
    public $coupon_code = '';
    public $applied_coupon_code = '';
    public $coupon_feedback = null;
    public $shipping_method_id = '';
    public $admin_notes = '';
    public $shipping_phone_country = 'MA';
    public $shipping_phone_local = '';
    public $billing_phone_country = 'MA';
    public $billing_phone_local = '';
    public $shipping_destination_id = '';
    public $billing_destination_id = '';

    public function mount()
    {
        $this->addItem();
        $this->hydratePhoneFields();
        $this->ensureShippingMethodSelection(
            $this->enabledShippingMethodsCollection()
        );
    }

    public function addItem()
    {
        $this->items[] = [
            'product_id' => '',
            'variant_id' => null,
            'quantity' => 1,
            'price' => 0,
            'name' => '',
            'sku' => null,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key)
    {
        // If product_id changed, update price and name
        if (str_ends_with($key, '.product_id')) {
            $index = explode('.', $key)[0];
            $product = Product::find($value);
            if ($product) {
                $this->items[$index]['price'] = $product->price;
                $this->items[$index]['name'] = $product->name;
                $this->items[$index]['sku'] = $product->sku;
            } else {
                $this->items[$index]['price'] = 0;
                $this->items[$index]['name'] = '';
                $this->items[$index]['sku'] = null;
            }
        }
    }

    public function getSubtotalProperty()
    {
        return collect($this->items)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getGrandTotalProperty()
    {
        return max(0, $this->subtotal - $this->discount_total + $this->shipping_total);
    }

    public function getShippingTotalProperty()
    {
        return round((float) ($this->shippingQuote['cost'] ?? 0), 2);
    }

    public function getDiscountTotalProperty(): float
    {
        return round((float) ($this->appliedCoupon['discount'] ?? 0), 2);
    }

    public function getShippingQuoteProperty(): array
    {
        return $this->buildShippingQuote($this->selectedShippingMethod);
    }

    public function getSelectedShippingMethodProperty(): ?ShippingMethod
    {
        if (! filled($this->shipping_method_id)) {
            return null;
        }

        return $this->enabledShippingMethodsCollection()
            ->firstWhere('id', (int) $this->shipping_method_id);
    }

    public function getSelectedCustomerProperty(): ?User
    {
        if (! filled($this->customer_id)) {
            return null;
        }

        return User::query()->find($this->customer_id);
    }

    public function getHasValidItemsProperty(): bool
    {
        return collect($this->items)->contains(function (array $item): bool {
            return filled($item['product_id'] ?? null)
                && (int) ($item['quantity'] ?? 0) > 0;
        });
    }

    public function getAppliedCouponProperty(): array
    {
        return $this->validateCouponCode($this->applied_coupon_code);
    }

    public function getAvailablePaymentMethodsProperty(): array
    {
        return app(PaymentMethodAvailabilityService::class)
            ->eligibleMethodOptions($this->selectedShippingMethod);
    }

    public function getCanSubmitProperty(): bool
    {
        if (! filled($this->customer_id) || ! $this->hasValidItems) {
            return false;
        }

        if (! filled($this->shipping_address['first_name'] ?? null)
            || ! filled($this->shipping_address['address_line_1'] ?? null)
            || ! filled($this->shipping_address['country'] ?? null)) {
            return false;
        }

        if (! $this->hasResolvedShippingDestination()) {
            return false;
        }

        if (! $this->selectedShippingMethod) {
            return false;
        }

        if (filled($this->applied_coupon_code) && ! ($this->appliedCoupon['valid'] ?? false)) {
            return false;
        }

        return ! ($this->shippingQuote['is_api'] && ! $this->shippingQuote['has_match']);
    }

    public function getShippingDestinationStatusProperty(): array
    {
        if ($this->isMoroccoShippingAddress()) {
            if ($this->hasResolvedShippingDestination()) {
                return [
                    'tone' => 'ready',
                    'title' => 'Destination confirmed',
                    'message' => trim((string) ($this->shipping_address['state'] ?: $this->shipping_address['city'] ?: 'Morocco district selected')),
                ];
            }

            return [
                'tone' => 'pending',
                'title' => 'District required',
                'message' => 'Choose the delivery district to unlock API pricing where needed.',
            ];
        }

        $countryCode = strtoupper(trim((string) ($this->shipping_address['country'] ?? '')));

        if ($countryCode === '') {
            return [
                'tone' => 'pending',
                'title' => 'Country required',
                'message' => 'Select a country to unlock the destination fields.',
            ];
        }

        if ($this->countryHasStates($countryCode) && ! filled($this->shipping_address['state'] ?? null)) {
            return [
                'tone' => 'pending',
                'title' => 'State required',
                'message' => 'Select the state or province before choosing the city.',
            ];
        }

        if (! filled($this->shipping_address['city'] ?? null)) {
            return [
                'tone' => 'pending',
                'title' => 'City required',
                'message' => 'Choose the destination city before selecting a shipping method.',
            ];
        }

        return [
            'tone' => 'ready',
            'title' => 'Destination confirmed',
            'message' => collect([
                $this->shipping_address['city'] ?? null,
                $this->shipping_address['state'] ?? null,
                $countryCode,
            ])->filter()->implode(' · '),
        ];
    }

    public function getCheckoutStatusProperty(): array
    {
        $selectedMethod = $this->selectedShippingMethod;
        $quote = $this->shippingQuote;
        $shippingDisplay = $this->shippingEstimateDisplay($selectedMethod, $quote);
        $totalDisplay = $this->grandTotalDisplay($selectedMethod, $quote);

        if (! filled($this->customer_id)) {
            return [
                'tone' => 'pending',
                'title' => 'Link or create customer',
                'message' => 'Search an existing customer or create a new one without leaving this page.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (! $this->hasValidItems) {
            return [
                'tone' => 'pending',
                'title' => 'Add basket items',
                'message' => 'Add at least one product line with a valid quantity.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (! filled($this->shipping_address['first_name'] ?? null)) {
            return [
                'tone' => 'pending',
                'title' => 'Add recipient name',
                'message' => 'Capture the delivery recipient before review.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (! filled($this->shipping_address['address_line_1'] ?? null)) {
            return [
                'tone' => 'pending',
                'title' => 'Add street address',
                'message' => 'Address line 1 is still required for the shipping label.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (! $this->hasResolvedShippingDestination()) {
            return [
                'tone' => 'pending',
                'title' => $this->shippingDestinationStatus['title'],
                'message' => $this->shippingDestinationStatus['message'],
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (! $selectedMethod) {
            return [
                'tone' => 'pending',
                'title' => 'Select shipping method',
                'message' => 'Choose the delivery method for this destination.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if ($quote['is_api'] && ! $quote['has_match']) {
            return [
                'tone' => 'pending',
                'title' => 'Pending district rate',
                'message' => $quote['message'] ?: 'The selected district still needs a matching live rate.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        if (filled($this->applied_coupon_code) && ! ($this->appliedCoupon['valid'] ?? false)) {
            return [
                'tone' => 'attention',
                'title' => 'Coupon requires attention',
                'message' => $this->appliedCoupon['error'] ?? 'The applied coupon is no longer valid for this basket.',
                'shipping_display' => $shippingDisplay,
                'grand_total_display' => $totalDisplay,
            ];
        }

        return [
            'tone' => 'ready',
            'title' => 'Ready to place order',
            'message' => 'Totals reflect the current basket, destination, and selected shipping method.',
            'shipping_display' => $shippingDisplay,
            'grand_total_display' => $totalDisplay,
        ];
    }

    public function selectCustomer($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->customer_id = $user->id;
            $this->search_customer = $user->full_name . " ({$user->email})";
            $this->show_create_customer_form = false;
            $this->customer_form_feedback = [
                'tone' => 'success',
                'message' => 'Customer linked to this order.',
            ];
            
            // Auto-fill names if empty
            $nameParts = explode(' ', $user->name, 2);
            $this->shipping_address['first_name'] = $nameParts[0] ?? '';
            $this->shipping_address['last_name'] = $nameParts[1] ?? '';
            $this->shipping_address['phone'] = $user->phone ?? '';
            $this->applyPhoneToFields('shipping', $this->shipping_address['phone'], $this->shipping_address['country']);
        }
    }

    public function startCreatingCustomer(): void
    {
        $this->syncInternationalPhones();
        $this->resetValidation();

        $this->show_create_customer_form = true;
        $this->seedInlineCustomerForm();
        $this->customer_form_feedback = null;
    }

    public function cancelCreatingCustomer(): void
    {
        $this->show_create_customer_form = false;
        $this->resetValidation();
    }

    public function createCustomer(): void
    {
        $this->syncInternationalPhones();
        $this->seedInlineCustomerForm();

        $validated = $this->validate([
            'new_customer.first_name' => 'required|string|max:255',
            'new_customer.last_name' => 'required|string|max:255',
            'new_customer.email' => 'required|email|max:255',
            'new_customer.phone' => 'nullable|string|max:50',
        ]);

        $email = Str::lower(trim((string) data_get($validated, 'new_customer.email')));
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if (! $existing->isCustomer()) {
                $this->addError('new_customer.email', 'This email already belongs to a staff account.');

                return;
            }

            $this->selectCustomer($existing->id);
            $this->customer_form_feedback = [
                'tone' => 'success',
                'message' => 'An existing customer matched this email and was linked to the order.',
            ];

            return;
        }

        $phone = $this->normalizeInlineCustomerPhone((string) data_get($validated, 'new_customer.phone', ''));

        try {
            $customer = app(CustomerAccountService::class)->createFromAdminOrder([
                'first_name' => (string) data_get($validated, 'new_customer.first_name'),
                'last_name' => (string) data_get($validated, 'new_customer.last_name'),
                'email' => $email,
                'phone' => $phone,
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->addError('new_customer.email', $exception->getMessage());

            return;
        } catch (Exception $exception) {
            $this->addError('new_customer.email', 'Customer could not be created right now. Please try again.');

            return;
        }

        $this->selectCustomer($customer->id);
        $this->customer_form_feedback = [
            'tone' => 'success',
            'message' => 'New customer created and linked to this order.',
        ];
    }

    public function updatedShippingPhoneCountry(): void
    {
        $this->syncAddressPhone('shipping');
    }

    public function updatedShippingPhoneLocal(): void
    {
        $this->syncAddressPhone('shipping');
    }

    public function updatedBillingPhoneCountry(): void
    {
        $this->syncAddressPhone('billing');
    }

    public function updatedBillingPhoneLocal(): void
    {
        $this->syncAddressPhone('billing');
    }

    public function updatedShippingAddressCountry($value): void
    {
        $this->handleCountryChange('shipping', $value);
        $this->ensureShippingMethodSelection($this->enabledShippingMethodsCollection());
    }

    public function updatedBillingAddressCountry($value): void
    {
        $this->handleCountryChange('billing', $value);
    }

    public function updatedShippingAddressState($value): void
    {
        $this->handleStateChange('shipping', $value);
    }

    public function updatedBillingAddressState($value): void
    {
        $this->handleStateChange('billing', $value);
    }

    public function updatedShippingDestinationId($value): void
    {
        $this->syncAddressFromDestination('shipping', $value);
    }

    public function updatedBillingDestinationId($value): void
    {
        $this->syncAddressFromDestination('billing', $value);
    }

    public function updatedShippingMethodId($value): void
    {
        if (! $this->isMoroccoShippingAddress()) {
            $this->ensurePaymentMethodSelection($this->availablePaymentMethods);
            return;
        }

        $method = $this->enabledShippingMethodsCollection()
            ->firstWhere('id', (int) $value);

        $currentDestinationId = trim((string) $this->shipping_destination_id);

        if ($currentDestinationId !== '' && $this->resolveMoroccoDistrictById($currentDestinationId)) {
            $this->syncAddressFromDestination('shipping', $currentDestinationId);
            $this->ensurePaymentMethodSelection($this->availablePaymentMethods);

            return;
        }

        $searchCandidates = collect([
            trim((string) ($this->shipping_address['state'] ?? '')),
            trim((string) ($this->shipping_address['city'] ?? '')),
        ])->filter()->unique()->values();

        foreach ($searchCandidates as $candidate) {
            $district = $method
                ? $this->matchCarrierDistrict($method, $candidate)
                : $this->resolveMoroccoDistrictByText($candidate);

            if (! $district) {
                continue;
            }

            $this->shipping_destination_id = (string) $district->id;
            $this->syncAddressFromDestination('shipping', $this->shipping_destination_id);

            $this->ensurePaymentMethodSelection($this->availablePaymentMethods);
            return;
        }

        $this->ensurePaymentMethodSelection($this->availablePaymentMethods);
    }

    public function updatedCouponCode($value): void
    {
        if (strtoupper(trim((string) $value)) !== strtoupper(trim((string) $this->applied_coupon_code))) {
            $this->coupon_feedback = null;
        }
    }

    public function applyCoupon(): void
    {
        if (! $this->hasValidItems) {
            $this->coupon_feedback = [
                'tone' => 'danger',
                'message' => 'Add at least one valid basket item before applying a coupon.',
            ];

            return;
        }

        $code = strtoupper(trim((string) $this->coupon_code));

        if ($code === '') {
            $this->coupon_feedback = [
                'tone' => 'danger',
                'message' => 'Enter a coupon code first.',
            ];

            return;
        }

        $validation = $this->validateCouponCode($code);

        if (! ($validation['valid'] ?? false)) {
            $this->coupon_feedback = [
                'tone' => 'danger',
                'message' => $validation['error'] ?? 'This coupon could not be applied.',
            ];

            return;
        }

        $this->coupon_code = $code;
        $this->applied_coupon_code = $code;
        $this->coupon_feedback = [
            'tone' => 'success',
            'message' => $code.' applied. '.$this->moneyLabel((float) ($validation['discount'] ?? 0)).' discount ready.',
        ];
    }

    public function removeCoupon(): void
    {
        $this->coupon_code = '';
        $this->applied_coupon_code = '';
        $this->coupon_feedback = null;
    }

    public function save()
    {
        $this->syncInternationalPhones();
        $this->ensurePaymentMethodSelection($this->availablePaymentMethods);

        $paymentMethodValues = collect($this->availablePaymentMethods)
            ->pluck('value')
            ->filter()
            ->values()
            ->all();

        $appliedCoupon = $this->appliedCoupon;

        if (filled($this->applied_coupon_code) && ! ($appliedCoupon['valid'] ?? false)) {
            $this->coupon_feedback = [
                'tone' => 'danger',
                'message' => $appliedCoupon['error'] ?? 'The applied coupon is no longer valid.',
            ];

            return null;
        }

        $this->validate([
            'customer_id' => 'required|exists:users,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => ['required', Rule::in($paymentMethodValues)],
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.address_line_1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.country' => 'required|string|size:2',
            'billing_address.country' => 'nullable|string|size:2',
        ]);

        $shippingMethod = ShippingMethod::query()
            ->enabled()
            ->with('shippingCarrier')
            ->find($this->shipping_method_id);

        if (! $shippingMethod) {
            $this->addError('shipping_method_id', 'Please select an enabled shipping method.');

            return null;
        }

        try {
            /** @var Order $order */
            $order = DB::transaction(function () use ($shippingMethod, $appliedCoupon) {
                $paymentSnapshot = app(PaymentMethodAvailabilityService::class)->snapshot($this->payment_method);

                $order = Order::create([
                    'reference_number' => 'ORD-' . strtoupper(uniqid()),
                    'customer_id' => $this->customer_id,
                    'status' => OrderStatus::PENDING,
                    'currency' => app(FinanceSettingsService::class)->baseCurrency(),
                    'subtotal' => $this->subtotal,
                    'tax_total' => 0,
                    'shipping_total' => $this->shipping_total,
                    'discount_total' => $this->discount_total,
                    'grand_total' => $this->grand_total,
                    'payment_method' => $paymentSnapshot['code'] ?? $this->payment_method,
                    'payment_method_id' => $paymentSnapshot['id'],
                    'payment_method_label' => $paymentSnapshot['label'],
                    'shipping_method' => $shippingMethod->name,
                    'shipping_method_id' => $shippingMethod->id,
                    'admin_notes' => $this->admin_notes,
                ]);

                foreach ($this->items as $item) {
                    $product = filled($item['product_id'] ?? null)
                        ? Product::query()->select(['id', 'sku'])->find($item['product_id'])
                        : null;

                    $lineItem = $order->lineItems()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'product_name' => $item['name'],
                        'sku' => $item['sku'] ?? $product?->sku,
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['price'] * $item['quantity'],
                    ]);

                    $stockItem = $this->resolveReservableStockItemForItem($item);

                    if (! $stockItem) {
                        continue;
                    }

                    app(InventoryService::class)->reserveStock(
                        $stockItem,
                        (int) $lineItem->quantity,
                        userId: auth()->id(),
                        referenceType: Order::class,
                        referenceId: (string) $order->id,
                    );
                }

                $shipping = array_merge($this->shipping_address, [
                    'type' => 'shipping',
                    'shipping_carrier_district_id' => filled($this->shipping_destination_id)
                        ? (int) $this->shipping_destination_id
                        : null,
                ]);
                $billing = $this->same_as_shipping 
                    ? array_merge($this->shipping_address, [
                        'type' => 'billing',
                        'shipping_carrier_district_id' => filled($this->billing_destination_id)
                            ? (int) $this->billing_destination_id
                            : (filled($this->shipping_destination_id) ? (int) $this->shipping_destination_id : null),
                    ])
                    : array_merge($this->billing_address, [
                        'type' => 'billing',
                        'shipping_carrier_district_id' => filled($this->billing_destination_id)
                            ? (int) $this->billing_destination_id
                            : null,
                    ]);
                
                $order->addresses()->createMany([$shipping, $billing]);

                if (($appliedCoupon['valid'] ?? false) && isset($appliedCoupon['coupon'])) {
                    app(CouponService::class)->apply(
                        $appliedCoupon['coupon'],
                        $order->id,
                        (float) $this->subtotal,
                        filled($this->customer_id) ? (int) $this->customer_id : null,
                    );
                }

                return $order;
            });

            app(OrderPostCreationAutomationService::class)->bootstrap($order->fresh([
                'paymentMethodRecord',
                'shippingMethodRecord.shippingCarrier',
                'addresses',
                'lineItems',
            ]));

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Order created manually.');
        } catch (Exception $e) {
            $this->addError('save', 'Failed to create order: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $shippingMethods = $this->enabledShippingMethodsCollection();

        $this->ensureShippingMethodSelection($shippingMethods);
        $this->ensurePaymentMethodSelection($this->availablePaymentMethods);

        $shippingMethodQuotes = $shippingMethods
            ->mapWithKeys(fn (ShippingMethod $method) => [
                $method->id => $this->buildShippingQuote($method),
            ])
            ->all();

        return view('livewire.admin.orders.order-create', [
            'products' => Product::active()->get(),
            'shippingMethods' => $shippingMethods,
            'shippingMethodQuotes' => $shippingMethodQuotes,
            'availableShippingCities' => $this->availableShippingCities($shippingMethods),
            'availableShippingDestinations' => $this->availableShippingDestinations($shippingMethods),
            'shippingIsMorocco' => $this->isMoroccoShippingAddress(),
            'billingIsMorocco' => $this->isMoroccoBillingAddress(),
            'shippingStateOptions' => $this->availableStatesForCountry($this->shipping_address['country'] ?? null),
            'shippingGlobalCityOptions' => $this->availableCitiesForCountryAndState(
                $this->shipping_address['country'] ?? null,
                $this->shipping_address['state'] ?? null,
            ),
            'billingStateOptions' => $this->availableStatesForCountry($this->billing_address['country'] ?? null),
            'billingGlobalCityOptions' => $this->availableCitiesForCountryAndState(
                $this->billing_address['country'] ?? null,
                $this->billing_address['state'] ?? null,
            ),
            'selectedCustomer' => $this->selectedCustomer,
            'canSubmit' => $this->canSubmit,
            'checkoutStatus' => $this->checkoutStatus,
            'shippingDestinationStatus' => $this->shippingDestinationStatus,
            'availablePaymentMethods' => $this->availablePaymentMethods,
            'appliedCoupon' => $this->appliedCoupon,
            'discountTotal' => $this->discountTotal,
            'couponFeedback' => $this->coupon_feedback,
            'countryOptions' => InternationalDirectory::countries(),
            'searchResults' => $this->search_customer && strlen($this->search_customer) > 2 
                ? User::customers()->where(fn($q) => $q->where('name', 'like', "%{$this->search_customer}%")
                    ->orWhere('phone', 'like', "%{$this->search_customer}%")
                    ->orWhere('email', 'like', "%{$this->search_customer}%"))
                    ->limit(5)->get() 
                : []
        ])->extends('admin.layouts.app')->section('content');
    }

    private function enabledShippingMethodsQuery(): Builder
    {
        return ShippingMethod::query()
            ->enabled()
            ->with([
                'shippingCarrier.districts' => fn ($query) => $query->where('is_active', true),
                'districtOverrides.district',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    private function enabledShippingMethodsCollection(): Collection
    {
        $countryCode = strtoupper(trim((string) ($this->shipping_address['country'] ?? '')));

        return $this->enabledShippingMethodsQuery()
            ->get()
            ->filter(fn (ShippingMethod $method) => $method->supportsCountry($countryCode))
            ->values();
    }

    private function buildShippingQuote(?ShippingMethod $method): array
    {
        if (! $method) {
            return [
                'cost' => 0.0,
                'eta' => null,
                'is_api' => false,
                'has_match' => false,
                'source' => 'none',
                'message' => null,
                'free_applied' => false,
            ];
        }

        if (! $method->isApiManaged() || ! $this->isMoroccoShippingAddress()) {
            $cost = round((float) $method->calculateCost((float) $this->subtotal), 2);
            $freeApplied = filled($method->free_shipping_threshold)
                && (float) $method->free_shipping_threshold > 0
                && (float) $this->subtotal >= (float) $method->free_shipping_threshold
                && $cost <= 0;

            return [
                'cost' => $cost,
                'eta' => $method->estimated_days,
                'is_api' => false,
                'has_match' => true,
                'source' => 'manual',
                'message' => null,
                'free_applied' => $freeApplied,
            ];
        }

        $district = $this->matchCarrierDistrict(
            $method,
            $this->shipping_address['city'] ?? null,
            $this->shipping_destination_id ?: null,
        );

        if (! $district) {
            return [
                'cost' => 0.0,
                'eta' => null,
                'is_api' => true,
                'has_match' => false,
                'source' => 'api_pending',
                'message' => filled($this->shipping_address['city'] ?? null)
                    ? 'No synced API rate matches the selected district.'
                    : 'Select a district to fetch the API delivery price.',
                'free_applied' => false,
            ];
        }

        $override = $method->districtOverrides
            ->firstWhere('shipping_carrier_district_id', $district->id);

        $forcedPrice = $override?->forced_price;

        return [
            'cost' => round((float) ($forcedPrice ?? $district->price ?? 0), 2),
            'eta' => $district->estimated_delivery ?: $method->estimated_days,
            'is_api' => true,
            'has_match' => true,
            'source' => $forcedPrice !== null ? 'forced' : 'api',
            'message' => null,
            'city' => $district->city,
            'district' => $this->districtDisplayLabel($district),
            'updated_at' => optional($district->updated_at)->format('M d, Y H:i'),
            'free_applied' => false,
        ];
    }

    private function matchCarrierDistrict(ShippingMethod $method, ?string $city, ?string $destinationId = null): ?ShippingCarrierDistrict
    {
        $carrier = $method->shippingCarrier;
        $city = trim((string) $city);

        if (! $carrier || $carrier->provider === ShippingCarrier::PROVIDER_MANUAL) {
            return null;
        }

        /** @var Collection<int, ShippingCarrierDistrict> $districts */
        $districts = $carrier->relationLoaded('districts')
            ? $carrier->districts
            : $carrier->districts()->where('is_active', true)->get();

        if (filled($destinationId)) {
            $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->id === (string) $destinationId);

            if (! $directMatch) {
                $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->external_id === (string) $destinationId);
            }

            if ($directMatch) {
                return $directMatch;
            }
        }

        if ($city === '') {
            return null;
        }

        $normalized = mb_strtolower($city);

        $match = $districts->first(function (ShippingCarrierDistrict $district) use ($normalized): bool {
            return mb_strtolower((string) $district->city) === $normalized;
        });

        if ($match) {
            return $match;
        }

        $match = $districts->first(function (ShippingCarrierDistrict $district) use ($normalized): bool {
            return mb_strtolower((string) $district->district_name) === $normalized;
        });

        if ($match) {
            return $match;
        }

        return $districts->first(function (ShippingCarrierDistrict $district) use ($normalized): bool {
            return str_contains(mb_strtolower((string) $district->district_name), $normalized)
                || str_contains(mb_strtolower((string) $district->city), $normalized);
        });
    }

    private function availableShippingCities(Collection $shippingMethods): array
    {
        if (! $this->isMoroccoShippingAddress()) {
            return collect($this->availableCitiesForCountryAndState(
                $this->shipping_address['country'] ?? null,
                $this->shipping_address['state'] ?? null,
            ))->pluck('value')->all();
        }

        $selectedMethod = $shippingMethods->firstWhere('id', (int) $this->shipping_method_id);

        $cities = match (true) {
            $selectedMethod?->isApiManaged() => $selectedMethod->shippingCarrier?->districts?->pluck('city') ?? collect(),
            default => $shippingMethods
                ->filter(fn (ShippingMethod $method) => $method->isApiManaged())
                ->flatMap(fn (ShippingMethod $method) => $method->shippingCarrier?->districts?->pluck('city') ?? collect()),
        };

        $cities = $cities
            ->map(fn ($city) => trim((string) $city))
            ->filter()
            ->unique(fn (string $city) => mb_strtolower($city))
            ->sortBy(fn (string $city) => mb_strtolower($city))
            ->values();

        $currentCity = trim((string) ($this->shipping_address['city'] ?? ''));

        if ($currentCity !== '' && ! $cities->contains(fn (string $city) => mb_strtolower($city) === mb_strtolower($currentCity))) {
            $cities->push($currentCity);
        }

        return $cities->all();
    }

    private function availableShippingDestinations(Collection $shippingMethods): array
    {
        return $this->moroccoDistricts()
            ->filter(fn ($district) => filled($district->district_name))
            ->unique(fn ($district) => (string) $district->id)
            ->sortBy([
                fn ($district) => mb_strtolower((string) $district->city),
                fn ($district) => mb_strtolower((string) $district->district_name),
            ])
            ->map(fn (ShippingCarrierDistrict $district) => [
                'id' => (string) $district->id,
                'label' => $this->districtDisplayLabel($district),
                'city' => trim((string) $district->city),
                'search' => mb_strtolower(
                    $this->districtDisplayLabel($district)
                    .' '
                    .trim((string) $district->district_name)
                    .' '
                    .trim((string) $district->city)
                ),
            ])
            ->values()
            ->all();
    }

    private function availableStatesForCountry(?string $countryCode): array
    {
        return GlobalAddressDirectory::stateOptions($countryCode);
    }

    private function availableCitiesForCountryAndState(?string $countryCode, ?string $stateName = null): array
    {
        return GlobalAddressDirectory::cityOptions($countryCode, $stateName);
    }

    private function hasResolvedShippingDestination(): bool
    {
        $countryCode = strtoupper(trim((string) ($this->shipping_address['country'] ?? '')));

        if ($countryCode === '') {
            return false;
        }

        if ($countryCode === 'MA') {
            return filled($this->shipping_destination_id)
                && filled($this->shipping_address['city'] ?? null);
        }

        if ($this->countryHasStates($countryCode) && ! filled($this->shipping_address['state'] ?? null)) {
            return false;
        }

        return filled($this->shipping_address['city'] ?? null);
    }

    private function countryHasStates(?string $countryCode): bool
    {
        return ! empty($this->availableStatesForCountry($countryCode));
    }

    private function shippingEstimateDisplay(?ShippingMethod $method, array $quote): string
    {
        if (! $this->hasValidItems) {
            return 'Pending';
        }

        if (! $method) {
            return 'Select method';
        }

        if ($quote['is_api'] && ! $quote['has_match']) {
            return 'Pending district';
        }

        if ($quote['free_applied'] ?? false) {
            return 'Free';
        }

        return $this->moneyLabel((float) ($quote['cost'] ?? 0));
    }

    private function grandTotalDisplay(?ShippingMethod $method, array $quote): string
    {
        if (! $this->hasValidItems) {
            return 'Pending';
        }

        if (! $method) {
            return 'Pending';
        }

        if ($quote['is_api'] && ! $quote['has_match']) {
            return 'Pending';
        }

        return $this->moneyLabel((float) $this->grand_total);
    }

    private function validateCouponCode(?string $code): array
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return [];
        }

        return app(CouponService::class)->validate($code, $this->buildCouponContext());
    }

    /**
     * @return array{cart_total: float, items_count: int, customer_id: int|null, product_ids: array<int, int>, category_ids: array<int, int>}
     */
    private function buildCouponContext(): array
    {
        $productIds = collect($this->items)
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->with('categories:id')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $categoryIds = $productIds
            ->flatMap(fn (int $productId) => $products->get($productId)?->categories?->pluck('id') ?? collect())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            'cart_total' => (float) $this->subtotal,
            'items_count' => (int) collect($this->items)->sum(fn (array $item) => (int) ($item['quantity'] ?? 0)),
            'customer_id' => filled($this->customer_id) ? (int) $this->customer_id : null,
            'product_ids' => $productIds->all(),
            'category_ids' => $categoryIds,
        ];
    }

    private function moneyLabel(float $amount): string
    {
        return number_format($amount, 2).' MAD';
    }

    private function hydratePhoneFields(): void
    {
        $this->applyPhoneToFields('shipping', $this->shipping_address['phone'], $this->shipping_address['country']);
        $this->applyPhoneToFields('billing', $this->billing_address['phone'], $this->billing_address['country']);
    }

    private function syncAddressFromDestination(string $type, mixed $destinationId): void
    {
        $addressProperty = $type.'_address';

        if (! filled($destinationId)) {
            $this->{$addressProperty}['city'] = '';
            $this->{$addressProperty}['state'] = '';

            return;
        }

        if (($type === 'shipping' && ! $this->isMoroccoShippingAddress())
            || ($type === 'billing' && ! $this->isMoroccoBillingAddress())) {
            return;
        }

        $district = $this->resolveMoroccoDistrictById($destinationId);

        if (! $district) {
            return;
        }

        $this->{$addressProperty}['city'] = trim((string) $district->city);
        $this->{$addressProperty}['state'] = $this->districtDisplayLabel($district);
    }

    private function districtDisplayLabel(ShippingCarrierDistrict $district): string
    {
        $city = trim((string) $district->city);
        $districtName = trim((string) $district->district_name);

        if ($districtName === '') {
            return $city;
        }

        if ($city === '') {
            return $districtName;
        }

        $normalizedCity = mb_strtolower($city);
        $normalizedDistrict = mb_strtolower($districtName);

        if (str_contains($normalizedDistrict, $normalizedCity)) {
            return $districtName;
        }

        return $city.' - '.$districtName;
    }

    private function isMoroccoShippingAddress(): bool
    {
        return strtoupper(trim((string) ($this->shipping_address['country'] ?? ''))) === 'MA';
    }

    private function isMoroccoBillingAddress(): bool
    {
        return strtoupper(trim((string) ($this->billing_address['country'] ?? ''))) === 'MA';
    }

    /**
     * @return Collection<int, ShippingCarrierDistrict>
     */
    private function moroccoDistricts(): Collection
    {
        return ShippingCarrierDistrict::query()
            ->where('is_active', true)
            ->whereHas('shippingCarrier', function ($query) {
                $query->where('provider', ShippingCarrier::PROVIDER_SENDIT)
                    ->where('is_enabled', true);
            })
            ->with('shippingCarrier')
            ->orderBy('city')
            ->orderBy('district_name')
            ->get();
    }

    private function resolveMoroccoDistrictById(mixed $destinationId): ?ShippingCarrierDistrict
    {
        if (! filled($destinationId)) {
            return null;
        }

        return $this->moroccoDistricts()
            ->first(fn (ShippingCarrierDistrict $district) => (string) $district->id === (string) $destinationId)
            ?? $this->moroccoDistricts()->first(fn (ShippingCarrierDistrict $district) => (string) $district->external_id === (string) $destinationId);
    }

    private function resolveMoroccoDistrictByText(?string $search): ?ShippingCarrierDistrict
    {
        $search = trim((string) $search);

        if ($search === '') {
            return null;
        }

        $normalized = mb_strtolower($search);

        return $this->moroccoDistricts()->first(function (ShippingCarrierDistrict $district) use ($normalized): bool {
            return mb_strtolower((string) $district->city) === $normalized
                || mb_strtolower((string) $district->district_name) === $normalized
                || mb_strtolower($this->districtDisplayLabel($district)) === $normalized
                || str_contains(mb_strtolower($this->districtDisplayLabel($district)), $normalized)
                || str_contains(mb_strtolower((string) $district->city), $normalized)
                || str_contains(mb_strtolower((string) $district->district_name), $normalized);
        });
    }

    private function handleCountryChange(string $type, mixed $value): void
    {
        $countryCode = strtoupper(trim((string) $value));
        $addressProperty = $type.'_address';
        $destinationProperty = $type.'_destination_id';

        $this->{$addressProperty}['country'] = $countryCode;

        if ($countryCode === 'MA') {
            $district = $this->resolveMoroccoDistrictById($this->{$destinationProperty});

            if (! $district) {
                $district = $this->resolveMoroccoDistrictByText($this->{$addressProperty}['state'] ?? null)
                    ?: $this->resolveMoroccoDistrictByText($this->{$addressProperty}['city'] ?? null);
            }

            if ($district) {
                $this->{$destinationProperty} = (string) $district->id;
                $this->syncAddressFromDestination($type, $this->{$destinationProperty});
            } else {
                $this->{$destinationProperty} = '';
                $this->{$addressProperty}['city'] = '';
                $this->{$addressProperty}['state'] = '';
            }

            return;
        }

        $district = $this->resolveMoroccoDistrictById($this->{$destinationProperty});
        $this->{$destinationProperty} = '';

        if ($district) {
            if (mb_strtolower(trim((string) ($this->{$addressProperty}['city'] ?? ''))) === mb_strtolower(trim((string) $district->city))) {
                $this->{$addressProperty}['city'] = '';
            }

            if (mb_strtolower(trim((string) ($this->{$addressProperty}['state'] ?? ''))) === mb_strtolower($this->districtDisplayLabel($district))) {
                $this->{$addressProperty}['state'] = '';
            }
        }

        $validStates = collect($this->availableStatesForCountry($countryCode))->pluck('value');

        if (filled($this->{$addressProperty}['state'] ?? null) && ! $validStates->contains($this->{$addressProperty}['state'])) {
            $this->{$addressProperty}['state'] = '';
            $this->{$addressProperty}['city'] = '';
        }

        $this->handleStateChange($type, $this->{$addressProperty}['state'] ?? null);
    }

    private function handleStateChange(string $type, mixed $value): void
    {
        $addressProperty = $type.'_address';
        $countryCode = strtoupper(trim((string) ($this->{$addressProperty}['country'] ?? '')));

        $this->{$addressProperty}['state'] = trim((string) $value);

        if ($countryCode === 'MA') {
            return;
        }

        $validCities = collect($this->availableCitiesForCountryAndState(
            $countryCode,
            $this->{$addressProperty}['state'] ?? null,
        ))->pluck('value');

        if (filled($this->{$addressProperty}['city'] ?? null) && ! $validCities->contains($this->{$addressProperty}['city'])) {
            $this->{$addressProperty}['city'] = '';
        }
    }

    private function syncInternationalPhones(): void
    {
        $this->syncAddressPhone('shipping');

        if (! $this->same_as_shipping) {
            $this->syncAddressPhone('billing');
        }
    }

    private function seedInlineCustomerForm(): void
    {
        $search = trim((string) $this->search_customer);
        $searchEmail = filter_var($search, FILTER_VALIDATE_EMAIL) ? Str::lower($search) : '';
        $searchPhone = preg_match('/^[\d\+\s\-\(\)]+$/', $search) ? $search : '';

        $this->new_customer = [
            'first_name' => trim((string) ($this->new_customer['first_name'] ?: ($this->shipping_address['first_name'] ?? ''))),
            'last_name' => trim((string) ($this->new_customer['last_name'] ?: ($this->shipping_address['last_name'] ?? ''))),
            'email' => trim((string) ($this->new_customer['email'] ?: $searchEmail)),
            'phone' => trim((string) ($this->new_customer['phone'] ?: ($this->shipping_address['phone'] ?? $searchPhone))),
        ];
    }

    private function normalizeInlineCustomerPhone(string $phone): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        return $this->normalizeInternationalPhone(
            strtoupper((string) ($this->shipping_address['country'] ?? 'MA')),
            $phone,
        );
    }

    private function syncAddressPhone(string $type): void
    {
        $countryProperty = $type.'_phone_country';
        $localProperty = $type.'_phone_local';
        $addressProperty = $type.'_address';

        $country = strtoupper((string) ($this->{$countryProperty} ?: data_get($this->{$addressProperty}, 'country', 'MA')));
        $local = (string) $this->{$localProperty};

        $this->{$addressProperty}['phone'] = $this->normalizeInternationalPhone($country, $local);
    }

    private function applyPhoneToFields(string $type, ?string $phone, ?string $fallbackCountry = null): void
    {
        [$country, $local] = $this->splitInternationalPhone($phone, $fallbackCountry);

        $countryProperty = $type.'_phone_country';
        $localProperty = $type.'_phone_local';

        $this->{$countryProperty} = $country;
        $this->{$localProperty} = $local;
    }

    private function normalizeInternationalPhone(string $countryCode, string $localNumber): string
    {
        $countryCode = strtoupper($countryCode ?: 'MA');
        $dialCode = InternationalDirectory::dialCodeFor($countryCode);
        $digits = preg_replace('/\D+/', '', $localNumber) ?: '';

        if ($digits === '') {
            return '';
        }

        $dialDigits = ltrim($dialCode, '+');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! str_starts_with($digits, $dialDigits) && str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if (str_starts_with($digits, $dialDigits)) {
            return '+'.$digits;
        }

        return $dialCode.$digits;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitInternationalPhone(?string $phone, ?string $fallbackCountry = null): array
    {
        $fallbackCountry = strtoupper((string) ($fallbackCountry ?: 'MA'));
        $cleanPhone = trim((string) $phone);

        if ($cleanPhone === '') {
            return [$fallbackCountry, ''];
        }

        $digits = preg_replace('/\D+/', '', $cleanPhone) ?: '';
        $callingCodes = collect(InternationalDirectory::callingCodes())
            ->mapWithKeys(fn (string $dial, string $code) => [$code => ltrim($dial, '+')])
            ->sortByDesc(fn (string $dial) => strlen($dial));

        foreach ($callingCodes as $countryCode => $dialDigits) {
            if (str_starts_with($digits, $dialDigits)) {
                return [$countryCode, substr($digits, strlen($dialDigits))];
            }
        }

        return [$fallbackCountry, ltrim($digits, '0')];
    }

    private function resolveReservableStockItemForItem(array $item): ?StockItem
    {
        if (! filled($item['product_id'] ?? null)) {
            return null;
        }

        $query = StockItem::query()->whereNull('branch_id');

        if (filled($item['variant_id'] ?? null)) {
            return $query
                ->where('product_variant_id', $item['variant_id'])
                ->first();
        }

        return $query
            ->where('product_id', $item['product_id'])
            ->whereNull('product_variant_id')
            ->first();
    }

    private function ensureShippingMethodSelection($shippingMethods): void
    {
        if ($shippingMethods->isEmpty()) {
            $this->shipping_method_id = '';

            return;
        }

        if (filled($this->shipping_method_id) && $shippingMethods->contains('id', (int) $this->shipping_method_id)) {
            return;
        }

        $defaultSlug = app(ShippingSettingsService::class)->defaultMethodCode();
        $preferred = $shippingMethods->firstWhere('slug', $defaultSlug) ?: $shippingMethods->first();

        $this->shipping_method_id = (string) $preferred?->id;
    }

    /**
     * @param  array<int, array{value:string,label:string,meta:?string}>  $paymentMethodOptions
     */
    private function ensurePaymentMethodSelection(array $paymentMethodOptions): void
    {
        $values = collect($paymentMethodOptions)
            ->pluck('value')
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            $this->payment_method = '';

            return;
        }

        if ($values->contains($this->payment_method)) {
            return;
        }

        $this->payment_method = (string) $values->first();
    }
}
