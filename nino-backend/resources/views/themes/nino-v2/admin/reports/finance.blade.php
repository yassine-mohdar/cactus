@extends('admin.layouts.app')

@section('title', 'Finance Summary Report')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Finance Summary</h1>
            <p class="page-subtitle">Understand payment mix, gateway reliability, and net revenue after fees and refunds.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">{{ $from }} to {{ $to }}</span>
            <x-admin.button href="{{ route('admin.reports.export.transactions', request()->all()) }}" variant="secondary">Export Transactions</x-admin.button>
        </div>
    </div>
@endsection

@section('content')
    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-4">
            <div class="filter-field">
                <label class="filter-label" for="finance-from">From</label>
                <input id="finance-from" type="date" name="from" value="{{ $from }}" class="input-field">
            </div>
            <div class="filter-field">
                <label class="filter-label" for="finance-to">To</label>
                <input id="finance-to" type="date" name="to" value="{{ $to }}" class="input-field">
            </div>
            <div class="filter-field xl:col-span-2 xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Window</x-admin.button>
                    <x-admin.button href="{{ route('admin.reports.finance') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Gross Revenue</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format((float) $revenue['gross'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Fees</p>
            <p class="stat-value text-[#C94B3C]">-{{ number_format((float) $revenue['fees'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Refunds</p>
            <p class="stat-value text-[#B97A22]">-{{ number_format((float) $revenue['refunds'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Net Revenue</p>
            <p class="stat-value">{{ number_format((float) $revenue['net'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2 mb-6">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Payment Methods</h2>
                    <p class="datatable-subtitle">Completed payment volume grouped by customer payment method.</p>
                </div>
                <span class="datatable-meta">{{ $methodBreakdown->count() }} methods</span>
            </div>

            @if($methodBreakdown->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No payment method data available.</p>
                        <p class="text-sm text-[#617169]">Completed transactions will populate this table once the selected window has activity.</p>
                    </div>
                </div>
            @else
                <x-nino.table>
                    <x-slot:head>
                        <th>Method</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Volume</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($methodBreakdown as $m)
                            <tr>
                                <td class="font-medium capitalize text-[#17302A]">{{ str_replace('_', ' ', $m->payment_method) }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format($m->count) }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) $m->total, 2) }} MAD</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Gateway Performance</h2>
                    <p class="datatable-subtitle">Success rates and overall processing volume by payment gateway.</p>
                </div>
                <span class="datatable-meta">{{ $gatewayStats->count() }} gateways</span>
            </div>

            @if($gatewayStats->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No gateway performance data available.</p>
                        <p class="text-sm text-[#617169]">Gateway metrics will appear after completed or failed payment attempts are recorded.</p>
                    </div>
                </div>
            @else
                <x-nino.table>
                    <x-slot:head>
                        <th>Gateway</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Success</th>
                        <th class="text-right">Rate</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($gatewayStats as $g)
                            @php
                                $successRate = $g->total > 0 ? ($g->success / $g->total) * 100 : 0;
                                $rateClass = $successRate >= 95 ? 'text-[#0F7A54]' : ($successRate >= 80 ? 'text-[#B97A22]' : 'text-[#C94B3C]');
                            @endphp
                            <tr>
                                <td class="font-medium capitalize text-[#17302A]">{{ $g->gateway }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format($g->total) }}</td>
                                <td class="text-right font-semibold text-[#0F7A54]">{{ number_format($g->success) }}</td>
                                <td class="text-right font-semibold {{ $rateClass }}">{{ number_format($successRate, 1) }}%</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Daily Revenue</h2>
                <p class="datatable-subtitle">Gross, fees, and implied net revenue by day for completed payment activity.</p>
            </div>
            <span class="datatable-meta">{{ $dailyRevenue->count() }} days</span>
        </div>

        @if($dailyRevenue->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No daily revenue rows available.</p>
                    <p class="text-sm text-[#617169]">Select a period with payment activity to view financial movement over time.</p>
                </div>
            </div>
        @else
            <x-nino.table scrollClass="max-h-[28rem] overflow-y-auto">
                <x-slot:head>
                    <th>Date</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Fees</th>
                    <th class="text-right">Net</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($dailyRevenue as $d)
                        <tr>
                            <td class="font-medium text-[#17302A]">{{ $d->date }}</td>
                            <td class="text-right font-semibold text-[#0F7A54]">{{ number_format((float) $d->revenue, 2) }} MAD</td>
                            <td class="text-right font-semibold text-[#C94B3C]">-{{ number_format((float) $d->fees, 2) }} MAD</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) ($d->revenue - $d->fees), 2) }} MAD</td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif
    </div>
@endsection
