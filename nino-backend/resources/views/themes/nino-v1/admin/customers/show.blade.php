@extends('admin.layouts.app')

@section('title', 'Customer: ' . $customer->name)

@section('header')
<div class="flex items-center justify-between">
    <div class="flex flex-col">
        <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 transition-colors mb-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Customers
        </a>
        <h1 class="text-2xl font-bold font-headline text-slate-900 tracking-tight">{{ $customer->name }}</h1>
        <p class="text-sm text-slate-500 mt-1">Customer since {{ $customer->created_at->format('F j, Y') }}</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="px-3 py-1 bg-slate-50 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-slate-200 shadow-sm">
            {{ $customer->status }}
        </span>
        <a href="{{ route('admin.customers.edit', $customer) }}" class="p-2 text-outline hover:text-slate-900 transition-colors active:scale-95">
            <span class="material-symbols-outlined">edit</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Left Column: Metrics & Timeline --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Metrics Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                <div class="text-[10px] font-black text-outline uppercase tracking-widest mb-1">Total Spent</div>
                <div class="text-2xl font-black text-slate-900">{{ number_format($metrics['total_spent'], 2) }} <span class="text-sm font-bold text-outline">MAD</span></div>
            </div>
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                <div class="text-[10px] font-black text-outline uppercase tracking-widest mb-1">Total Orders</div>
                <div class="text-2xl font-black text-slate-900">{{ $metrics['total_orders'] }}</div>
            </div>
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                <div class="text-[10px] font-black text-outline uppercase tracking-widest mb-1">Avg. Order Value</div>
                <div class="text-2xl font-black text-slate-900">{{ number_format($metrics['average_order_value'], 2) }} <span class="text-sm font-bold text-outline">MAD</span></div>
            </div>
        </div>

        {{-- Latest Orders --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/10 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Recent Orders</h3>
                <span class="text-xs font-bold text-outline">{{ count($recentOrders) }} orders</span>
            </div>
            <div class="p-0">
                <table class="nino-table">
                    <thead class="bg-canvas/50 text-[10px] font-black text-outline uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Order ID</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($recentOrders as $order)
                            {{-- Order rows will go here --}}
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            
                                    No orders placed yet.
                                
                        </div>
                    </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Internal Notes --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/10">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Internal Admin Notes</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($notes as $note)
                        <div class="p-4 bg-canvas rounded-md border border-slate-200">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold text-slate-900">{{ $note->admin->name }}</span>
                                <span class="text-[10px] text-outline">{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-slate-500">{{ $note->note }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 italic text-center py-4">No internal notes for this customer.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Contact & Info --}}
    <div class="space-y-6">
        {{-- Contact Info Card --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/10">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Contact Information</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-outline uppercase tracking-widest mb-1">Email Address</label>
                    <a href="mailto:{{ $customer->email }}" class="text-sm font-bold text-slate-800 hover:underline">{{ $customer->email }}</a>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-outline uppercase tracking-widest mb-1">Phone Number</label>
                    <div class="text-sm font-bold text-slate-900">{{ $customer->phone ?? 'Not provided' }}</div>
                </div>
            </div>
        </div>

        {{-- Shipping Addresses --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/10">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Saved Addresses</h3>
            </div>
            <div class="p-6 space-y-4">
                @forelse($customer->addresses as $address)
                    <div class="pb-4 border-b border-slate-200 last:border-0 last:pb-0">
                        <div class="text-xs font-bold text-slate-900 mb-1">{{ $address->first_name }} {{ $address->last_name }}</div>
                        <div class="text-xs text-slate-500">{{ $address->address_line_1 }}</div>
                        <div class="text-xs text-slate-500">{{ $address->city }}, {{ $address->postal_code }}</div>
                        <div class="text-xs text-slate-800 font-bold mt-1 uppercase tracking-tighter">{{ $address->type }}</div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic">No addresses saved yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
