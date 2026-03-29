@extends('admin.layouts.app')

@section('title', 'Orders Report')

@section('header')
    @php
        $totalRevenue = (float) $statusBreakdown->sum(fn ($row) => (float) ($row->total ?? 0));
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Orders Report</h1>
            <p class="page-subtitle">Review order throughput, fulfillment status, and revenue movement across the selected operating window.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">{{ $from }} to {{ $to }}</span>
            <x-admin.button href="{{ route('admin.reports.export.orders', request()->all()) }}" variant="secondary">Export CSV</x-admin.button>
        </div>
    </div>
@endsection

@section('content')
    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-4">
            <div class="filter-field">
                <label class="filter-label" for="orders-report-from">From</label>
                <input id="orders-report-from" type="date" name="from" value="{{ $from }}" class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="orders-report-to">To</label>
                <input id="orders-report-to" type="date" name="to" value="{{ $to }}" class="input-field">
            </div>
            <div class="filter-field xl:col-span-2 xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Window</x-admin.button>
                    <x-admin.button href="{{ route('admin.reports.orders') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total Orders</p>
            <p class="stat-value">{{ number_format($fulfillment['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Revenue In Window</p>
            <p class="stat-value">{{ number_format($totalRevenue, 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Pending</p>
            <p class="stat-value text-[#B97A22]">{{ number_format($fulfillment['pending']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Delivered</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($fulfillment['delivered']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Cancelled</p>
            <p class="stat-value text-[#C94B3C]">{{ number_format($fulfillment['cancelled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Avg Items / Order</p>
            <p class="stat-value">{{ number_format((float) $avgItems, 1) }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Status Breakdown</h2>
                    <p class="datatable-subtitle">Orders and revenue grouped by lifecycle status.</p>
                </div>
                <span class="datatable-meta">{{ $statusBreakdown->count() }} statuses</span>
            </div>

            @if($statusBreakdown->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No orders were created in this window.</p>
                        <p class="text-sm text-[#617169]">Try widening the date range to compare fulfillment behavior.</p>
                    </div>
                </div>
            @else
                <x-nino.table>
                    <x-slot:head>
                        <th>Status</th>
                        <th class="text-right">Orders</th>
                        <th class="text-right">Revenue</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($statusBreakdown as $row)
                            @php
                                $statusLabel = $row->status ? ucwords(str_replace('_', ' ', $row->status)) : 'N/A';
                                $statusClass = match($row->status) {
                                    'delivered' => 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]',
                                    'shipped' => 'border-[#D6E7F8] bg-[#EEF5FC] text-[#235B8C]',
                                    'cancelled' => 'border-[#F2CCC5] bg-[#FCEDEA] text-[#C94B3C]',
                                    'pending' => 'border-[#F3E0B6] bg-[#FFF4DF] text-[#B97A22]',
                                    default => 'border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/85 text-[#617169]',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format($row->count) }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) ($row->total ?? 0), 2) }} MAD</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Daily Volume</h2>
                    <p class="datatable-subtitle">Daily order count and booked revenue across the reporting range.</p>
                </div>
                <span class="datatable-meta">{{ $dailyVolume->count() }} days</span>
            </div>

            @if($dailyVolume->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No daily volume to chart.</p>
                        <p class="text-sm text-[#617169]">Order activity will appear here once the selected period contains transactions.</p>
                    </div>
                </div>
            @else
                <x-nino.table scrollClass="max-h-[28rem] overflow-y-auto">
                    <x-slot:head>
                        <th>Date</th>
                        <th class="text-right">Orders</th>
                        <th class="text-right">Revenue</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($dailyVolume as $row)
                            <tr>
                                <td class="font-medium text-[#17302A]">{{ $row->date }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format($row->count) }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) ($row->total ?? 0), 2) }} MAD</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>
    </div>
@endsection
