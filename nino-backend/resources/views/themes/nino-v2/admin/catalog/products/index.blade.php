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
    @php
        $supportsTags = $supportsTags ?? true;
        $pricingMeta = $pricingMeta ?? ['base_currency' => config('finance.base_currency', 'MAD')];
        $filters = $filters ?? ['search' => '', 'status' => '', 'category' => null, 'type' => '', 'stock' => ''];
    @endphp
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
                <p class="metric-label">Archived</p>
                <h3 class="metric-value">{{ number_format($summary['archived']) }}</h3>
                <p class="metric-subtitle">Retired products kept for reporting and history</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Featured</p>
                <h3 class="metric-value">{{ number_format($summary['featured']) }}</h3>
                <p class="metric-subtitle">Products highlighted in curated storefront placements</p>
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
                    <p class="datatable-subtitle">Skim product media, category placement, price, cost signals, and stock without leaving the page.</p>
                </div>
                <span class="datatable-meta">{{ number_format($products->total()) }} products · {{ $pricingMeta['base_currency'] }}</span>
            </div>

            <form id="product-bulk-form" method="POST" action="{{ route('admin.catalog.products.bulk') }}" class="border-b border-[rgba(120,112,95,0.12)] px-5 py-4">
                @csrf
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,14rem)_auto]">
                        <div>
                            <label class="form-label">Bulk action</label>
                            <select name="action" class="form-select">
                                <option value="">Choose action</option>
                                <option value="publish">Publish selected</option>
                                <option value="draft">Move to draft</option>
                                <option value="archive">Archive selected</option>
                                <option value="delete">Delete selected</option>
                            </select>
                        </div>
                        <x-nino.button type="submit" variant="secondary" size="sm" icon="playlist_add_check">Apply to Selected</x-nino.button>
                    </div>
                    <p class="text-xs text-[#61706B]">Bulk actions support publishing workflow cleanup and safe multi-record maintenance.</p>
                </div>
            </form>

            <form method="GET" action="{{ route('admin.catalog.products.index') }}" class="border-b border-[rgba(120,112,95,0.12)] px-5 py-4">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))]">
                    <div>
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-input" placeholder="Name, SKU, slug, barcode">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="draft" @selected($filters['status'] === 'draft')>Draft</option>
                            <option value="published" @selected($filters['status'] === 'published')>Published</option>
                            <option value="archived" @selected($filters['status'] === 'archived')>Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All categories</option>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All types</option>
                            <option value="simple" @selected($filters['type'] === 'simple')>Simple</option>
                            <option value="variable" @selected($filters['type'] === 'variable')>Variable</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Stock</label>
                        <select name="stock" class="form-select">
                            <option value="">All stock</option>
                            <option value="in" @selected($filters['stock'] === 'in')>Healthy stock</option>
                            <option value="low" @selected($filters['stock'] === 'low')>Low stock</option>
                            <option value="out" @selected($filters['stock'] === 'out')>Out of stock</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3">
                    <p class="text-xs text-[#61706B]">Filters stay on pagination and help operators isolate publishing, merchandising, and stock exceptions faster.</p>
                    <div class="flex items-center gap-2">
                        <x-nino.button href="{{ route('admin.catalog.products.index') }}" variant="ghost" size="sm">Reset</x-nino.button>
                        <x-nino.button type="submit" variant="secondary" size="sm" icon="filter_alt">Apply Filters</x-nino.button>
                    </div>
                </div>
            </form>

            <div class="datatable-scroll">
                <table class="nino-table">
                    <thead>
                        <tr class="hover:bg-transparent">
                            <th class="w-10 text-center">
                                <input type="checkbox" data-check-all class="size-4 rounded border-[rgba(120,112,95,0.24)] text-[#245848] focus:ring-[#245848]">
                            </th>
                            <th class="w-10">Img</th>
                            <th>Product & SKU</th>
                            <th>Type</th>
                            <th>{{ $supportsTags ? 'Categories & Tags' : 'Categories' }}</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Stock</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $statusTone = match ($product->status) {
                                    'published' => 'success',
                                    'draft' => 'warning',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="product-bulk-form" class="size-4 rounded border-[rgba(120,112,95,0.24)] text-[#245848] focus:ring-[#245848]">
                                </td>
                                <td>
                                    @if($product->primary_image)
                                        <img src="{{ Storage::url($product->primary_image) }}" alt="{{ $product->featuredImage?->altTextLabel() ?? $product->name }}" class="h-8 w-8 rounded-md border border-[rgba(120,112,95,0.16)] object-cover">
                                    @else
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FBFAF7] text-[8px] font-bold tracking-[0.18em] text-[#7A8681]">IMG</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="max-w-[220px] truncate font-semibold text-[#1E2B27]">
                                        <a href="{{ route('admin.catalog.products.edit', $product) }}" wire:navigate class="table-link">{{ $product->name }}</a>
                                    </div>
                                    @if($product->is_featured)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center rounded-md border border-[rgba(36,88,72,0.14)] bg-[#EAF3EE] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#245848]">Featured</span>
                                        </div>
                                    @endif
                                    <div class="mt-0.5 font-mono text-[10px] tracking-[0.18em] text-[#7A8681]">{{ $product->sku ?: 'NO-SKU' }}</div>
                                    <div class="mt-1 text-[10px] text-[#61706B]">{{ $product->slug }}</div>
                                    @if($product->excerpt())
                                        <div class="mt-1 text-[11px] leading-5 text-[#61706B]">{{ $product->excerpt(90) }}</div>
                                    @endif
                                    @if($product->hasPhysicalProfile())
                                        <div class="mt-1 text-[10px] text-[#61706B]">
                                            {{ $product->weight !== null ? rtrim(rtrim(number_format((float) $product->weight, 2, '.', ''), '0'), '.') . ' kg' : 'No weight' }}
                                            @if($product->dimensionsSummary())
                                                · {{ $product->dimensionsSummary() }}
                                            @endif
                                        </div>
                                    @endif
                                    <div class="mt-1 text-[10px] text-[#61706B]">
                                        {{ $product->images_count === 1 ? '1 media asset' : number_format($product->images_count) . ' media assets' }}
                                        @if($product->isVariable())
                                            · {{ $product->variants_count === 1 ? '1 variant' : number_format($product->variants_count) . ' variants' }}
                                        @endif
                                    </div>
                                    @if($product->badges() !== [])
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach($product->badges() as $badge)
                                                <span class="inline-flex items-center rounded-md border border-[rgba(196,81,67,0.14)] bg-[#FCF0ED] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#C45143]">{{ $badge }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="mt-1 text-[10px] text-[#61706B]">
                                        Merch {{ $product->related_products_count }}/{{ $product->upsell_products_count }}/{{ $product->cross_sell_products_count }}
                                    </div>
                                    <div class="mt-1 text-[10px] text-[#61706B]">
                                        SEO
                                        @if($product->meta_title)
                                            · title
                                        @endif
                                        @if($product->meta_description)
                                            · desc
                                        @endif
                                        @if($product->canonical_url)
                                            · canonical
                                        @endif
                                        @if($product->og_title || $product->og_description || $product->og_image)
                                            · og
                                        @endif
                                        @if($product->noindex)
                                            · noindex
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">
                                        {{ $product->typeLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($product->categories as $cat)
                                                <span class="inline-flex max-w-[110px] items-center truncate rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">{{ $cat->name }}</span>
                                            @empty
                                                <span class="text-[11px] text-[#8A948F]">No categories</span>
                                            @endforelse
                                        </div>
                                        @if($supportsTags && $product->tags->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($product->tags as $tag)
                                                    <span class="inline-flex max-w-[110px] items-center truncate rounded-md border border-[rgba(36,88,72,0.14)] bg-[#EAF3EE] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#245848]">#{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right font-mono font-medium text-[#1E2B27]">
                                    @if($product->effectivePrice() !== null)
                                        <div class="space-y-1">
                                            <div>{{ number_format($product->effectivePrice(), 2) }} {{ $pricingMeta['base_currency'] }}</div>
                                            @if($product->hasSalePrice() && $product->price !== null)
                                                <div class="text-[10px] text-[#8A948F] line-through">{{ number_format((float) $product->price, 2) }} {{ $pricingMeta['base_currency'] }}</div>
                                            @endif
                                            @if($product->hasCostPrice())
                                                <div class="text-[10px] text-[#61706B]">
                                                    Cost {{ number_format((float) $product->cost_price, 2) }} {{ $pricingMeta['base_currency'] }}
                                                    @if($product->marginPercent() !== null)
                                                        · Margin {{ rtrim(rtrim(number_format((float) $product->marginPercent(), 2, '.', ''), '0'), '.') }}%
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[#9AA59F]">---</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-medium">
                                    <span class="{{ $product->quantity <= 5 ? 'text-[#C45143] font-bold' : 'text-[#1E2B27]' }}">{{ $product->quantity }}</span>
                                </td>
                                <td class="text-center">
                                    <x-nino.status-badge :tone="$statusTone" size="sm">{{ $product->statusLabel() }}</x-nino.status-badge>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        @can('catalog.products.update')
                                            @if($product->isPublished())
                                                <form action="{{ route('admin.catalog.products.status', $product) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="status" value="draft">
                                                    <button type="submit" class="table-action-link">Move to Draft</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.catalog.products.status', $product) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="status" value="published">
                                                    <button type="submit" class="table-action-link">Publish</button>
                                                </form>
                                            @endif
                                        @endcan

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
                                <td colspan="9" class="datatable-empty">
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
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="datatable-footer text-xs">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-check-all]');

    if (!toggle) {
        return;
    }

    toggle.addEventListener('change', () => {
        document.querySelectorAll('input[name="product_ids[]"][form="product-bulk-form"]').forEach((checkbox) => {
            checkbox.checked = toggle.checked;
        });
    });
});
</script>
@endpush
