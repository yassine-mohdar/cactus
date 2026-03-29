@extends('admin.layouts.app')

@section('title', 'Inventory Report')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Inventory Report</h1>
            <p class="page-subtitle">Monitor available units, low-stock risk, valuation, and category distribution across the current inventory ledger.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">Threshold {{ $threshold }}</span>
            <x-admin.button href="{{ route('admin.reports.export.inventory') }}" variant="secondary">Export Inventory</x-admin.button>
            <x-admin.button href="{{ route('admin.reports.export.products') }}" variant="outline">Export Products</x-admin.button>
        </div>
    </div>
@endsection

@section('content')
    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Products</p>
            <p class="stat-value">{{ number_format($stockSummary['total_products']) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">{{ number_format($stockSummary['total_variants']) }} variants</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">In Stock</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($stockSummary['in_stock']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Low Stock</p>
            <p class="stat-value text-[#B97A22]">{{ number_format($stockSummary['low_stock']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Out of Stock</p>
            <p class="stat-value text-[#C94B3C]">{{ number_format($stockSummary['out_of_stock']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Units</p>
            <p class="stat-value">{{ number_format($stockSummary['total_units']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Inventory Value</p>
            <p class="stat-value text-[#235B8C]">{{ number_format((float) $stockSummary['total_value'], 2) }}</p>
            <p class="mt-2 text-sm text-[#5D6F66]">MAD</p>
        </div>
    </div>

    <form method="GET" class="filter-toolbar mb-6">
        <div class="filter-grid xl:grid-cols-4">
            <div class="filter-field">
                <label class="filter-label" for="inventory-threshold">Low Stock Threshold</label>
                <input id="inventory-threshold" type="number" name="threshold" value="{{ $threshold }}" min="1" class="input-field">
            </div>
            <div class="filter-field xl:col-span-3 xl:items-end">
                <div class="flex flex-wrap gap-3">
                    <x-admin.button type="submit" variant="primary">Apply Threshold</x-admin.button>
                    <x-admin.button href="{{ route('admin.reports.inventory') }}" variant="outline">Reset</x-admin.button>
                </div>
            </div>
        </div>
    </form>

    <div class="grid gap-6 xl:grid-cols-2 mb-6">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Low Stock Items</h2>
                    <p class="datatable-subtitle">Products with available inventory above zero but at or below the alert threshold.</p>
                </div>
                <span class="datatable-meta">{{ $lowStock->count() }} items</span>
            </div>

            @if($lowStock->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No low stock alerts right now.</p>
                        <p class="text-sm text-[#617169]">The current threshold does not flag any items as low availability.</p>
                    </div>
                </div>
            @else
                <x-nino.table scrollClass="max-h-[28rem] overflow-y-auto">
                    <x-slot:head>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>SKU</th>
                        <th class="text-right">Available</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($lowStock as $item)
                            <tr>
                                <td class="font-medium text-[#17302A]">{{ $item->product?->name ?? '—' }}</td>
                                <td>{{ $item->branch?->name ?? 'Global' }}</td>
                                <td class="font-mono text-xs">{{ $item->sku ?? '—' }}</td>
                                <td class="text-right font-semibold text-[#B97A22]">{{ number_format($item->available_quantity) }}</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Out of Stock</h2>
                    <p class="datatable-subtitle">Stock records with zero or negative availability that need replenishment attention.</p>
                </div>
                <span class="datatable-meta">{{ $outOfStock->count() }} items</span>
            </div>

            @if($outOfStock->isEmpty())
                <div class="datatable-empty">
                    <div class="datatable-empty-panel">
                        <p class="text-sm font-semibold text-[#17302A]">No out-of-stock records found.</p>
                        <p class="text-sm text-[#617169]">Everything currently has positive available inventory.</p>
                    </div>
                </div>
            @else
                <x-nino.table scrollClass="max-h-[28rem] overflow-y-auto">
                    <x-slot:head>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>SKU</th>
                        <th class="text-right">Available</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($outOfStock as $item)
                            <tr>
                                <td class="font-medium text-[#17302A]">{{ $item->product?->name ?? '—' }}</td>
                                <td>{{ $item->branch?->name ?? 'Global' }}</td>
                                <td class="font-mono text-xs">{{ $item->sku ?? '—' }}</td>
                                <td class="text-right font-semibold text-[#C94B3C]">{{ number_format($item->available_quantity) }}</td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            @endif
        </div>
    </div>

    <div class="datatable-shell mb-6">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Stock by Category</h2>
                <p class="datatable-subtitle">Aggregate available stock and product count across category groupings.</p>
            </div>
            <span class="datatable-meta">{{ $stockByCategory->count() }} categories</span>
        </div>

        @if($stockByCategory->isEmpty())
            <div class="datatable-empty">
                <div class="datatable-empty-panel">
                    <p class="text-sm font-semibold text-[#17302A]">No category inventory data available.</p>
                    <p class="text-sm text-[#617169]">Category distribution will appear once products are stocked and assigned.</p>
                </div>
            </div>
        @else
            <x-nino.table>
                <x-slot:head>
                    <th>Category</th>
                    <th class="text-right">Products</th>
                    <th class="text-right">Total Stock</th>
                </x-slot:head>

                <x-slot:body>
                    @foreach($stockByCategory as $row)
                        <tr>
                            <td class="font-medium text-[#17302A]">{{ $row->category }}</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format($row->product_count) }}</td>
                            <td class="text-right font-semibold text-[#17302A]">{{ number_format($row->total_stock) }}</td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-nino.table>
        @endif
    </div>

    <x-admin.card title="Import Catalog (CSV)">
        <div class="space-y-4">
            <p class="form-copy">Bulk import catalog rows using the supported headers: <span class="font-semibold text-[#17302A]">Name, SKU, Price, Stock, Status</span>. Existing products are matched by name.</p>

            <form action="{{ route('admin.reports.import.catalog') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" class="form-upload" required>
                <div class="form-actions">
                    <x-admin.button type="submit" variant="primary">Import Catalog</x-admin.button>
                </div>
            </form>

            @if(session('import_errors'))
                <div class="rounded-[1.25rem] border border-[#EFC5BE] bg-[#FCEDEA] px-4 py-3 text-sm text-[#C94B3C]">
                    @foreach(session('import_errors') as $e)
                        <p>{{ $e }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    </x-admin.card>
@endsection
