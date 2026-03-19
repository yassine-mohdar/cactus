@extends('admin.layouts.app')

@section('header', 'Products')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Products</h1>
        @can('catalog.products.create')
            <x-admin.button href="{{ route('admin.catalog.products.create') }}" variant="primary">
                Add Product
            </x-admin.button>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-muted">
                <thead class="bg-gray-50/50 text-xs uppercase text-ink/70">
                    <tr>
                        <th class="p-4 font-semibold border-b border-border">Product</th>
                        <th class="p-4 font-semibold border-b border-border">SKU</th>
                        <th class="p-4 font-semibold border-b border-border">Categories</th>
                        <th class="p-4 font-semibold border-b border-border text-center">Price</th>
                        <th class="p-4 font-semibold border-b border-border text-center">Stock</th>
                        <th class="p-4 font-semibold border-b border-border text-center">Status</th>
                        <th class="p-4 font-semibold border-b border-border text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="p-4 font-medium text-ink">
                                <div class="flex items-center space-x-3">
                                    @if($product->primary_image)
                                        <img src="{{ Storage::url($product->primary_image) }}" class="w-10 h-10 rounded object-cover border border-border">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 border border-border flex items-center justify-center text-xs text-gray-400">IMG</div>
                                    @endif
                                    <div class="flex flex-col">
                                        <span class="font-bold">{{ $product->name }}</span>
                                        <span class="text-xs text-ink/50">{{ ucfirst($product->type) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">{{ $product->sku ?: '--' }}</td>
                            <td class="p-4 flex flex-wrap gap-1">
                                @foreach($product->categories as $cat)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800">
                                        {{ $cat->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="p-4 text-center">
                                @if($product->price)
                                    ${{ number_format($product->price, 2) }}
                                @else
                                    --
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                {{ $product->quantity }}
                            </td>
                            <td class="p-4 text-center">
                                @if($product->status === 'published')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published</span>
                                @elseif($product->status === 'draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Draft</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Archived</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-2">
                                @can('catalog.products.update')
                                    <a href="{{ route('admin.catalog.products.edit', $product) }}" class="text-sage hover:text-sage-dark font-medium transition">Edit</a>
                                @endcan
                                
                                @can('catalog.products.delete')
                                    <form action="{{ route('admin.catalog.products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-medium transition">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-ink-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
            <div class="mt-4 border-t border-border pt-4 px-4">
                {{ $products->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
