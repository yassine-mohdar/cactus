@extends('admin.layouts.app')

@section('title', 'Coupons & Promotions')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Coupons & Promotions</h1>
            <p class="page-subtitle">Manage discount logic, redemption status, and lifecycle controls for every promotion in circulation.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">{{ number_format($coupons->total()) }} coupons</span>
            <x-admin.button href="{{ route('admin.promotions.coupons.create') }}" variant="primary">New Coupon</x-admin.button>
        </div>
    </div>
@endsection

@section('content')
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total Coupons</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Redemptions</p>
            <p class="stat-value text-[#235B8C]">{{ number_format($stats['total_usage']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Savings</p>
            <p class="stat-value text-[#B97A22]">{{ number_format((float) $stats['total_savings'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-5">
            <div class="filter-field xl:col-span-2">
                <label class="filter-label" for="coupon-search">Search</label>
                <input id="coupon-search" type="text" name="search" value="{{ request('search') }}" placeholder="Code or campaign name..." class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="coupon-status">Status</label>
                <select id="coupon-status" name="status" class="input-field">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="exhausted" {{ request('status') === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="coupon-type">Type</label>
                <select id="coupon-type" name="type" class="input-field">
                    <option value="">All</option>
                    @foreach(\App\Modules\Promotions\Enums\CouponType::cases() as $t)
                        <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                    <x-admin.button href="{{ route('admin.promotions.coupons.index') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Coupon Directory</h2>
                <p class="datatable-subtitle">Review discount type, availability, usage, and schedule before editing or retiring a campaign.</p>
            </div>
            <span class="datatable-meta">{{ number_format($coupons->total()) }} total</span>
        </div>

        @if($coupons->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No coupons match the current filters.</p>
                    <p class="text-sm text-[#617169]">Try widening the search or create a new promotion to get started.</p>
                </div>
            </div>
        @else
            <x-nino.table>
                <x-slot:head>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Discount</th>
                    <th class="text-center">Status</th>
                    <th>Usage</th>
                    <th>Dates</th>
                    <th class="text-right">Actions</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($coupons as $coupon)
                        @php $status = $coupon->computedStatus(); @endphp
                        <tr>
                            <td>
                                <span class="font-mono text-xs font-bold text-[#17302A]">{{ $coupon->code }}</span>
                            </td>
                            <td class="font-medium text-[#17302A]">{{ $coupon->name }}</td>
                            <td class="font-semibold text-[#17302A]">
                                {{ $coupon->formattedValue() }}
                                @if($coupon->max_discount)
                                    <span class="block text-xs font-normal text-[#617169]">Max {{ number_format((float) $coupon->max_discount, 2) }} MAD</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $status->badgeColor() }}">{{ $status->label() }}</span>
                            </td>
                            <td class="text-sm text-[#17302A]">
                                {{ number_format($coupon->usages_count ?? $coupon->usage_count) }}
                                @if($coupon->usage_limit)
                                    <span class="text-[#617169]"> / {{ number_format($coupon->usage_limit) }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-[#617169]">
                                @if($coupon->starts_at || $coupon->ends_at)
                                    {{ $coupon->starts_at?->format('M d') ?? '—' }} to {{ $coupon->ends_at?->format('M d') ?? '∞' }}
                                @else
                                    No date limits
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.promotions.coupons.edit', $coupon) }}" class="table-action-link">Edit</a>
                                    <form action="{{ route('admin.promotions.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Delete this coupon?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif

        <div class="datatable-footer">
            {{ $coupons->links() }}
        </div>
    </div>
@endsection
