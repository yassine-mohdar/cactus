@extends('admin.layouts.app')

@php
    $statusTone = match ($transaction->status->value) {
        'completed' => 'success',
        'failed', 'cancelled' => 'danger',
        'pending' => 'warning',
        default => 'neutral',
    };

    $typeTone = str_contains($transaction->type->value, 'refund') ? 'danger' : 'info';
@endphp

@section('title', 'Transaction ' . $transaction->reference)

@section('header')
    <x-nino.page-header
        title="Transaction {{ $transaction->reference }}"
        subtitle="Payment-level detail for finance review, reconciliation, and exception handling.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$statusTone">{{ $transaction->status->label() }}</x-nino.status-badge>
            <x-nino.status-badge :tone="$typeTone">{{ $transaction->type->label() }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.finance.transactions.index') }}" variant="secondary" icon="arrow_back">Back to Transactions</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
<div class="detail-grid">
    <div class="detail-main">
        <x-nino.detail-section title="Payment Info" subtitle="Gateway, method, fees, and captured amount.">
            <div class="detail-meta-grid">
                <div>
                    <p class="detail-kicker">Method</p>
                    <p class="detail-value">{{ $transaction->payment_method->icon() }} {{ $transaction->payment_method->label() }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Gateway</p>
                    <p class="detail-value">{{ $transaction->gateway ?: 'Not captured' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Gateway Ref</p>
                    <p class="detail-value-mono">{{ $transaction->gateway_transaction_id ?: '—' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Amount</p>
                    <p class="detail-value-mono">{{ $transaction->formattedAmount() }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Fee</p>
                    <p class="detail-value-mono">{{ number_format((float) $transaction->fee_amount, 2) }} {{ $transaction->currency }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Net</p>
                    <p class="detail-value-mono">{{ number_format((float) $transaction->net_amount, 2) }} {{ $transaction->currency }}</p>
                </div>
            </div>

            @if($transaction->failure_reason)
                <p class="detail-note mt-5"><strong>Failure reason:</strong> {{ $transaction->failure_reason }}</p>
            @endif
        </x-nino.detail-section>

        @if($transaction->order_total)
            <x-nino.detail-section title="Order Snapshot" subtitle="Order totals captured at the time of payment processing.">
                <div class="detail-meta-grid">
                    <div>
                        <p class="detail-kicker">Subtotal</p>
                        <p class="detail-value-mono">{{ number_format((float) $transaction->order_subtotal, 2) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Discount</p>
                        <p class="detail-value-mono text-[#C45143]">-{{ number_format((float) $transaction->order_discount, 2) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Shipping</p>
                        <p class="detail-value-mono">{{ number_format((float) $transaction->order_shipping, 2) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Tax</p>
                        <p class="detail-value-mono">{{ number_format((float) $transaction->order_tax, 2) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Order Total</p>
                        <p class="detail-value-mono">{{ number_format((float) $transaction->order_total, 2) }}</p>
                    </div>
                </div>
            </x-nino.detail-section>
        @endif

        @if($transaction->isCod())
            <x-nino.detail-section title="COD Details" subtitle="Collection and reconciliation metadata for cash-on-delivery flows.">
                <div class="detail-meta-grid">
                    <div>
                        <p class="detail-kicker">COD Status</p>
                        <p class="detail-value">{{ $transaction->cod_status?->label() ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Collected By</p>
                        <p class="detail-value">{{ $transaction->cod_collected_by ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Collected Amount</p>
                        <p class="detail-value-mono">{{ $transaction->cod_collected_amount ? number_format((float) $transaction->cod_collected_amount, 2) : '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Collected At</p>
                        <p class="detail-value">{{ $transaction->cod_collected_at?->format('M j, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Deposited At</p>
                        <p class="detail-value">{{ $transaction->cod_deposited_at?->format('M j, Y H:i') ?? '—' }}</p>
                    </div>
                    @if($transaction->hasCodDiscrepancy())
                        <div>
                            <p class="detail-kicker">Discrepancy</p>
                            <p class="detail-value-mono text-[#C45143]">{{ $transaction->codDiscrepancyAmount() > 0 ? '+' : '' }}{{ number_format((float) $transaction->codDiscrepancyAmount(), 2) }}</p>
                        </div>
                    @endif
                </div>

                @if($transaction->cod_notes)
                    <p class="detail-note mt-5">{{ $transaction->cod_notes }}</p>
                @endif
            </x-nino.detail-section>
        @endif
    </div>

    <div class="detail-sidebar">
        <x-nino.detail-section title="Linked Order" subtitle="Commerce record associated with this transaction.">
            @if($transaction->order)
                <div class="space-y-2">
                    <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ $transaction->order->reference_number }}</p>
                    <p class="text-sm text-[#61706B]">Customer: {{ $transaction->order->customer?->full_name ?: ($transaction->order->customer?->name ?? 'Guest') }}</p>
                    <x-nino.button href="{{ route('admin.orders.show', $transaction->order) }}" size="sm" variant="secondary">View Order</x-nino.button>
                </div>
            @else
                <x-nino.empty-state
                    title="No linked order"
                    description="This transaction does not currently point to an order record."
                    icon="link_off" />
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Timeline" subtitle="Creation and processing checkpoints for finance audit trails.">
            <div class="space-y-4">
                <div>
                    <p class="detail-kicker">Created</p>
                    <p class="detail-value">{{ $transaction->created_at->format('M j, Y H:i') }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Updated</p>
                    <p class="detail-value">{{ $transaction->updated_at->format('M j, Y H:i') }}</p>
                </div>
                @if($transaction->processor)
                    <div>
                        <p class="detail-kicker">Processed By</p>
                        <p class="detail-value">{{ $transaction->processor->first_name }}</p>
                    </div>
                @endif
            </div>
        </x-nino.detail-section>

        <x-nino.detail-section title="Refund Requests" subtitle="Requests already linked to this transaction.">
            @if($transaction->refundRequests->isNotEmpty())
                <div class="space-y-3">
                    @foreach($transaction->refundRequests as $refund)
                        <div class="surface-panel-compact flex items-center justify-between gap-3">
                            <span class="font-mono text-xs text-[#61706B]">{{ $refund->reference }}</span>
                            <span class="text-sm font-medium text-[#1E2B27]">{{ $refund->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-nino.empty-state
                    title="No refund requests"
                    description="Refund requests tied to this transaction will appear here."
                    icon="currency_exchange" />
            @endif
        </x-nino.detail-section>
    </div>
</div>
@endsection
