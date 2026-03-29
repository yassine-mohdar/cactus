@extends('admin.layouts.app')

@section('title', 'Coupons & Promotions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Coupons & Promotions</h1>
        <p class="text-sm text-slate-500 mt-1">Manage discount codes, targeting, and usage limits.</p>
    </div>
    <a href="{{ route('admin.promotions.coupons.create') }}" class="btn-primary">+ New Coupon</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Coupons</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Redemptions</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['total_usage'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Savings</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($stats['total_savings'], 2) }} MAD</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Code or name..." class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-40">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
        <select name="status" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-32">
            <option value="">All</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="exhausted" {{ request('status') === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Type</label>
        <select name="type" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-32">
            <option value="">All</option>
            @foreach(\App\Modules\Promotions\Enums\CouponType::cases() as $t)
                <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-primary">Filter</button>
    @if(request()->hasAny(['search', 'status', 'type']))
        <a href="{{ route('admin.promotions.coupons.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900">Clear</a>
    @endif
</form>

{{-- Coupons Table --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Code</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Discount</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Usage</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Dates</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($coupons as $coupon)
            @php $status = $coupon->computedStatus(); @endphp
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3">
                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $coupon->code }}</span>
                </td>
                <td class="px-4 py-3 text-slate-900">{{ $coupon->name }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">
                    {{ $coupon->formattedValue() }}
                    @if($coupon->max_discount)
                        <span class="text-xs text-slate-500">(max {{ number_format($coupon->max_discount, 2) }})</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status->badgeColor() }}">{{ $status->label() }}</span>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">
                    {{ $coupon->usages_count ?? $coupon->usage_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    @if($coupon->starts_at || $coupon->ends_at)
                        {{ $coupon->starts_at?->format('M d') ?? '—' }} → {{ $coupon->ends_at?->format('M d') ?? '∞' }}
                    @else
                        No limits
                    @endif
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('admin.promotions.coupons.edit', $coupon) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                    <form action="{{ route('admin.promotions.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Delete this coupon?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No coupons yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $coupons->links() }}</div>
@endsection
