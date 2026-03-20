@extends('admin.layouts.app')
@section('title', 'Financial Reports')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Financial Reports</h1>
    <p class="text-sm text-slate-500 mt-1">Revenue, payment methods, COD reconciliation, and refund analytics.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="mb-4 p-3 rounded-lg bg-yellow-50 text-yellow-700 text-sm border border-yellow-200">{{ session('warning') }}</div>@endif

{{-- Revenue Overview --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Gross Revenue</p>
        <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($revenue['gross'], 2) }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Fees</p>
        <p class="text-xl font-bold text-orange-600 mt-1">-{{ number_format($revenue['fees'], 2) }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Refunds</p>
        <p class="text-xl font-bold text-red-600 mt-1">-{{ number_format($revenue['refunds'], 2) }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Discounts</p>
        <p class="text-xl font-bold text-purple-600 mt-1">-{{ number_format($revenue['discounts'], 2) }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4 border-l-4 border-l-slate-800">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Net Revenue</p>
        <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($revenue['net'], 2) }}</p>
    </div>
</div>

{{-- Tabs --}}
<div x-data="{ tab: 'methods' }" class="space-y-4">
    <div class="flex gap-1 border-b border-slate-200">
        <button @click="tab='methods'" :class="tab==='methods' ? 'border-b-2 border-indigo-600 text-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium transition-colors">Payment Methods</button>
        <button @click="tab='gateways'" :class="tab==='gateways' ? 'border-b-2 border-indigo-600 text-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium transition-colors">Gateways</button>
        <button @click="tab='cod'" :class="tab==='cod' ? 'border-b-2 border-indigo-600 text-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium transition-colors">COD Reconciliation</button>
        <button @click="tab='refunds'" :class="tab==='refunds' ? 'border-b-2 border-indigo-600 text-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium transition-colors">Refunds</button>
        <button @click="tab='fees'" :class="tab==='fees' ? 'border-b-2 border-indigo-600 text-slate-900' : 'text-slate-500'" class="px-4 py-2 text-sm font-medium transition-colors">Discounts & Fees</button>
    </div>

    {{-- Payment Methods --}}
    <div x-show="tab==='methods'" class="datatable-shell">
        <table class="nino-table">
            <thead>
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Method</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Transactions</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-right">Total Volume</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byMethod as $m)
                <tr class="hover:bg-white-dim/50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ \App\Modules\Finance\Enums\PaymentMethod::tryFrom($m->payment_method)?->icon() }} {{ \App\Modules\Finance\Enums\PaymentMethod::tryFrom($m->payment_method)?->label() ?? $m->payment_method }}</td>
                    <td class="px-4 py-3 text-center text-slate-900">{{ number_format($m->count) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($m->total, 2) }} MAD</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Gateways --}}
    <div x-show="tab==='gateways'" class="datatable-shell">
        <table class="nino-table">
            <thead>
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Gateway</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Completed</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Failed</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Pending</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Success Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gatewayStats as $gateway => $statuses)
                @php
                    $completed = $statuses->firstWhere('status', 'completed')?->count ?? 0;
                    $failed = $statuses->firstWhere('status', 'failed')?->count ?? 0;
                    $pending = $statuses->firstWhere('status', 'pending')?->count ?? 0;
                    $total = $completed + $failed + $pending;
                    $rate = $total > 0 ? round($completed / $total * 100, 1) : 0;
                @endphp
                <tr class="hover:bg-white-dim/50">
                    <td class="px-4 py-3 font-medium text-slate-900 capitalize">{{ $gateway }}</td>
                    <td class="px-4 py-3 text-center text-green-600 font-medium">{{ $completed }}</td>
                    <td class="px-4 py-3 text-center text-red-600 font-medium">{{ $failed }}</td>
                    <td class="px-4 py-3 text-center text-yellow-600">{{ $pending }}</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rate >= 95 ? 'bg-green-100 text-green-800' : ($rate >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">{{ $rate }}%</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- COD Reconciliation --}}
    <div x-show="tab==='cod'" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending</p>
                <p class="text-xl font-bold text-yellow-600 mt-1">{{ $codStats['pending'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Collected</p>
                <p class="text-xl font-bold text-blue-600 mt-1">{{ $codStats['collected'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Deposited</p>
                <p class="text-xl font-bold text-indigo-600 mt-1">{{ $codStats['deposited'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Reconciled</p>
                <p class="text-xl font-bold text-green-600 mt-1">{{ $codStats['reconciled'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Discrepancies</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ $codStats['discrepancies'] }}</p>
            </div>
        </div>
        <p class="text-sm text-slate-500">Unreconciled amount: <span class="font-bold text-slate-900">{{ number_format($codStats['unreconciled_amount'], 2) }} MAD</span></p>

        <div class="datatable-shell">
            <table class="nino-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-semibold text-slate-900">Reference</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Order</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-right">Amount</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-center">COD Status</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Date</th>
                        <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($codTransactions as $cod)
                    <tr class="hover:bg-white-dim/50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $cod->reference }}</td>
                        <td class="px-4 py-3 text-xs">{{ $cod->order?->reference_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($cod->amount, 2) }}</td>
                        <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cod->cod_status?->badgeColor() ?? 'bg-gray-100 text-gray-600' }}">{{ $cod->cod_status?->label() ?? 'N/A' }}</span></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $cod->created_at->format('M d') }}</td>
                        <td class="px-4 py-3 text-right space-x-1">
                            @if($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::PENDING)
                                <form action="{{ route('admin.finance.reports.cod.collect', $cod) }}" method="POST" class="inline">@csrf<input type="hidden" name="collected_by" value="{{ auth()->user()->first_name ?? 'Admin' }}"><button type="submit" class="text-blue-600 text-xs font-medium">Collect</button></form>
                            @elseif($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::COLLECTED)
                                <form action="{{ route('admin.finance.reports.cod.deposit', $cod) }}" method="POST" class="inline">@csrf<button type="submit" class="text-indigo-600 text-xs font-medium">Deposit</button></form>
                            @elseif($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::DEPOSITED)
                                <form action="{{ route('admin.finance.reports.cod.reconcile', $cod) }}" method="POST" class="inline">@csrf<button type="submit" class="text-green-600 text-xs font-medium">Reconcile</button></form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Refunds --}}
    <div x-show="tab==='refunds'" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending</p>
                <p class="text-xl font-bold text-yellow-600 mt-1">{{ $refundStats['pending'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Refunded</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ number_format($refundStats['total_refunded'], 2) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Avg Refund</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($refundStats['avg_refund'], 2) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Rejection Rate</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $refundStats['rejection_rate'] }}%</p>
            </div>
        </div>
    </div>

    {{-- Discounts & Fees --}}
    <div x-show="tab==='fees'" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Discounts</p>
                <p class="text-xl font-bold text-purple-600 mt-1">{{ number_format($discountStats['total_discounts'], 2) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Fees</p>
                <p class="text-xl font-bold text-orange-600 mt-1">{{ number_format($discountStats['total_fees'], 2) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Avg Discount</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($discountStats['avg_discount'], 2) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-md p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Avg Fee</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($discountStats['avg_fee'], 2) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
