@extends('admin.layouts.app')
@section('title', 'Inventory Report')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-slate-900">Inventory Report</h1><p class="text-sm text-slate-500 mt-1">Stock levels, low stock alerts, and inventory valuation.</p></div>
    <div class="flex gap-2">
        <a href="{{ route('admin.reports.export.inventory') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-sm font-medium rounded-lg hover:bg-white-dim transition-colors text-slate-900">📥 Export CSV</a>
        <a href="{{ route('admin.reports.export.products') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-sm font-medium rounded-lg hover:bg-white-dim transition-colors text-slate-900">📦 Export Products</a>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Products</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stockSummary['total_products']) }}</p><p class="text-xs text-slate-500">{{ $stockSummary['total_variants'] }} variants</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">In Stock</p><p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stockSummary['in_stock']) }}</p><p class="text-xs text-slate-500">{{ $stockSummary['total_units'] }} total units</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Low Stock</p><p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stockSummary['low_stock']) }}</p><p class="text-xs text-slate-500">≤ {{ $threshold }} available units</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Inventory Value</p><p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stockSummary['total_value'], 2) }}</p><p class="text-xs text-slate-500">MAD</p></div>
</div>

{{-- Threshold --}}
<form method="GET" class="mb-6 flex gap-3 items-end">
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">Low Stock Threshold</label><input type="number" name="threshold" value="{{ $threshold }}" min="1" class="w-24 rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
    <button type="submit" class="btn-primary">Apply</button>
</form>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Low Stock --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">⚠️ Low Stock Items ({{ $lowStock->count() }})</h2>
        <div class="max-h-80 overflow-y-auto">
            <table class="nino-table">
                <thead class="bg-white-dim border-b border-slate-200 sticky top-0"><tr><th class="px-3 py-2 font-semibold text-slate-900">Product</th><th class="px-3 py-2 font-semibold text-slate-900">Branch</th><th class="px-3 py-2 font-semibold text-slate-900">SKU</th><th class="px-3 py-2 font-semibold text-slate-900 text-right">Available</th></tr></thead>
                <tbody class="divide-y divide-outline-variant">
                    @foreach($lowStock as $item)
                    <tr><td class="px-3 py-2 text-slate-900">{{ $item->product?->name ?? '—' }}</td><td class="px-3 py-2 text-slate-500">{{ $item->branch?->name ?? 'Global' }}</td><td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $item->sku ?? '—' }}</td><td class="px-3 py-2 text-right font-bold text-yellow-600">{{ $item->available_quantity }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Out of Stock --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">🚫 Out of Stock ({{ $outOfStock->count() }})</h2>
        <div class="max-h-80 overflow-y-auto">
            <table class="nino-table">
                <thead class="bg-white-dim border-b border-slate-200 sticky top-0"><tr><th class="px-3 py-2 font-semibold text-slate-900">Product</th><th class="px-3 py-2 font-semibold text-slate-900">Branch</th><th class="px-3 py-2 font-semibold text-slate-900">SKU</th><th class="px-3 py-2 font-semibold text-slate-900 text-right">Available</th></tr></thead>
                <tbody class="divide-y divide-outline-variant">
                    @foreach($outOfStock as $item)
                    <tr><td class="px-3 py-2 text-slate-900">{{ $item->product?->name ?? '—' }}</td><td class="px-3 py-2 text-slate-500">{{ $item->branch?->name ?? 'Global' }}</td><td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $item->sku ?? '—' }}</td><td class="px-3 py-2 text-right font-bold text-red-600">{{ $item->available_quantity }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- By Category --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm p-6 mt-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Stock by Category</h2>
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200"><tr><th class="px-4 py-3 font-semibold text-slate-900">Category</th><th class="px-4 py-3 font-semibold text-slate-900 text-right">Products</th><th class="px-4 py-3 font-semibold text-slate-900 text-right">Total Stock</th></tr></thead>
        <tbody class="divide-y divide-outline-variant">
            @foreach($stockByCategory as $row)
            <tr><td class="px-4 py-3 font-medium text-slate-900">{{ $row->category }}</td><td class="px-4 py-3 text-right text-slate-900">{{ $row->product_count }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($row->total_stock) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Import --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm p-6 mt-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">📤 Import Catalog (CSV)</h2>
    <form action="{{ route('admin.reports.import.catalog') }}" method="POST" enctype="multipart/form-data" class="flex gap-3 items-end">
        @csrf
        <div class="flex-1"><input type="file" name="file" accept=".csv,.txt" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm"></div>
        <button type="submit" class="btn-primary">Import</button>
    </form>
    <p class="text-xs text-slate-500 mt-2">CSV with headers: Name, SKU, Price, Stock, Status. Existing products matched by name.</p>
    @if(session('import_errors'))<div class="mt-3 p-3 rounded bg-red-50 text-red-700 text-xs">@foreach(session('import_errors') as $e)<p>{{ $e }}</p>@endforeach</div>@endif
</div>
@endsection
