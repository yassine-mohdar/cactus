@extends('admin.layouts.app')

@section('title', 'Financial Reports')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Financial Reports</h1>
            <p class="page-subtitle">Inspect payment method mix, gateway health, COD reconciliation, refunds, and the cost of fees and discounts from one control surface.</p>
        </div>

        <span class="datatable-meta">Live Finance Snapshot</span>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 rounded-[1.35rem] border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 shadow-[0_18px_42px_-30px_rgba(8,127,91,0.45)] backdrop-blur">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 rounded-[1.35rem] border border-[#F3E0B6] bg-[#FFF4DF] px-4 py-3 text-sm font-medium text-[#8A671E] shadow-[0_18px_42px_-30px_rgba(185,122,34,0.35)] backdrop-blur">
            {{ session('warning') }}
        </div>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Gross Revenue</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format((float) $revenue['gross'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Fees</p>
            <p class="stat-value text-[#B97A22]">-{{ number_format((float) $revenue['fees'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Refunds</p>
            <p class="stat-value text-[#C94B3C]">-{{ number_format((float) $revenue['refunds'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Discounts</p>
            <p class="stat-value text-[#7D3BB0]">-{{ number_format((float) $revenue['discounts'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Net Revenue</p>
            <p class="stat-value">{{ number_format((float) $revenue['net'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <div x-data="{ tab: 'methods' }" class="space-y-6">
        <div class="filter-toolbar">
            <nav class="flex flex-wrap gap-2" aria-label="Finance report tabs">
                @foreach([
                    'methods' => 'Payment Methods',
                    'gateways' => 'Gateways',
                    'cod' => 'COD Reconciliation',
                    'refunds' => 'Refunds',
                    'fees' => 'Discounts & Fees',
                ] as $tabKey => $tabLabel)
                    <button
                        type="button"
                        @click="tab = '{{ $tabKey }}'"
                        :class="tab === '{{ $tabKey }}' ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848] shadow-[0_12px_30px_-22px_rgba(36,88,72,0.42)]' : 'border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/85 text-[#617169] hover:bg-[#F7F2E8] hover:text-[#17302A]'"
                        class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div x-show="tab === 'methods'" x-cloak class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Payment Methods</h2>
                    <p class="datatable-subtitle">Completed transaction volume grouped by the customer payment method used at checkout.</p>
                </div>
                <span class="datatable-meta">{{ $byMethod->count() }} methods</span>
            </div>

            @if($byMethod->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No payment method data available.</p>
                        <p class="text-sm text-[#617169]">Completed payments will appear here once transactions are processed.</p>
                    </div>
                </div>
            @else
                <x-nino.table>
                    <x-slot:head>
                        <th>Method</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-right">Total Volume</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($byMethod as $m)
                            <tr>
                                <td class="font-medium text-[#17302A]">{{ app(\App\Modules\Payments\Services\PaymentMethodAvailabilityService::class)->legacyIcon($m->payment_method) }} {{ app(\App\Modules\Payments\Services\PaymentMethodAvailabilityService::class)->legacyLabel($m->payment_method) ?? $m->payment_method }}</td>
                                <td class="text-center font-semibold text-[#17302A]">{{ number_format($m->count) }}</td>
                                <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) $m->total, 2) }} MAD</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>

        <div x-show="tab === 'gateways'" x-cloak class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Gateway Reliability</h2>
                    <p class="datatable-subtitle">Success and failure distribution for gateways with recorded payment attempts.</p>
                </div>
                <span class="datatable-meta">{{ $gatewayStats->count() }} gateways</span>
            </div>

            @if($gatewayStats->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No gateway metrics available.</p>
                        <p class="text-sm text-[#617169]">Gateway health appears once payment attempts have been recorded.</p>
                    </div>
                </div>
            @else
                <x-nino.table>
                    <x-slot:head>
                        <th>Gateway</th>
                        <th class="text-center">Completed</th>
                        <th class="text-center">Failed</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Success Rate</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($gatewayStats as $gateway => $statuses)
                            @php
                                $completed = $statuses->firstWhere('status', 'completed')?->count ?? 0;
                                $failed = $statuses->firstWhere('status', 'failed')?->count ?? 0;
                                $pending = $statuses->firstWhere('status', 'pending')?->count ?? 0;
                                $total = $completed + $failed + $pending;
                                $rate = $total > 0 ? round($completed / $total * 100, 1) : 0;
                                $rateClass = $rate >= 95 ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : ($rate >= 80 ? 'border-[#F3E0B6] bg-[#FFF4DF] text-[#B97A22]' : 'border-[#F2CCC5] bg-[#FCEDEA] text-[#C94B3C]');
                            @endphp
                            <tr>
                                <td class="font-medium capitalize text-[#17302A]">{{ $gateway }}</td>
                                <td class="text-center font-semibold text-[#0F7A54]">{{ number_format($completed) }}</td>
                                <td class="text-center font-semibold text-[#C94B3C]">{{ number_format($failed) }}</td>
                                <td class="text-center font-semibold text-[#B97A22]">{{ number_format($pending) }}</td>
                                <td class="text-center">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $rateClass }}">{{ $rate }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>

        <div x-show="tab === 'cod'" x-cloak class="space-y-6">
            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-label">Pending</p>
                    <p class="stat-value text-[#B97A22]">{{ number_format($codStats['pending']) }}</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Collected</p>
                    <p class="stat-value text-[#235B8C]">{{ number_format($codStats['collected']) }}</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Deposited</p>
                    <p class="stat-value text-[#7D3BB0]">{{ number_format($codStats['deposited']) }}</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Reconciled</p>
                    <p class="stat-value text-[#0F7A54]">{{ number_format($codStats['reconciled']) }}</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Discrepancies</p>
                    <p class="stat-value text-[#C94B3C]">{{ number_format($codStats['discrepancies']) }}</p>
                </div>
            </div>

            <x-admin.card title="Outstanding COD Transactions">
                <div class="space-y-4">
                    <p class="form-copy">Pending COD amount: <span class="font-semibold text-[#17302A]">{{ number_format((float) $codStats['pending_amount'], 2) }} MAD</span>. Unreconciled amount: <span class="font-semibold text-[#17302A]">{{ number_format((float) $codStats['unreconciled_amount'], 2) }} MAD</span>.</p>
                </div>
            </x-admin.card>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">COD Queue</h2>
                        <p class="datatable-subtitle">Move unreconciled COD transactions through collection, deposit, and reconciliation steps.</p>
                    </div>
                    <span class="datatable-meta">{{ $codTransactions->count() }} transactions</span>
                </div>

                @if($codTransactions->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No unreconciled COD transactions.</p>
                            <p class="text-sm text-[#617169]">The COD queue is currently clear.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table>
                        <x-slot:head>
                            <th>Reference</th>
                            <th>Order</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">COD Status</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </x-slot:head>

                        <x-slot:body>
                            @foreach($codTransactions as $cod)
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-[#17302A]">{{ $cod->reference }}</td>
                                    <td class="text-sm text-[#17302A]">{{ $cod->order?->reference_number ?? '—' }}</td>
                                    <td class="text-right font-semibold text-[#17302A]">{{ number_format((float) $cod->amount, 2) }} MAD</td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $cod->cod_status?->badgeColor() ?? 'bg-gray-100 text-gray-600' }}">{{ $cod->cod_status?->label() ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-sm text-[#617169]">{{ $cod->created_at->format('M d, Y') }}</td>
                                    <td class="text-right">
                                        <div class="table-actions">
                                            @if($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::PENDING)
                                                <form action="{{ route('admin.finance.reports.cod.collect', $cod) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="collected_by" value="{{ auth()->user()->first_name ?? 'Admin' }}">
                                                    <button type="submit" class="table-action-link">Collect</button>
                                                </form>
                                            @elseif($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::COLLECTED)
                                                <form action="{{ route('admin.finance.reports.cod.deposit', $cod) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="table-action-link">Deposit</button>
                                                </form>
                                            @elseif($cod->cod_status === \App\Modules\Finance\Enums\CodStatus::DEPOSITED)
                                                <form action="{{ route('admin.finance.reports.cod.reconcile', $cod) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="table-action-link">Reconcile</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>
        </div>

        <div x-show="tab === 'refunds'" x-cloak class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">Pending</p>
                <p class="stat-value text-[#B97A22]">{{ number_format($refundStats['pending']) }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Total Refunded</p>
                <p class="stat-value text-[#C94B3C]">{{ number_format((float) $refundStats['total_refunded'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg Refund</p>
                <p class="stat-value">{{ number_format((float) $refundStats['avg_refund'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Rejection Rate</p>
                <p class="stat-value">{{ number_format((float) $refundStats['rejection_rate'], 1) }}%</p>
            </div>
        </div>

        <div x-show="tab === 'fees'" x-cloak class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">Total Discounts</p>
                <p class="stat-value text-[#7D3BB0]">{{ number_format((float) $discountStats['total_discounts'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Total Fees</p>
                <p class="stat-value text-[#B97A22]">{{ number_format((float) $discountStats['total_fees'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg Discount</p>
                <p class="stat-value">{{ number_format((float) $discountStats['avg_discount'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg Fee</p>
                <p class="stat-value">{{ number_format((float) $discountStats['avg_fee'], 2) }}</p>
                <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
            </div>
        </div>
    </div>
@endsection
