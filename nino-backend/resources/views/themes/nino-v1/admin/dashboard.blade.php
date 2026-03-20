@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Welcome back, ' . explode(' ', auth()->user()->name)[0])
@section('subheader', 'Operational overview for today, ' . now()->format('M d, Y') . '.')

@section('content')

<div class="space-y-6">
    {{-- Platform Admin Metrics --}}
    @if(auth()->user()->hasRole(['Super Admin', 'Platform Admin']))
        <div>
            <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-3">Platform Overview</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-nino.metric-card 
                    title="Total Revenue" 
                    icon="payments" 
                    :value="number_format($metrics['totalRevenue'] ?? 0, 2)" 
                    subtitle="MAD" 
                />
                
                <x-nino.metric-card 
                    title="Orders Today" 
                    icon="package_2" 
                    :value="$metrics['totalOrders'] ?? 0" 
                />
                
                <x-nino.metric-card 
                    title="Customers" 
                    icon="group" 
                    :value="$metrics['totalCustomers'] ?? 0" 
                />
                
                <x-nino.metric-card 
                    title="Inventory Alerts" 
                    icon="inventory_2" 
                    :value="$metrics['lowStockCount'] ?? 0" 
                    color="error"
                />
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Shipping Agent Action Board --}}
        @if(auth()->user()->hasRole(['Super Admin', 'Shipping Agent']))
                <x-nino.card title="Fulfillment Center">
                <x-slot:header>
                    <x-nino.button href="{{ route('admin.orders.index', ['status' => 'preparing']) }}" size="sm" variant="secondary">View Queue</x-nino.button>
                </x-slot:header>
                <div class="text-center py-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-indigo-50 mb-6 border border-indigo-100 shadow-sm shadow-indigo-500/10">
                        <span class="material-symbols-outlined text-4xl text-indigo-600 drop-shadow-sm">local_shipping</span>
                    </div>
                    <h3 class="text-6xl font-black text-indigo-600 tracking-tighter mb-2">{{ $metrics['pendingShipments'] ?? 0 }}</h3>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-widest mt-2">Orders awaiting shipment</p>
                </div>
            </x-nino.card>
        @endif

        {{-- Support Queue Dashboard --}}
        @if(auth()->user()->hasRole(['Super Admin', 'Customer Support Agent']))
            <x-nino.card title="Support Desk" noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ route('admin.support.lookup') }}" size="sm" variant="secondary" icon="search">Lookup</x-nino.button>
                </x-slot:header>
                <div class="p-6 border-b border-red-100 bg-red-50/50 flex justify-between items-center">
                    <span class="text-sm font-bold text-red-600 uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.2em]">error</span> Open Tickets
                    </span>
                    <span class="text-3xl font-black text-red-600">{{ $metrics['openTickets'] ?? 0 }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse(($metrics['recentTickets'] ?? []) as $ticket)
                        <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                            <div>
                                <a href="{{ route('admin.support.issues.show', $ticket) }}" class="text-sm font-extrabold text-slate-800 hover:underline" wire:navigate>
                                    {{ $ticket->reference_number }}
                                </a>
                                <p class="text-xs font-medium text-slate-500 truncate max-w-[250px] mt-0.5">{{ $ticket->subject }}</p>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-widest px-3 py-1 rounded-[10px] bg-surface border border-slate-300">{{ $ticket->status->label() }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-500">
                            No active support issues. All clear!
                        </div>
                    @endforelse
                </div>
            </x-nino.card>
        @endif

        {{-- SEO/Content Manager Dashboard --}}
        @if(auth()->user()->hasRole(['Super Admin', 'SEO / Content Manager']))
            <x-nino.card title="Content Engine">
                <div class="flex items-center justify-between p-4 border border-amber-200 rounded-md bg-amber-50 mb-3 shadow-sm shadow-amber-500/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-amber-100/50 flex items-center justify-center border border-amber-200/50">
                            <span class="material-symbols-outlined text-amber-600">draw</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-amber-900">Draft Posts</p>
                            <p class="text-xs text-amber-600 font-medium">Needs review before publishing</p>
                        </div>
                    </div>
                    <span class="text-2xl font-black text-amber-600">{{ $metrics['draftPosts'] ?? 0 }}</span>
                </div>
                <!-- Future metrics: Missing Meta Tags, Broken Links, etc. -->
            </x-nino.card>
        @endif
    </div>
</div>

@endsection
