@extends('admin.layouts.app')

@section('title', 'Sales Report')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Sales Report</h1>
            <p class="page-subtitle">Track revenue, order momentum, and best-performing products over the period and grouping that matter to the team.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">{{ strtoupper($groupBy) }} view</span>
            <x-admin.button href="{{ route('admin.reports.export.orders', request()->all()) }}" variant="secondary">Export CSV</x-admin.button>
        </div>
    </div>
@endsection

@section('content')
    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-5">
            <div class="filter-field">
                <label class="filter-label" for="sales-from">From</label>
                <input id="sales-from" type="date" name="from" value="{{ $from }}" class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="sales-to">To</label>
                <input id="sales-to" type="date" name="to" value="{{ $to }}" class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="sales-group-by">Group By</label>
                <select id="sales-group-by" name="group_by" class="input-field">
                    <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Day</option>
                    <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Week</option>
                    <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Month</option>
                </select>
            </div>
            <div class="filter-field xl:col-span-2 xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Window</x-admin.button>
                    <x-admin.button href="{{ route('admin.reports.sales') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total Revenue</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format((float) $summary['total_revenue'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Orders</p>
            <p class="stat-value text-[#235B8C]">{{ number_format($summary['total_orders']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Avg Order Value</p>
            <p class="stat-value">{{ number_format((float) $summary['avg_order_value'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Completed Payments</p>
            <p class="stat-value text-[#7D3BB0]">{{ number_format((float) $summary['total_transactions'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <div class="datatable-shell mb-6">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Sales Over Time</h2>
                <p class="datatable-subtitle">Revenue and order count grouped by {{ $groupBy }} to show trend direction and average order size.</p>
            </div>
            <span class="datatable-meta">{{ $salesOverTime->count() }} rows</span>
        </div>

        @if($salesOverTime->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No sales data in this period.</p>
                    <p class="text-sm text-[#617169]">Adjust the date window or grouping to inspect a wider range.</p>
                </div>
            </div>
        @else
            <x-nino.table>
                <x-slot:head>
                    <th>Period</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Avg Value</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($salesOverTime as $row)
                        <tr>
                            <td class="font-medium text-[#17302A]">{{ $row->period }}</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format($row->order_count) }}</td>
                            <td class="text-right font-semibold text-[#0F7A54]">{{ number_format((float) ($row->revenue ?? 0), 2) }} MAD</td>
                            <td class="text-right text-[#17302A]">{{ number_format((float) ($row->avg_order_value ?? 0), 2) }} MAD</td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Top Products</h2>
                <p class="datatable-subtitle">The ten products generating the most revenue within the selected sales window.</p>
            </div>
            <span class="datatable-meta">Top 10</span>
        </div>

        @if($topProducts->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No product sales found.</p>
                    <p class="text-sm text-[#617169]">Top performers will appear once completed orders exist in the selected range.</p>
                </div>
            </div>
        @else
            <x-nino.table>
                <x-slot:head>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-right">Qty Sold</th>
                    <th class="text-right">Revenue</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($topProducts as $i => $p)
                        <tr>
                            <td class="text-[#6E7D75]">{{ $i + 1 }}</td>
                            <td class="font-medium text-[#17302A]">{{ $p->product_name }}</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format($p->total_qty) }}</td>
                            <td class="text-right font-semibold text-[#0F7A54]">{{ number_format((float) ($p->total_revenue ?? 0), 2) }} MAD</td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif
    </div>
@endsection
