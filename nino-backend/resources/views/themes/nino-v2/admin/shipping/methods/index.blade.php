@extends('admin.layouts.app')

@section('title', 'Shipping Methods')

@section('header')
    <x-nino.page-header
        title="Shipping Methods"
        subtitle="Configure customer-facing delivery options, carrier mapping, and pricing rules.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.shipping.methods.create') }}" variant="primary" icon="add">Add Method</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if(session('success'))
    <x-nino.inline-alert tone="success" title="Shipping method updated" class="mb-6">
        {{ session('success') }}
    </x-nino.inline-alert>
@endif
@if(session('error'))
    <x-nino.inline-alert tone="danger" title="Shipping method action failed" class="mb-6">
        {{ session('error') }}
    </x-nino.inline-alert>
@endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Methods</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Enabled</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['enabled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Free Shipping Rules</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['free_shipping']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Shipments In Flight</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['active_shipments']) }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)]">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Delivery Methods</h2>
                    <p class="datatable-subtitle">Pricing, carrier assignment, and operational availability for each shipping option.</p>
                </div>
                <span class="datatable-meta">{{ number_format($methods->count()) }} methods</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Name</th>
                    <th>Carrier</th>
                    <th>Base Cost</th>
                    <th>Free Above</th>
                    <th>Est. Days</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($methods as $method)
                        <tr>
                            <td>
                                <p class="font-semibold text-[#1E2B27]">{{ $method->name }}</p>
                                <p class="mt-1 text-xs font-mono text-[#7A8681]">{{ $method->slug }}</p>
                            </td>
                            <td>
                                <p class="table-muted">{{ $method->carrierLabel() }}</p>
                                <p class="mt-1 text-xs font-mono text-[#7A8681]">{{ strtoupper($method->shippingCarrier?->provider ?? 'manual') }}</p>
                            </td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ number_format((float) $method->base_cost, 2) }} MAD</td>
                            <td class="table-muted">{{ $method->free_shipping_threshold ? number_format((float) $method->free_shipping_threshold, 2).' MAD' : 'No threshold' }}</td>
                            <td class="table-muted">{{ $method->estimated_days ?: 'Not set' }}</td>
                            <td>
                                <x-nino.status-badge :tone="$method->is_enabled ? 'success' : 'neutral'" size="sm">
                                    {{ $method->is_enabled ? 'Enabled' : 'Disabled' }}
                                </x-nino.status-badge>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.shipping.methods.edit', $method) }}" class="table-action-link">Edit</a>
                                    <form action="{{ route('admin.shipping.methods.destroy', $method) }}" method="POST" class="inline" onsubmit="return confirm('Delete this shipping method?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No shipping methods configured"
                                    description="Create the first customer-facing delivery option to start routing fulfillment."
                                    icon="local_shipping" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>
        </div>

        <x-nino.detail-section title="Carrier Coverage" subtitle="How the current delivery methods are distributed across carrier partners.">
            <div class="space-y-3">
                @forelse($carrierBreakdown as $carrier)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-[#1E2B27]">{{ $carrier['label'] }}</span>
                        <span class="font-mono text-sm text-[#61706B]">{{ number_format($carrier['count']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[#61706B]">Carrier breakdown will appear once at least one delivery method is configured.</p>
                @endforelse
            </div>
        </x-nino.detail-section>
    </div>
@endsection
