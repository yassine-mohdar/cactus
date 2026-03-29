@extends('admin.layouts.app')

@section('title', 'Promotions Reports')

@section('header')
    <x-nino.page-header
        title="Promotions Reports"
        subtitle="Discount performance, coupon velocity, and abandoned-cart recovery across a defined reporting window.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.promotions.coupons.index') }}" variant="secondary" icon="sell">Coupons</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Discount Impact</p>
            <p class="stat-value text-[#B8802F]">{{ number_format((float) $totalDiscounts, 2) }}</p>
            <p class="mt-2 text-sm text-[#61706B]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Average Discount</p>
            <p class="stat-value">{{ number_format((float) $avgDiscount, 2) }}</p>
            <p class="mt-2 text-sm text-[#61706B]">MAD per order</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Cart Recovery Rate</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format((float) $abandonedStats['recovery_rate'], 1) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Lost Revenue</p>
            <p class="stat-value text-[#C45143]">{{ number_format((float) $abandonedStats['lost_revenue'], 2) }}</p>
            <p class="mt-2 text-sm text-[#61706B]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Recovered Revenue</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format((float) $abandonedStats['recovered_revenue'], 2) }}</p>
            <p class="mt-2 text-sm text-[#61706B]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Orders Before Discount</p>
            <p class="stat-value text-[#61706B]">{{ number_format((float) $totalOrdersBefore, 2) }}</p>
            <p class="mt-2 text-sm text-[#61706B]">MAD</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <div class="space-y-4">
            <x-nino.tab-strip label="Promotion analytics">
                @php
                    $baseFilters = [
                        'from' => optional($filters['from'])->format('Y-m-d'),
                        'to' => optional($filters['to'])->format('Y-m-d'),
                        'status' => $filters['status'],
                        'type' => $filters['type'],
                    ];
                @endphp
                <a href="{{ route('admin.promotions.reports', array_merge($baseFilters, ['tab' => 'coupons'])) }}" class="tab-pill {{ $tab === 'coupons' ? 'tab-pill-active' : '' }}">Top Coupons</a>
                <a href="{{ route('admin.promotions.reports', array_merge($baseFilters, ['tab' => 'recent'])) }}" class="tab-pill {{ $tab === 'recent' ? 'tab-pill-active' : '' }}">Recent Usage</a>
                <a href="{{ route('admin.promotions.reports', array_merge($baseFilters, ['tab' => 'abandoned'])) }}" class="tab-pill {{ $tab === 'abandoned' ? 'tab-pill-active' : '' }}">Abandoned Carts</a>
            </x-nino.tab-strip>

            <form method="GET">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="filter-grid xl:grid-cols-5">
                    <div class="filter-field">
                        <label class="filter-label" for="report-from">From</label>
                        <input id="report-from" type="date" name="from" value="{{ optional($filters['from'])->format('Y-m-d') }}" class="input-field">
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="report-to">To</label>
                        <input id="report-to" type="date" name="to" value="{{ optional($filters['to'])->format('Y-m-d') }}" class="input-field">
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="report-status">Coupon Status</label>
                        <select id="report-status" name="status" class="input-field">
                            <option value="">All statuses</option>
                            <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="expired" {{ $filters['status'] === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="exhausted" {{ $filters['status'] === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                            <option value="scheduled" {{ $filters['status'] === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="report-type">Coupon Type</label>
                        <select id="report-type" name="type" class="input-field">
                            <option value="">All types</option>
                            @foreach(\App\Modules\Promotions\Enums\CouponType::cases() as $promotionType)
                                <option value="{{ $promotionType->value }}" {{ $filters['type'] === $promotionType->value ? 'selected' : '' }}>{{ $promotionType->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field xl:items-end">
                        <div class="flex flex-wrap gap-3">
                            <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
                            <x-nino.button href="{{ route('admin.promotions.reports') }}" variant="outline">Reset</x-nino.button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($tab === 'recent')
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Recent Coupon Usage</h2>
                    <p class="datatable-subtitle">Most recent redemptions within the active reporting window.</p>
                </div>
                <span class="datatable-meta">{{ number_format($recentUsages->count()) }} entries</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Date</th>
                    <th>Coupon</th>
                    <th>Customer</th>
                    <th>Order</th>
                    <th>Discount</th>
                    <th>Before → After</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($recentUsages as $usage)
                        <tr>
                            <td>
                                <p class="table-mono text-[#61706B]">{{ $usage->created_at->format('M d, H:i') }}</p>
                            </td>
                            <td>
                                <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ $usage->coupon?->code ?? '—' }}</p>
                                <p class="mt-1 text-xs text-[#7A8681]">{{ $usage->coupon?->name ?? 'Removed coupon' }}</p>
                            </td>
                            <td class="table-muted">{{ $usage->customer?->first_name ?? 'Guest' }}</td>
                            <td class="table-muted">{{ $usage->order?->reference_number ?? '—' }}</td>
                            <td class="font-mono text-sm font-semibold text-[#B8802F]">-{{ number_format((float) $usage->discount_amount, 2) }}</td>
                            <td class="font-mono text-xs text-[#61706B]">{{ number_format((float) $usage->order_total_before, 2) }} → {{ number_format((float) $usage->order_total_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No coupon usage in this period"
                                    description="Adjust the date range or coupon filters to surface a different redemption window."
                                    icon="monitoring" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>
        </div>
    @elseif($tab === 'abandoned')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
            <x-nino.detail-section title="Recovery Snapshot" subtitle="Abandoned-cart volume and recovery outcomes across the reporting window.">
                <x-nino.entity-detail-grid class="xl:grid-cols-2">
                    <div>
                        <p class="detail-kicker">Total Carts</p>
                        <p class="detail-value-mono">{{ number_format($abandonedStats['total']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Active Abandoned</p>
                        <p class="detail-value-mono">{{ number_format($abandonedStats['active']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Recovered</p>
                        <p class="detail-value-mono">{{ number_format($abandonedStats['recovered']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Recovery Rate</p>
                        <p class="detail-value-mono">{{ number_format((float) $abandonedStats['recovery_rate'], 1) }}%</p>
                    </div>
                </x-nino.entity-detail-grid>
            </x-nino.detail-section>

            <x-nino.detail-section title="Commercial Impact" subtitle="Revenue at risk versus revenue recovered from automated cart recovery.">
                <div class="space-y-4">
                    <div class="surface-panel">
                        <p class="detail-kicker">Lost Revenue</p>
                        <p class="metric-value text-[#C45143]">{{ number_format((float) $abandonedStats['lost_revenue'], 2) }}</p>
                    </div>
                    <div class="surface-panel">
                        <p class="detail-kicker">Recovered Revenue</p>
                        <p class="metric-value text-[#1F7A4E]">{{ number_format((float) $abandonedStats['recovered_revenue'], 2) }}</p>
                    </div>
                    <p class="text-sm leading-6 text-[#61706B]">Abandoned-cart metrics are filtered only by date range. Coupon status and type filters affect coupon usage analytics, not cart abandonment volume.</p>
                </div>
            </x-nino.detail-section>
        </div>
    @else
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Top Coupons</h2>
                    <p class="datatable-subtitle">Highest-performing coupons by usage count and discount impact in the current reporting window.</p>
                </div>
                <span class="datatable-meta">{{ number_format($topCoupons->count()) }} coupons</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Discount</th>
                    <th>Status</th>
                    <th>Usages</th>
                    <th class="text-right">Total Saved</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($topCoupons as $coupon)
                        @php $couponStatus = $coupon->computedStatus(); @endphp
                        <tr>
                            <td class="font-mono text-sm font-semibold text-[#1E2B27]">{{ $coupon->code }}</td>
                            <td class="text-sm text-[#1E2B27]">{{ $coupon->name }}</td>
                            <td class="text-sm font-semibold text-[#1E2B27]">{{ $coupon->formattedValue() }}</td>
                            <td>
                                <x-nino.status-badge :tone="match ($couponStatus->value) { 'active' => 'success', 'scheduled' => 'info', 'inactive' => 'neutral', default => 'warning' }" size="sm">
                                    {{ $couponStatus->label() }}
                                </x-nino.status-badge>
                            </td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($coupon->usages_count) }}</td>
                            <td class="text-right font-mono text-sm font-semibold text-[#B8802F]">{{ number_format((float) ($coupon->usages_sum_discount_amount ?? 0), 2) }} MAD</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No coupon performance in this period"
                                    description="Try widening the date range or relaxing the coupon filters to see more promotional activity."
                                    icon="sell" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>
        </div>
    @endif
@endsection
