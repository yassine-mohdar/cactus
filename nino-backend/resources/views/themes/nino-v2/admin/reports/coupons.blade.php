@extends('admin.layouts.app')

@section('title', 'Coupon Usage Report')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Coupon Usage Report</h1>
            <p class="page-subtitle">Measure coupon adoption, redemption volume, and the total discount being given back to shoppers.</p>
        </div>

        <span class="datatable-meta">{{ $from }} to {{ $to }}</span>
    </div>
@endsection

@section('content')
    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-4">
            <div class="filter-field">
                <label class="filter-label" for="coupons-from">From</label>
                <input id="coupons-from" type="date" name="from" value="{{ $from }}" class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="coupons-to">To</label>
                <input id="coupons-to" type="date" name="to" value="{{ $to }}" class="input-field">
            </div>
            <div class="filter-field xl:col-span-2 xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Window</x-admin.button>
                    <x-admin.button href="{{ route('admin.reports.coupons') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total Coupons</p>
            <p class="stat-value">{{ number_format($summary['total_coupons']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active Coupons</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($summary['active_coupons']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Redemptions</p>
            <p class="stat-value text-[#235B8C]">{{ number_format($summary['total_redemptions']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Discount Given</p>
            <p class="stat-value text-[#7D3BB0]">{{ number_format((float) $summary['total_discount_given'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Top Coupon Performance</h2>
                <p class="datatable-subtitle">Coupons ordered by redemption count within the selected period.</p>
            </div>
            <span class="datatable-meta">{{ $topCoupons->count() }} coupons</span>
        </div>

        @if($topCoupons->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No coupons found.</p>
                    <p class="text-sm text-[#617169]">Coupon metrics will appear once offers are created or redeemed.</p>
                </div>
            </div>
        @else
            <x-nino.table>
                <x-slot:head>
                    <th>Code</th>
                    <th class="text-center">Type</th>
                    <th class="text-right">Value</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Redemptions</th>
                    <th class="text-right">Limit</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($topCoupons as $coupon)
                        <tr>
                            <td class="font-mono text-xs font-bold text-[#17302A]">{{ $coupon->code }}</td>
                            <td class="text-center text-xs uppercase tracking-[0.18em] text-[#6E7D75]">{{ $coupon->type ?? '—' }}</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ $coupon->value ? number_format((float) $coupon->value, 2) : '—' }}</td>
                            <td class="text-center">
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $coupon->is_active ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/85 text-[#617169]' }}">
                                    {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format($coupon->usages_count ?? 0) }}</td>
                            <td class="text-right text-[#17302A]">{{ $coupon->usage_limit ?? '∞' }}</td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif
    </div>
@endsection
