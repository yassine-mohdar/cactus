@extends('admin.layouts.app')

@section('title', 'Branch: ' . $branch->name)

@php
    $statusTone = $branch->status === 'active' ? 'success' : 'neutral';
@endphp

@section('header')
    <x-nino.page-header
        title="{{ $branch->name }}"
        subtitle="Monitor branch readiness, current stock exposure, and the contacts responsible for day-to-day operations.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$statusTone">{{ ucfirst($branch->status) }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.inventory.branches.edit', $branch) }}" variant="secondary" icon="edit">Edit Branch</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.branches.index') }}" variant="secondary" icon="arrow_back">Back to Branches</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="metric-grid">
            <div class="metric-tile">
                <p class="metric-label">Tracked SKUs</p>
                <h3 class="metric-value">{{ number_format($stockSummary['total_skus']) }}</h3>
                <p class="metric-subtitle">Stock items currently assigned to this branch</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Available Units</p>
                <h3 class="metric-value">{{ number_format($stockSummary['available_units']) }}</h3>
                <p class="metric-subtitle">Sellable quantity after reserved stock is deducted</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Low Stock</p>
                <h3 class="metric-value">{{ number_format($stockSummary['low_stock_items']) }}</h3>
                <p class="metric-subtitle">Items at or below the operational threshold</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Recent Movements</p>
                <h3 class="metric-value">{{ number_format($stockSummary['recent_movements']) }}</h3>
                <p class="metric-subtitle">Inventory movements recorded in the last 14 days</p>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-main">
                <x-nino.detail-section title="Branch Inventory" subtitle="Products with the lowest available stock are surfaced first so operators can act quickly." noPadding>
                    @if($inventoryPreview->isNotEmpty())
                        <x-nino.table>
                            <x-slot name="head">
                                <th class="text-left">Product</th>
                                <th class="text-left">SKU</th>
                                <th class="text-right">Available</th>
                                <th class="text-right">Reserved</th>
                            </x-slot>
                            <x-slot name="body">
                                @foreach($inventoryPreview as $item)
                                    <tr>
                                        <td class="text-left">
                                            <div class="font-semibold text-[#1E2B27]">{{ $item->product?->name ?? 'Deleted product' }}</div>
                                            <div class="text-xs text-[#61706B]">{{ $item->variant?->name ?? 'Base item' }}</div>
                                        </td>
                                        <td class="text-left table-mono">{{ $item->sku ?? 'NO-SKU' }}</td>
                                        <td class="text-right font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item->available_quantity) }}</td>
                                        <td class="text-right font-mono text-sm text-[#61706B]">{{ number_format($item->reserved_quantity) }}</td>
                                    </tr>
                                @endforeach
                            </x-slot>
                        </x-nino.table>
                    @else
                        <div class="p-5">
                            <x-nino.empty-state
                                title="No inventory assigned"
                                description="This branch has not received stock yet. Inventory will surface here as soon as purchase orders or stock transfers create branch-level records."
                                icon="inventory_2" />
                        </div>
                    @endif
                </x-nino.detail-section>

                <x-nino.detail-section title="Linked Team Contacts" subtitle="Users associated with this organization can act as local operators for stock and support escalation.">
                    @if($branch->users->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($branch->users as $user)
                                <div class="surface-panel">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-[#1E2B27]">{{ $user->name }}</p>
                                            <p class="mt-1 text-xs text-[#61706B]">{{ $user->email }}</p>
                                        </div>
                                        <x-nino.status-badge :tone="$user->status === 'active' ? 'success' : 'neutral'" size="sm">{{ ucfirst($user->status ?? 'active') }}</x-nino.status-badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-nino.empty-state
                            title="No team contacts linked"
                            description="Assign local users to this branch if you want operator scope and organization-based access to line up."
                            icon="group" />
                    @endif
                </x-nino.detail-section>
            </div>

            <div class="detail-sidebar">
                <x-nino.detail-section title="Branch Details" subtitle="Contact channels and location metadata used by operations.">
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Email</p>
                            <p class="detail-value">{{ $branch->email ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Phone</p>
                            <p class="detail-value">{{ $branch->phone ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Reserved Units</p>
                            <p class="detail-value-mono">{{ number_format($stockSummary['reserved_units']) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Linked Users</p>
                            <p class="detail-value-mono">{{ number_format($branch->users_count ?? 0) }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                </x-nino.detail-section>

                <x-nino.detail-section title="Location" subtitle="Physical address used for routing, warehousing, and operational escalation.">
                    <div class="space-y-4">
                        <div>
                            <p class="detail-kicker">Address</p>
                            <p class="detail-value">{{ $branch->address ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">City</p>
                            <p class="detail-value">{{ $branch->city ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Country</p>
                            <p class="detail-value">{{ $branch->country ?: 'Not provided' }}</p>
                        </div>
                    </div>
                </x-nino.detail-section>
            </div>
        </div>
    </div>
@endsection
