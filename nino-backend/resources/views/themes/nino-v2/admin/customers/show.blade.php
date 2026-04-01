@extends('admin.layouts.app')

@php
    $statusTone = $customer->status === 'active' ? 'success' : 'danger';
@endphp

@section('title', 'Customer: ' . $customer->name)

@section('header')
    <x-nino.page-header
        title="{{ $customer->name }}"
        subtitle="Customer since {{ $customer->created_at->format('F j, Y') }}. Review lifetime value, recent orders, and internal notes.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$statusTone">{{ $customer->status }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.customers.edit', $customer) }}" variant="secondary" icon="edit">Edit Customer</x-nino.button>
            <x-nino.button href="{{ route('admin.customers.index') }}" variant="secondary" icon="arrow_back">Back to Customers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
<div class="space-y-6">
    <div class="metric-grid">
        <div class="metric-tile">
            <p class="metric-label">Total Spent</p>
            <h3 class="metric-value">{{ number_format((float) $metrics['total_spent'], 2) }}</h3>
            <p class="metric-subtitle">MAD across all completed customer orders</p>
        </div>
        <div class="metric-tile">
            <p class="metric-label">Total Orders</p>
            <h3 class="metric-value">{{ number_format((int) $metrics['total_orders']) }}</h3>
            <p class="metric-subtitle">Orders linked to this customer account</p>
        </div>
        <div class="metric-tile">
            <p class="metric-label">Average Order Value</p>
            <h3 class="metric-value">{{ number_format((float) $metrics['average_order_value'], 2) }}</h3>
            <p class="metric-subtitle">MAD average basket value</p>
        </div>
    </div>

    <div class="detail-grid">
        <div class="detail-main">
            <x-nino.detail-section title="Recent Orders" subtitle="Latest order activity and financial contribution from this customer." noPadding>
                @if($recentOrders->isNotEmpty())
                    <x-nino.table>
                        <x-slot name="head">
                            <th class="text-left">Reference</th>
                            <th class="text-left">Placed</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Total</th>
                        </x-slot>

                        <x-slot name="body">
                            @foreach($recentOrders as $order)
                                @php
                                    $orderTone = match ($order->status->value) {
                                        'delivered' => 'success',
                                        'shipped' => 'info',
                                        'failed', 'cancelled', 'refunded' => 'danger',
                                        'refund_pending' => 'warning',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-left">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="table-link table-mono">{{ $order->reference_number }}</a>
                                    </td>
                                    <td class="text-left text-sm text-[#61706B]">{{ $order->created_at->format('M j, Y') }}</td>
                                    <td class="text-left">
                                        <x-nino.status-badge :tone="$orderTone" size="sm">{{ $order->status->label() }}</x-nino.status-badge>
                                    </td>
                                    <td class="text-right font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency }}</td>
                                </tr>
                            @endforeach
                        </x-slot>
                    </x-nino.table>
                @else
                    <div class="p-5">
                        <x-nino.empty-state
                            title="No customer orders yet"
                            description="Order activity will appear here as soon as this customer completes a checkout."
                            icon="shopping_bag" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Internal Admin Notes" subtitle="Operational context that should stay internal to the team.">
                @if($notes->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($notes as $note)
                            <div class="surface-panel">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-[#1E2B27]">{{ $note->admin->name }}</span>
                                    <span class="text-[11px] text-[#7A8681]">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-[#61706B]">{{ $note->note }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No internal notes"
                        description="Use notes to capture retention risk, VIP context, or manual support history."
                        icon="sticky_note_2" />
                @endif
            </x-nino.detail-section>
        </div>

        <div class="detail-sidebar">
            <x-nino.detail-section title="Contact Information" subtitle="Primary customer contact and account status.">
                <div class="space-y-4">
                    <div>
                        <p class="detail-kicker">Email</p>
                        <a href="mailto:{{ $customer->email }}" class="detail-value inline-block hover:text-[#245848]">{{ $customer->email }}</a>
                    </div>
                    <div>
                        <p class="detail-kicker">Phone</p>
                        <p class="detail-value">{{ $customer->phone ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Account Status</p>
                        <div class="mt-2">
                            <x-nino.status-badge :tone="$statusTone">{{ $customer->status }}</x-nino.status-badge>
                        </div>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Saved Addresses" subtitle="Shipping and billing addresses linked to this profile.">
                @if($customer->addresses->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($customer->addresses as $address)
                            <div class="surface-panel">
                                <p class="text-sm font-semibold text-[#1E2B27]">{{ $address->first_name }} {{ $address->last_name }}</p>
                                <div class="mt-2 space-y-1 text-sm text-[#61706B]">
                                    <p>{{ $address->address_line_1 }}</p>
                                    <p>{{ $address->city }}, {{ $address->postal_code }}</p>
                                </div>
                                <div class="mt-3">
                                    <x-nino.status-badge tone="neutral" size="sm">{{ $address->type }}</x-nino.status-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No saved addresses"
                        description="This customer has not yet stored a billing or shipping address."
                        icon="pin_drop" />
                @endif
            </x-nino.detail-section>
        </div>
    </div>
</div>
@endsection
