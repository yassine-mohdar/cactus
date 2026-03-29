<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Orders\Models\OrderLineItem;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Support\Enums\IssuePriority;
use App\Modules\Support\Enums\IssueStatus;
use App\Modules\Support\Enums\IssueType;
use App\Modules\Support\Models\SupportIssue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $supportAgent = $this->seedStaffUser(
            name: 'Nino Support',
            email: 'support.agent@ninoworld.com',
            role: 'Customer Support Agent',
        );

        $shippingAgent = $this->seedStaffUser(
            name: 'Nino Shipping',
            email: 'shipping.agent@ninoworld.com',
            role: 'Shipping Agent',
        );

        $this->seedStaffUser(
            name: 'Nino Finance',
            email: 'finance.manager@ninoworld.com',
            role: 'Finance Manager',
        );

        $customer = User::updateOrCreate(
            ['email' => 'qa.customer@example.test'],
            [
                'name' => 'QA Customer',
                'first_name' => 'QA',
                'last_name' => 'Customer',
                'phone' => '0612345678',
                'type' => 'customer',
                'status' => 'active',
                'password' => Hash::make('password'),
                'community_auto_invite_to_default_group' => true,
            ]
        );

        $shippingMethod = ShippingMethod::updateOrCreate(
            ['slug' => 'standard-delivery'],
            [
                'name' => 'Standard Delivery',
                'carrier' => 'Amana',
                'description' => 'Default release-readiness shipping method for local QA and admin walkthroughs.',
                'base_cost' => 25.00,
                'free_shipping_threshold' => 400.00,
                'estimated_days' => '2-4 business days',
                'is_enabled' => true,
                'sort_order' => 1,
                'metadata' => [
                    'source' => 'local_demo_seed',
                ],
            ]
        );

        $order = Order::updateOrCreate(
            ['reference_number' => 'ORD-DEMO-1001'],
            [
                'customer_id' => $customer->id,
                'status' => OrderStatus::SHIPPED,
                'currency' => 'MAD',
                'subtotal' => 324.00,
                'tax_total' => 0.00,
                'shipping_total' => 25.00,
                'discount_total' => 0.00,
                'grand_total' => 349.00,
                'payment_method' => PaymentMethod::STRIPE->value,
                'shipping_method' => $shippingMethod->slug,
                'customer_notes' => 'Please deliver after 2 PM.',
                'admin_notes' => 'Local release-readiness sample order.',
            ]
        );

        OrderLineItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'sku' => 'DEMO-PLUSH-001',
            ],
            [
                'product_name' => 'Nino Plush Bear',
                'variant_name' => 'Sandy Beige',
                'unit_price' => 162.00,
                'quantity' => 2,
                'line_total' => 324.00,
            ]
        );

        OrderAddress::updateOrCreate(
            [
                'order_id' => $order->id,
                'type' => 'shipping',
            ],
            [
                'first_name' => 'QA',
                'last_name' => 'Customer',
                'phone' => '0612345678',
                'address_line_1' => '15 Rue des Orangers',
                'address_line_2' => 'Appartement 4',
                'city' => 'Casablanca',
                'state' => 'Casablanca-Settat',
                'postal_code' => '20250',
                'country' => 'MA',
            ]
        );

        OrderAddress::updateOrCreate(
            [
                'order_id' => $order->id,
                'type' => 'billing',
            ],
            [
                'first_name' => 'QA',
                'last_name' => 'Customer',
                'phone' => '0612345678',
                'address_line_1' => '15 Rue des Orangers',
                'city' => 'Casablanca',
                'state' => 'Casablanca-Settat',
                'postal_code' => '20250',
                'country' => 'MA',
            ]
        );

        Shipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $shippingMethod->id,
                'status' => ShipmentStatus::DISPATCHED,
                'carrier_name' => 'Amana',
                'carrier_service' => 'Standard',
                'tracking_number' => 'AMANA-DEMO-1001',
                'tracking_url' => 'https://www.amana.ma/fr/suivi-colis?code=AMANA-DEMO-1001',
                'package_count' => 1,
                'dispatched_at' => now()->subDay(),
                'dispatched_by' => $shippingAgent->id,
                'internal_notes' => 'Release demo shipment for local admin validation.',
            ]
        );

        PaymentTransaction::updateOrCreate(
            ['reference' => 'TXN-DEMO-1001'],
            [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'type' => TransactionType::PAYMENT,
                'status' => TransactionStatus::COMPLETED,
                'payment_method' => PaymentMethod::STRIPE,
                'gateway' => 'stripe',
                'gateway_transaction_id' => 'pi_demo_1001',
                'amount' => 349.00,
                'fee_amount' => 9.50,
                'net_amount' => 339.50,
                'currency' => 'MAD',
                'order_subtotal' => 324.00,
                'order_discount' => 0.00,
                'order_shipping' => 25.00,
                'order_tax' => 0.00,
                'order_total' => 349.00,
                'metadata' => [
                    'source' => 'local_demo_seed',
                    'gateway_mode' => 'test',
                ],
            ]
        );

        SupportIssue::updateOrCreate(
            ['reference' => 'TKT-DEMO-1001'],
            [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'customer_name' => $customer->full_name,
                'type' => IssueType::SHIPPING,
                'status' => IssueStatus::IN_PROGRESS,
                'priority' => IssuePriority::HIGH,
                'subject' => 'Customer requested updated delivery window',
                'description' => 'Sample support ticket kept in local/demo environments for release validation.',
                'assigned_to' => $supportAgent->id,
                'created_by' => $supportAgent->id,
            ]
        );
    }

    private function seedStaffUser(string $name, string $email, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'type' => 'staff',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
