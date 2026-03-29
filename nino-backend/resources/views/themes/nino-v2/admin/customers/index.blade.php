@extends('admin.layouts.app')

@section('title', 'Customers')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Customers</h1>
            <p class="page-subtitle">Monitor growth, search customer records quickly, and jump straight into order history or support context.</p>
        </div>

        <x-nino.button href="{{ route('admin.customers.create') }}" variant="primary" icon="person_add">
            New Customer
        </x-nino.button>
    </div>
@endsection

@section('content')
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Customer Base</p>
            <p class="stat-value">{{ number_format((int) ($summary->total_customers ?? 0)) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active Accounts</p>
            <p class="stat-value">{{ number_format((int) ($summary->active_customers ?? 0)) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">New In 30 Days</p>
            <p class="stat-value">{{ number_format((int) ($summary->new_last_30_days ?? 0)) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Orders Across Customers</p>
            <p class="stat-value">{{ number_format((int) ($summary->total_orders ?? 0)) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-4xl">
                <div class="filter-field">
                    <label class="filter-label" for="customers-search">Search</label>
                    <input
                        id="customers-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, email, or phone"
                        class="input-field"
                    >
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="customers-status">Status</label>
                    <select id="customers-status" name="status" class="input-field">
                        <option value="">All customers</option>
                        <option value="active" @selected(request('status') === 'active')>Active only</option>
                        <option value="suspended" @selected(request('status') === 'suspended')>Suspended only</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search') || request()->filled('status'))
                    <a href="{{ route('admin.customers.index') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Customer Directory</h2>
                <p class="datatable-subtitle">Use this table as the operational handoff point between commerce, support, and retention workflows.</p>
            </div>
            <span class="datatable-meta">{{ number_format($customers->total()) }} records</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Customer</th>
                <th class="text-left">Contact</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Lifetime Value</th>
                <th class="text-left">Joined</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </x-slot>

            <x-slot name="body">
                @forelse($customers as $customer)
                    <tr>
                        <td class="text-left">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-[1rem] border border-[rgba(145,133,109,0.16)] bg-[#F7F2E8] text-sm font-bold text-[#245848]">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($customer->full_name ?: $customer->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="table-link">{{ $customer->full_name ?: $customer->name }}</a>
                                    <p class="table-muted">{{ $customer->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-left">
                            <p class="text-sm font-semibold text-[#17302A]">{{ $customer->phone ?: 'No phone provided' }}</p>
                            <p class="table-muted">{{ $customer->email }}</p>
                        </td>
                        <td class="text-right">
                            <span class="table-mono font-semibold text-[#17302A]">{{ number_format((int) $customer->orders_count) }}</span>
                        </td>
                        <td class="text-right">
                            <span class="table-mono font-semibold text-[#17302A]">{{ number_format((float) ($customer->lifetime_value ?? 0), 2) }}</span>
                            <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#708078]">MAD</span>
                        </td>
                        <td class="text-left">
                            <p class="text-sm font-semibold text-[#17302A]">{{ $customer->created_at->format('M j, Y') }}</p>
                            <p class="table-muted">{{ $customer->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="text-center">
                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] {{ $customer->status === 'active' ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : 'border-[#EFC5BE] bg-[#FCEDEA] text-[#C94B3C]' }}">
                                {{ $customer->status }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="table-action-link">View</a>
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="table-action-link">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="datatable-empty">
                            <div class="datatable-empty-panel py-16">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] text-[#708078]">
                                    <span class="material-symbols-outlined text-[1.9rem]">group</span>
                                </div>
                                <h3 class="mt-2 text-lg font-bold text-[#17302A]">No customers match these filters.</h3>
                                <p class="text-sm text-[#617169]">Try broadening your search or add a new customer record manually.</p>
                                <div class="mt-3">
                                    <x-nino.button href="{{ route('admin.customers.create') }}" variant="primary" icon="person_add">
                                        Create Customer
                                    </x-nino.button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        @if($customers->hasPages())
            <div class="datatable-footer">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
