@extends('admin.layouts.app')

@php
    $billingAddress = $order->addresses->firstWhere('type', 'billing');
    $customerName = $billingAddress
        ? trim($billingAddress->first_name . ' ' . $billingAddress->last_name)
        : trim($order->customer?->full_name ?: ($order->customer?->name ?? ''));
    $customerEmail = $order->customer?->email;
    $customerPhone = $billingAddress?->phone ?? $order->customer?->phone;
    $orderTone = match ($order->status->value) {
        'delivered' => 'success',
        'shipped' => 'info',
        'failed', 'cancelled', 'refunded' => 'danger',
        default => 'warning',
    };
@endphp

@section('title', 'Order Timeline — ' . $order->reference_number)

@section('header')
    <x-nino.page-header
        title="Order Timeline"
        subtitle="Support context for {{ $order->reference_number }} across transactions, notes, and activity.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$orderTone">{{ $order->status?->label() ?? 'Unknown' }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.support.lookup') }}" variant="secondary" icon="arrow_back">Back to Lookup</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
<div class="detail-grid">
    <div class="detail-main">
        <x-nino.detail-section title="Order Summary" subtitle="Core customer and payment context for the support desk.">
            <div class="detail-meta-grid">
                <div>
                    <p class="detail-kicker">Reference</p>
                    <p class="detail-value-mono">{{ $order->reference_number }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Customer</p>
                    <p class="detail-value">{{ $customerName !== '' ? $customerName : '—' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Total</p>
                    <p class="detail-value-mono">{{ number_format((float) $order->grand_total, 2) }} MAD</p>
                </div>
                <div>
                    <p class="detail-kicker">Placed</p>
                    <p class="detail-value">{{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Email</p>
                    <p class="detail-value">{{ $customerEmail ?? '—' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Phone</p>
                    <p class="detail-value">{{ $customerPhone ?? '—' }}</p>
                </div>
            </div>
        </x-nino.detail-section>

        <x-nino.detail-section title="Customer Order Summary" subtitle="Recent order context for this customer across the support journey.">
            <div class="detail-meta-grid">
                <div>
                    <p class="detail-kicker">Total Orders</p>
                    <p class="detail-value-mono">{{ $customerOrderSummary['total_orders'] }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Last Order</p>
                    <p class="detail-value">{{ $customerOrderSummary['last_order_at']?->format('M d, Y H:i') ?? 'First order' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="detail-kicker">Recent Statuses</p>
                    @if(collect($customerOrderSummary['recent_status_counts'])->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($customerOrderSummary['recent_status_counts'] as $statusSummary)
                                <x-nino.status-badge tone="neutral">
                                    {{ $statusSummary['label'] }} · {{ $statusSummary['count'] }}
                                </x-nino.status-badge>
                            @endforeach
                        </div>
                    @else
                        <p class="detail-value text-sm">No prior customer order history yet.</p>
                    @endif
                </div>
            </div>

            @if($recentCustomerOrders->isNotEmpty())
                <div class="mt-5 space-y-3 border-t border-[#E3DDD2] pt-5">
                    @foreach($recentCustomerOrders as $recentOrder)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-[#E3DDD2] bg-[#FCFBF8] px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="detail-value-mono">{{ $recentOrder->reference_number }}</p>
                                <p class="mt-1 text-xs text-[#7A8681]">{{ $recentOrder->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-nino.status-badge tone="neutral">{{ $recentOrder->status?->label() ?? 'Unknown' }}</x-nino.status-badge>
                                <span class="font-mono text-sm font-semibold text-[#1E2B27]">
                                    {{ number_format((float) $recentOrder->grand_total, 2) }} {{ $recentOrder->currency ?? 'MAD' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Payment History" subtitle="Transactions related to this order." noPadding>
            @if($transactions->count())
                <div class="queue-list">
                    @foreach($transactions as $txn)
                        <div class="queue-row">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <x-nino.status-badge tone="neutral" size="sm">{{ $txn->type->label() }}</x-nino.status-badge>
                                    <span class="font-mono text-xs text-[#61706B]">{{ $txn->reference }}</span>
                                </div>
                                <p class="queue-meta mt-2">{{ $txn->status->label() }}</p>
                            </div>
                            <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ $txn->formattedAmount() }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-5">
                    <x-nino.empty-state
                        title="No payment history"
                        description="Transactions linked to this order will appear here for faster support triage."
                        icon="payments" />
                </div>
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Activity Timeline" subtitle="Status changes and internal system events for this order.">
            @if($activities->count())
                <div class="space-y-4">
                    @foreach($activities as $event)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2.5 w-2.5 rounded-full bg-[#245848]"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-[#1E2B27]">{{ $event->description }}</p>
                                <p class="mt-1 text-[11px] text-[#7A8681]">
                                    {{ $event->performer?->first_name ?? 'System' }} &middot; {{ $event->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-nino.empty-state
                    title="No activity recorded"
                    description="Timeline events will populate here once staff actions or system changes are logged."
                    icon="history" />
            @endif
        </x-nino.detail-section>
    </div>

    <div class="detail-sidebar">
        <x-nino.detail-section title="Add Note" subtitle="Capture internal support context for this order.">
            <form action="{{ route('admin.support.notes.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="notable_type" value="{{ get_class($order) }}">
                <input type="hidden" name="notable_id" value="{{ $order->id }}">
                <textarea name="content" rows="4" class="input-field" placeholder="Add internal note..."></textarea>
                <x-nino.button type="submit" variant="primary" class="w-full justify-center">Add Note</x-nino.button>
            </form>
        </x-nino.detail-section>

        <x-nino.detail-section title="Internal Notes" subtitle="{{ $notes->count() }} notes currently attached to this order." noPadding>
            @if($notes->count())
                <div class="queue-list">
                    @foreach($notes as $note)
                        <div class="px-5 py-4">
                            <div class="{{ $note->is_pinned ? 'surface-panel-active' : 'surface-panel' }}">
                                <p class="whitespace-pre-line text-sm leading-6 text-[#1E2B27]">{{ $note->content }}</p>
                                <div class="mt-3 flex items-center justify-between gap-3 text-[11px] text-[#7A8681]">
                                    <span>{{ $note->author?->first_name ?? 'Unknown' }} &middot; {{ $note->created_at->diffForHumans() }}</span>
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.support.notes.pin', $note) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex rounded-md p-1 text-[#61706B] transition-colors hover:bg-[#FCFBF8] hover:text-[#1E2B27]" aria-label="Pin note">
                                                <span class="material-symbols-outlined text-base">{{ $note->is_pinned ? 'keep' : 'push_pin' }}</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.support.notes.destroy', $note) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex rounded-md p-1 text-[#C45143] transition-colors hover:bg-[#FCFBF8] hover:text-[#A73D30]" aria-label="Delete note">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-5">
                    <x-nino.empty-state
                        title="No notes yet"
                        description="Internal notes will help future support agents pick up this case without losing context."
                        icon="sticky_note_2" />
                </div>
            @endif
        </x-nino.detail-section>
    </div>
</div>
@endsection
