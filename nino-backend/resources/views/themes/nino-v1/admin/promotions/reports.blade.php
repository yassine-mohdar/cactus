@extends('admin.layouts.app')

@section('title', 'Promotions Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Promotions & Cart Reports</h1>
    <p class="text-sm text-slate-500 mt-1">Usage analytics, revenue impact, and abandoned cart recovery.</p>
</div>

{{-- Revenue Impact Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Discounts Given</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($totalDiscounts, 2) }} MAD</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Avg Discount</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($avgDiscount, 2) }} MAD</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Cart Recovery Rate</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $abandonedStats['recovery_rate'] }}%</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Lost Revenue</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($abandonedStats['lost_revenue'], 2) }} MAD</p>
    </div>
</div>

{{-- Tabs --}}
<div x-data="{ tab: 'coupons' }">
    <div class="flex border-b border-slate-200 mb-4">
        <button @click="tab='coupons'" :class="tab==='coupons' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium">Top Coupons</button>
        <button @click="tab='recent'" :class="tab==='recent' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium">Recent Usage</button>
        <button @click="tab='abandoned'" :class="tab==='abandoned' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium">Abandoned Carts</button>
    </div>

    {{-- Top Coupons --}}
    <div x-show="tab==='coupons'">
        <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
            <table class="nino-table">
                <thead class="bg-white-dim border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-slate-900">Code</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Discount</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-center">Usages</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-right">Total Saved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($topCoupons as $c)
                    <tr class="hover:bg-white-dim/50 transition-colors">
                        <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $c->code }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $c->name }}</td>
                        <td class="px-4 py-3 text-slate-900 font-semibold">{{ $c->formattedValue() }}</td>
                        <td class="px-4 py-3 text-center text-slate-900">{{ $c->usages_count }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-orange-600">{{ number_format($c->usages_sum_discount_amount ?? 0, 2) }} MAD</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No coupon usage yet.
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Usage --}}
    <div x-show="tab==='recent'" style="display:none">
        <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
            <table class="nino-table">
                <thead class="bg-white-dim border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-slate-900">Date</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Coupon</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Customer</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Order</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-right">Discount</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-right">Before → After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($recentUsages as $u)
                    <tr class="hover:bg-white-dim/50 transition-colors">
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $u->created_at->format('M d, H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-slate-900 text-xs">{{ $u->coupon?->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-900 text-xs">{{ $u->customer?->first_name ?? 'Guest' }}</td>
                        <td class="px-4 py-3 text-slate-900 text-xs">{{ $u->order?->reference_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-orange-600">-{{ number_format($u->discount_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs text-slate-500">{{ number_format($u->order_total_before, 2) }} → {{ number_format($u->order_total_after, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No coupon usage yet.
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Abandoned Carts --}}
    <div x-show="tab==='abandoned'" style="display:none">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase">Total Carts</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $abandonedStats['total'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase">Active Abandoned</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ $abandonedStats['active'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase">Recovered</p>
                <p class="text-xl font-bold text-green-600 mt-1">{{ $abandonedStats['recovered'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase">Recovered Revenue</p>
                <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($abandonedStats['recovered_revenue'], 2) }} MAD</p>
            </div>
        </div>
        <p class="text-sm text-slate-500 text-center py-4">Abandoned cart data populates as cart abandonment events are captured. Recovery notifications are sent automatically via the notification system.</p>
    </div>
</div>
@endsection
