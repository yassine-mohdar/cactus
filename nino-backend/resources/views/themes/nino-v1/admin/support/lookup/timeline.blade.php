@extends('admin.layouts.app')
@php
    $billingAddress = $order->addresses->firstWhere('type', 'billing');
    $customerName = $billingAddress
        ? trim($billingAddress->first_name . ' ' . $billingAddress->last_name)
        : trim($order->customer?->full_name ?: ($order->customer?->name ?? ''));
    $customerEmail = $order->customer?->email;
    $customerPhone = $billingAddress?->phone ?? $order->customer?->phone;
@endphp
@section('title', 'Order Timeline — ' . $order->reference_number)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.support.lookup') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Lookup</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">Order {{ $order->reference_number }}</h1>
    <p class="text-sm text-slate-500 mt-1">Customer timeline and support context.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Order Details + Timeline --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Order Summary --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Order Summary</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-xs text-slate-500">Status</p><p class="mt-0.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $order->status?->label() ?? 'N/A' }}</span></p></div>
                <div><p class="text-xs text-slate-500">Total</p><p class="font-bold text-slate-900 text-lg mt-0.5">{{ number_format((float) $order->grand_total, 2) }} MAD</p></div>
                <div><p class="text-xs text-slate-500">Customer</p><p class="text-slate-900 mt-0.5">{{ $customerName !== '' ? $customerName : '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Date</p><p class="text-slate-900 mt-0.5">{{ $order->created_at->format('M d, Y H:i') }}</p></div>
            </div>
            @if($customerEmail || $customerPhone)
            <div class="grid grid-cols-2 gap-4 text-sm mt-4 pt-4 border-t border-slate-200">
                <div><p class="text-xs text-slate-500">Email</p><p class="text-slate-900 mt-0.5">{{ $customerEmail ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Phone</p><p class="text-slate-900 mt-0.5">{{ $customerPhone ?? '—' }}</p></div>
            </div>
            @endif
        </div>

        {{-- Payment Transactions --}}
        @if($transactions->count())
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Payment History</h2>
            <div class="space-y-3">
                @foreach($transactions as $txn)
                <div class="flex items-center justify-between text-sm p-3 bg-white-dim rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->type->badgeColor() }}">{{ $txn->type->label() }}</span>
                        <span class="font-mono text-xs">{{ $txn->reference }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">{{ $txn->formattedAmount() }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status->badgeColor() }}">{{ $txn->status->label() }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Activity Timeline --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Activity Timeline</h2>
            @if($activities->count())
            <div class="space-y-4">
                @foreach($activities as $event)
                <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-1 w-2 h-2 rounded-full bg-slate-900"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900">{{ $event->description }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $event->performer?->first_name ?? 'System' }} · {{ $event->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">No activity logged yet.</p>
            @endif
        </div>
    </div>

    {{-- Right: Notes --}}
    <div class="space-y-6">
        {{-- Add Note --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Add Note</h2>
            <form action="{{ route('admin.support.notes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="notable_type" value="{{ get_class($order) }}">
                <input type="hidden" name="notable_id" value="{{ $order->id }}">
                <textarea name="content" rows="3" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Add internal note..."></textarea>
                <button type="submit" class="mt-2 w-full px-4 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors">Add Note</button>
            </form>
        </div>

        {{-- Existing Notes --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Internal Notes <span class="text-xs font-normal text-slate-500">({{ $notes->count() }})</span></h2>
            @if($notes->count())
            <div class="space-y-3">
                @foreach($notes as $note)
                <div class="p-3 bg-white-dim rounded-lg text-sm {{ $note->is_pinned ? 'border-l-4 border-l-slate-800' : '' }}">
                    <p class="text-slate-900 whitespace-pre-line">{{ $note->content }}</p>
                    <div class="flex items-center justify-between mt-2 text-xs text-slate-500">
                        <span>{{ $note->author?->first_name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}</span>
                        <div class="flex gap-2">
                            <form action="{{ route('admin.support.notes.pin', $note) }}" method="POST" class="inline">@csrf<button type="submit" class="hover:text-slate-900">{{ $note->is_pinned ? '📌' : '📍' }}</button></form>
                            <form action="{{ route('admin.support.notes.destroy', $note) }}" method="POST" class="inline">@csrf @method('DELETE')<button type="submit" class="text-red-400 hover:text-red-600">✕</button></form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">No notes yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
