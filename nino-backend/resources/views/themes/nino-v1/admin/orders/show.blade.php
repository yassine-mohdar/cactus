@extends('admin.layouts.app')

@section('header')
<div class="flex items-center justify-between">
    <div class="flex flex-col">
        <h1 class="text-2xl font-bold font-headline text-slate-900">Order {{ $order->reference_number }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $order->created_at->format('F j, Y - g:i A') }}</p>
    </div>
    <span class="px-3 py-1.5 bg-slate-100 text-slate-900 rounded-lg border border-slate-200 text-sm font-bold uppercase tracking-wider shadow-sm">
        {{ $order->status->label() }}
    </span>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Left Column: Items & Totals -->
    <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden text-slate-900">
            <div class="px-6 py-4 border-b border-slate-200 bg-white-container-low flex items-center justify-between">
                <h3 class="font-bold text-lg font-headline">Order Items</h3>
                <span class="text-sm text-slate-500">{{ $order->lineItems->sum('quantity') }} items</span>
            </div>
            <div class="p-0 sm:p-6 opacity-100 transition-opacity">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="pb-3 px-4 sm:px-0 text-left">Product</th>
                                <th class="pb-3 px-4 sm:px-0 text-right">Price</th>
                                <th class="pb-3 px-4 sm:px-0 text-right">Qty</th>
                                <th class="pb-3 px-4 sm:px-0 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/50">
                            @foreach ($order->lineItems as $item)
                                <tr class="hover:bg-white transition-colors">
                                    <td class="py-4 px-4 sm:px-0">
                                        <div class="font-bold text-slate-900 text-base">{{ $item->product_name }}</div>
                                        @if($item->variant_name)
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $item->variant_name }}</div>
                                        @endif
                                        <div class="text-[10px] text-outline mt-1 font-mono uppercase tracking-wider">SKU: {{ $item->sku }}</div>
                                    </td>
                                    <td class="py-4 px-4 sm:px-0 text-right text-slate-500">{{ $item->unit_price }}</td>
                                    <td class="py-4 px-4 sm:px-0 text-right font-medium">x{{ $item->quantity }}</td>
                                    <td class="py-4 px-4 sm:px-0 text-right font-bold text-base text-slate-900">{{ $item->line_total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5 space-y-3 text-sm px-4 sm:px-0">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal</span>
                        <span class="font-medium text-slate-900">{{ $order->subtotal }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Shipping</span>
                        <span class="font-medium text-slate-900">{{ $order->shipping_total }}</span>
                    </div>
                    @if($order->discount_total > 0)
                    <div class="flex justify-between text-slate-500">
                        <span>Discount</span>
                        <span class="text-red-600 font-medium">-{{ $order->discount_total }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between pt-4 mt-2 border-t border-slate-200/50 font-bold text-lg text-slate-900">
                        <span>Grand Total</span>
                        <span>{{ $order->grand_total }} <span class="text-sm font-normal text-slate-500">{{ $order->currency }}</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Customer & Shipping -->
    <div class="flex flex-col gap-6">
        
        <!-- Customer Details -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-white-container-low">
                <h3 class="font-bold text-lg font-headline text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Customer
                </h3>
            </div>
            <div class="p-6 text-sm flex flex-col gap-1">
                @if($order->customer)
                    <div class="font-bold text-base text-slate-900">{{ $order->customer->full_name }}</div>
                    <a href="mailto:{{ $order->customer->email }}" class="text-slate-900 hover:underline">{{ $order->customer->email }}</a>
                    <div class="mt-3 text-xs text-slate-500 pt-3 border-t border-slate-200/30">
                        <a href="{{ route('admin.customers.show', $order->customer) }}" class="text-slate-900 font-medium hover:underline inline-flex items-center gap-1">
                            View full profile 
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                @else
                    <div class="inline-flex items-center justify-center py-2 px-3 rounded-md bg-white-variant text-slate-500 font-medium text-xs border border-slate-200">
                        Guest Checkout Account
                    </div>
                @endif
            </div>
        </div>

        <!-- Shipping Destination -->
        @if($shippingAddress)
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-white-container-low">
                <h3 class="font-bold text-lg font-headline text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Shipping Destination
                </h3>
            </div>
            <div class="p-6 text-sm text-slate-500 space-y-1">
                <div class="font-bold text-slate-900 text-base mb-2">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</div>
                <div>{{ $shippingAddress->address_line_1 }}</div>
                @if($shippingAddress->address_line_2)<div>{{ $shippingAddress->address_line_2 }}</div>@endif
                <div>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</div>
                <div class="font-medium text-slate-900">{{ $shippingAddress->country }}</div>
                
                @if($shippingAddress->phone)
                <div class="pt-4 mt-2 border-t border-slate-200/30 text-slate-900">
                    <a href="tel:{{ $shippingAddress->phone }}" class="hover:text-slate-900 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        {{ $shippingAddress->phone }}
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

        @can('orders.override_status')
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-white-container-low">
                <h3 class="font-bold text-lg font-headline text-slate-900">Manual Status Override</h3>
            </div>
            <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="p-6 space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1" for="order-status-v1">Status</label>
                    <select id="order-status-v1" name="status" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected(old('status', $order->status->value) === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1" for="order-status-notes-v1">Reason / note</label>
                    <textarea id="order-status-notes-v1" name="notes" rows="3" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    Update Order Status
                </button>
            </form>
        </div>
        @endcan
        
        <!-- Customer Notes -->
        @if($order->customer_notes)
        <div class="bg-white rounded-lg border-l-4 border-l-slate-800 shadow-sm overflow-hidden flex flex-col relative z-0">
            <div class="px-6 py-4">
                <h3 class="font-bold text-sm uppercase tracking-wider text-slate-900 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                    Order Notes
                </h3>
                <p class="text-sm text-slate-500 italic leading-relaxed">
                    "{{ $order->customer_notes }}"
                </p>
            </div>
            <!-- Decorative quote icon -->
            <svg class="absolute bottom-0 right-0 w-16 h-16 text-slate-900/5 -z-10 transform translate-x-2 translate-y-2" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
        </div>
        @endif

    </div>

</div>
@endsection
