@extends('admin.layouts.app')

@section('title', 'Products')
@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Products</h1>
            <p class="page-subtitle">Manage catalog pricing, stock health, and publishing state from one list.</p>
        </div>
        @can('catalog.products.create')
            <x-admin.button href="{{ route('admin.catalog.products.create') }}" variant="primary" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span> Add Product
            </x-admin.button>
        @endcan
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-4 text-sm font-medium text-slate-900 bg-slate-100 p-4 rounded-md border border-slate-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-red-600 bg-red-500/10 p-4 rounded-md border border-red-500/20">
            {{ session('error') }}
        </div>
    @endif

    <x-nino.card noPadding>
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Catalog List</h2>
                <p class="datatable-subtitle">Skim product media, category placement, price, and stock without leaving the page.</p>
            </div>
            <span class="datatable-meta">{{ number_format($products->total()) }} products</span>
        </div>

        <div class="datatable-scroll">
            <x-nino.table>
                <thead>
                    <tr>
                        <th class="w-10">Img</th>
                        <th>Product & SKU</th>
                        <th>Categories</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Stock</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                @if($product->primary_image)
                                    <img src="{{ Storage::url($product->primary_image) }}" class="w-8 h-8 rounded object-cover border border-slate-300">
                                @else
                                    <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-[8px] font-bold text-slate-400 border border-slate-300">IMG</div>
                                @endif
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 truncate max-w-[200px]">
                                    <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate class="table-link">{{ $product->name }}</a>
                                </div>
                                <div class="text-[10px] text-slate-500 tracking-wider font-mono">{{ $product->sku ?: 'NO-SKU' }}</div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($product->categories as $cat)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 border border-slate-200 text-slate-600 truncate max-w-[100px]">{{ $cat->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right font-mono font-medium text-slate-900">
                                @if($product->price)
                                    {{ number_format($product->price, 2) }}
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </td>
                            <td class="text-right font-mono font-medium">
                                <span class="{{ $product->quantity <= 5 ? 'text-red-600 font-bold' : 'text-slate-900' }}">{{ $product->quantity }}</span>
                            </td>
                            <td class="text-center">
                                @if($product->status === 'published')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-50 border border-slate-200 text-slate-800 uppercase">Published</span>
                                @elseif($product->status === 'draft')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 border border-amber-200 text-amber-600 uppercase">Draft</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-50 border border-slate-200 text-slate-500 uppercase">Archived</span>
                                @endif
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
                                <div class="datatable-empty-panel">
                                    <p class="text-sm font-semibold text-slate-900">No products found.</p>
                                    <p class="text-sm text-slate-500">Create your first product to start filling the catalog.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-nino.table>
        </div>
        
        @if($products->hasPages())
            <div class="datatable-footer text-xs">
                {{ $products->links() }}
            </div>
        @endif
    </x-nino.card>
@endsection
