@extends('admin.layouts.app')

@section('title', 'Products')
@section('header')
    <x-nino.page-header
        title="Products"
        subtitle="Manage catalog pricing, stock health, and publishing state from one denser operational list.">
        <x-slot:actions>
            @can('catalog.products.create')
                <x-nino.button href="{{ route('admin.catalog.products.create') }}" variant="primary" icon="add">Add Product</x-nino.button>
            @endcan
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <x-nino.inline-alert tone="success" title="Catalog updated">
                {{ session('success') }}
            </x-nino.inline-alert>
        @endif
        @if (session('error'))
            <x-nino.inline-alert tone="danger" title="Catalog action failed">
                {{ session('error') }}
            </x-nino.inline-alert>
        @endif

        <div class="metric-grid">
            <div class="metric-tile">
                <p class="metric-label">Total Products</p>
                <h3 class="metric-value">{{ number_format($summary['total_products']) }}</h3>
                <p class="metric-subtitle">Every catalog product currently stored</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Published</p>
                <h3 class="metric-value">{{ number_format($summary['published']) }}</h3>
                <p class="metric-subtitle">Customer-facing products ready to sell</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Drafts</p>
                <h3 class="metric-value">{{ number_format($summary['drafts']) }}</h3>
                <p class="metric-subtitle">Products still being merchandised or reviewed</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Low Stock</p>
                <h3 class="metric-value">{{ number_format($summary['low_stock']) }}</h3>
                <p class="metric-subtitle">Products at or below the default threshold</p>
            </div>
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Catalog List</h2>
                    <p class="datatable-subtitle">Skim product media, category placement, price, and stock without leaving the page.</p>
                </div>
                <span class="datatable-meta">{{ number_format($products->total()) }} products</span>
            </div>

            <x-nino.table>
                <x-slot name="head">
                    <th class="w-10">Img</th>
                    <th>Product & SKU</th>
                    <th>Categories</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Stock</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Actions</th>
                </x-slot>
                <x-slot name="body">
                    @forelse($products as $product)
                        @php
                            $statusTone = match ($product->status) {
                                'published' => 'success',
                                'draft' => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td>
                                @if($product->primary_image)
                                    <img src="{{ Storage::url($product->primary_image) }}" class="h-8 w-8 rounded-md border border-[rgba(120,112,95,0.16)] object-cover">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FBFAF7] text-[8px] font-bold tracking-[0.18em] text-[#7A8681]">IMG</div>
                                @endif
                            </td>
                            <td>
                                <div class="max-w-[220px] truncate font-semibold text-[#1E2B27]">
                                    <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate class="table-link">{{ $product->name }}</a>
                                </div>
                                <div class="mt-0.5 font-mono text-[10px] tracking-[0.18em] text-[#7A8681]">{{ $product->sku ?: 'NO-SKU' }}</div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($product->categories as $cat)
                                        <span class="inline-flex max-w-[110px] items-center truncate rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">{{ $cat->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right font-mono font-medium text-[#1E2B27]">
                                @if($product->price)
                                    {{ number_format($product->price, 2) }}
                                @else
                                    <span class="text-[#9AA59F]">---</span>
                                @endif
                            </td>
                            <td class="text-right font-mono font-medium">
                                <span class="{{ $product->quantity <= 5 ? 'text-[#C45143] font-bold' : 'text-[#1E2B27]' }}">{{ $product->quantity }}</span>
                            </td>
                            <td class="text-center">
                                <x-nino.status-badge :tone="$statusTone" size="sm">{{ ucfirst($product->status) }}</x-nino.status-badge>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    @can('catalog.products.update')
                                        <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate class="table-action-link">Edit</a>
                                    @endcan

                                    @can('catalog.products.delete')
                                        <form action="{{ route('admin.catalog.products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="datatable-empty">
                                <div class="datatable-empty-panel py-16 text-center">
                                    <x-nino.empty-state
                                        title="Your catalog is empty"
                                        description="Get started by creating your first product. You can add images, set pricing, and organize by categories."
                                        icon="category">
                                    @can('catalog.products.create')
                                        <x-nino.button href="{{ route('admin.catalog.products.create') }}" variant="primary" icon="add">Add First Product</x-nino.button>
                                    @endcan
                                    </x-nino.empty-state>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
            </x-nino.table>

            @if($products->hasPages())
                <div class="datatable-footer text-xs">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
