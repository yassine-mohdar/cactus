<?php

namespace App\Livewire\Admin\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Finance\Services\FinanceSettingsService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderCreate extends Component
{
    // Customer
    public $customer_id = '';
    public $search_customer = '';
    
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
    public $shipping_method = 'standard';
    public $admin_notes = '';

    public function mount()
    {
        $this->addItem();
    }

    public function addItem()
    {
        $this->items[] = [
            'product_id' => '',
            'variant_id' => null,
            'quantity' => 1,
            'price' => 0,
            'name' => ''
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
            } else {
                $this->items[$index]['price'] = 0;
                $this->items[$index]['name'] = '';
            }
        }
    }

    public function getSubtotalProperty()
    {
        return collect($this->items)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getGrandTotalProperty()
    {
        return $this->subtotal; // Simple implementation without tax/shipping for now
    }

    public function selectCustomer($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->customer_id = $user->id;
            $this->search_customer = $user->full_name . " ({$user->email})";
            
            // Auto-fill names if empty
            $nameParts = explode(' ', $user->name, 2);
            $this->shipping_address['first_name'] = $nameParts[0] ?? '';
            $this->shipping_address['last_name'] = $nameParts[1] ?? '';
            $this->shipping_address['phone'] = $user->phone ?? '';
        }
    }

    public function save()
    {
        $this->validate([
            'customer_id' => 'required|exists:users,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.address_line_1' => 'required|string',
            'shipping_address.city' => 'required|string',
        ]);

        try {
            return DB::transaction(function () {
                $order = Order::create([
                    'reference_number' => 'ORD-' . strtoupper(uniqid()),
                    'customer_id' => $this->customer_id,
                    'status' => OrderStatus::PENDING,
                    'currency' => app(FinanceSettingsService::class)->baseCurrency(),
                    'subtotal' => $this->subtotal,
                    'grand_total' => $this->grand_total,
                    'payment_method' => $this->payment_method,
                    'shipping_method' => $this->shipping_method,
                    'admin_notes' => $this->admin_notes,
                ]);

                foreach ($this->items as $item) {
                    $order->lineItems()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['price'] * $item['quantity'],
                    ]);
                }

                $shipping = array_merge($this->shipping_address, ['type' => 'shipping']);
                $billing = $this->same_as_shipping 
                    ? array_merge($this->shipping_address, ['type' => 'billing'])
                    : array_merge($this->billing_address, ['type' => 'billing']);
                
                $order->addresses()->createMany([$shipping, $billing]);

                return redirect()->route('admin.orders.show', $order)->with('success', 'Order created manually.');
            });
        } catch (Exception $e) {
            $this->addError('save', 'Failed to create order: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.orders.order-create', [
            'products' => Product::active()->get(),
            'searchResults' => $this->search_customer && strlen($this->search_customer) > 2 
                ? User::where(fn($q) => $q->where('name', 'like', "%{$this->search_customer}%")
                    ->orWhere('email', 'like', "%{$this->search_customer}%"))
                    ->limit(5)->get() 
                : []
        ])->extends('admin.layouts.app')->section('content');
    }
}
