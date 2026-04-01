@extends('admin.layouts.app')
@section('title', 'Transaction ' . $transaction->reference)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.finance.transactions.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Transactions</a>
    <div class="flex items-center gap-3 mt-2">
        <h1 class="text-2xl font-bold text-slate-900 font-mono">{{ $transaction->reference }}</h1>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->status->badgeColor() }}">{{ $transaction->status->label() }}</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type->badgeColor() }}">{{ $transaction->type->label() }}</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Details --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Payment Info --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Payment Info</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><p class="text-xs text-slate-500">Method</p><p class="font-medium text-slate-900 mt-0.5">{{ $transaction->resolvedPaymentMethodIcon() }} {{ $transaction->resolvedPaymentMethodLabel() }}</p></div>
                <div><p class="text-xs text-slate-500">Gateway</p><p class="font-medium text-slate-900 mt-0.5">{{ $transaction->gateway ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Gateway Ref</p><p class="font-mono text-xs text-slate-900 mt-0.5">{{ $transaction->gateway_transaction_id ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Amount</p><p class="font-bold text-slate-900 text-lg mt-0.5">{{ $transaction->formattedAmount() }}</p></div>
                <div><p class="text-xs text-slate-500">Fee</p><p class="font-medium text-orange-600 mt-0.5">{{ number_format($transaction->fee_amount, 2) }} {{ $transaction->currency }}</p></div>
                <div><p class="text-xs text-slate-500">Net</p><p class="font-medium text-green-600 mt-0.5">{{ number_format($transaction->net_amount, 2) }} {{ $transaction->currency }}</p></div>
            </div>
            @if($transaction->failure_reason)
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"><strong>Failure:</strong> {{ $transaction->failure_reason }}</div>
            @endif
        </div>

        {{-- Order Financial Snapshot --}}
        @if($transaction->order_total)
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Order Snapshot</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div><p class="text-xs text-slate-500">Subtotal</p><p class="font-medium text-slate-900 mt-0.5">{{ number_format($transaction->order_subtotal, 2) }}</p></div>
                <div><p class="text-xs text-slate-500">Discount</p><p class="font-medium text-red-600 mt-0.5">-{{ number_format($transaction->order_discount, 2) }}</p></div>
                <div><p class="text-xs text-slate-500">Shipping</p><p class="font-medium text-slate-900 mt-0.5">{{ number_format($transaction->order_shipping, 2) }}</p></div>
                <div><p class="text-xs text-slate-500">Tax</p><p class="font-medium text-slate-900 mt-0.5">{{ number_format($transaction->order_tax, 2) }}</p></div>
                <div><p class="text-xs text-slate-500">Total</p><p class="font-bold text-slate-900 mt-0.5">{{ number_format($transaction->order_total, 2) }}</p></div>
            </div>
        </div>
        @endif

        {{-- COD Details --}}
        @if($transaction->isCod())
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">COD Details</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><p class="text-xs text-slate-500">COD Status</p><p class="mt-0.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->cod_status?->badgeColor() ?? 'bg-gray-100 text-gray-600' }}">{{ $transaction->cod_status?->label() ?? '—' }}</span></p></div>
                <div><p class="text-xs text-slate-500">Collected By</p><p class="font-medium text-slate-900 mt-0.5">{{ $transaction->cod_collected_by ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Collected Amount</p><p class="font-medium text-slate-900 mt-0.5">{{ $transaction->cod_collected_amount ? number_format($transaction->cod_collected_amount, 2) : '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Collected At</p><p class="text-slate-900 mt-0.5">{{ $transaction->cod_collected_at?->format('M d, H:i') ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Deposited At</p><p class="text-slate-900 mt-0.5">{{ $transaction->cod_deposited_at?->format('M d, H:i') ?? '—' }}</p></div>
                @if($transaction->hasCodDiscrepancy())
                <div><p class="text-xs text-slate-500">Discrepancy</p><p class="font-bold text-red-600 mt-0.5">{{ $transaction->codDiscrepancyAmount() > 0 ? '+' : '' }}{{ number_format($transaction->codDiscrepancyAmount(), 2) }}</p></div>
                @endif
            </div>
            @if($transaction->cod_notes)<p class="mt-3 text-sm text-slate-500 italic">{{ $transaction->cod_notes }}</p>@endif
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Linked Order --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Linked Order</h2>
            @if($transaction->order)
            <p class="font-medium text-slate-900">{{ $transaction->order->reference_number }}</p>
            <p class="text-xs text-slate-500 mt-1">Customer: {{ $transaction->customer?->first_name ?? 'Guest' }}</p>
            @else
            <p class="text-sm text-slate-500">No linked order.</p>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Timeline</h2>
            <div class="space-y-3 text-sm">
                <div><p class="text-xs text-slate-500">Created</p><p class="text-slate-900">{{ $transaction->created_at->format('M d, Y H:i') }}</p></div>
                <div><p class="text-xs text-slate-500">Updated</p><p class="text-slate-900">{{ $transaction->updated_at->format('M d, Y H:i') }}</p></div>
                @if($transaction->processor)<div><p class="text-xs text-slate-500">Processed By</p><p class="text-slate-900">{{ $transaction->processor->first_name }}</p></div>@endif
            </div>
        </div>

        {{-- Refund Requests --}}
        @if($transaction->refundRequests->count())
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Refund Requests</h2>
            <div class="space-y-2">
                @foreach($transaction->refundRequests as $refund)
                <div class="flex items-center justify-between text-sm">
                    <span class="font-mono text-xs">{{ $refund->reference }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $refund->status->badgeColor() }}">{{ $refund->status->label() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
