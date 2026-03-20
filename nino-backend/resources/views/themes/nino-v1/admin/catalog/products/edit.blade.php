@extends('admin.layouts.app')

@section('header', 'Edit Product')

@section('content')
    <div class="mb-6 flex items-center space-x-4">
        <a href="{{ route('admin.catalog.products.index') }}" class="text-slate-500 hover:text-slate-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Product: {{ $product->name }}</h1>
    </div>

    <form action="{{ route('admin.catalog.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Details -->
                <x-admin.card title="Basic Details">
                    <div class="space-y-4">
                        <x-admin.input name="name" label="Product Name" :value="old('name', $product->name)" required />
                        
                        <div class="grid grid-cols-2 gap-4">
                            <x-admin.input name="sku" label="SKU (Stock Keeping Unit)" :value="old('sku', $product->sku)" />
                            <x-admin.input name="barcode" label="Barcode (ISBN, UPC, GTIN, etc.)" :value="old('barcode', $product->barcode)" />
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Short Description</label>
                            <x-admin.textarea name="short_description" rows="3">{{ old('short_description', $product->short_description) }}</x-admin.textarea>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Full Description</label>
                            <x-admin.textarea name="description" rows="8">{{ old('description', $product->description) }}</x-admin.textarea>
                        </div>
                    </div>
                </x-admin.card>

                <!-- Pricing & Inventory -->
                <div x-data="{ type: '{{ old('type', $product->type) }}' }">
                    <x-admin.card title="Pricing & Inventory">
                        <div class="mb-4">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Product Type</label>
                            <x-admin.select name="type" x-model="type">
                                <option value="simple">Simple Product</option>
                                <option value="variable">Variable Product</option>
                            </x-admin.select>
                        </div>
                        
                        <!-- Shown only if simple -->
                        <div x-show="type === 'simple'" x-transition class="space-y-4">
                            <div class="grid grid-cols-3 gap-4">
                                <x-admin.input type="number" step="0.01" name="price" label="Regular Price" :value="old('price', $product->price)" />
                                <x-admin.input type="number" step="0.01" name="sale_price" label="Sale Price" :value="old('sale_price', $product->sale_price)" />
                                <x-admin.input type="number" step="0.01" name="cost_price" label="Cost (internal)" :value="old('cost_price', $product->cost_price)" />
                            </div>
                            
                            <hr class="border-t border-dashed border-slate-300">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <x-admin.input type="number" name="quantity" label="Stock Quantity" :value="old('quantity', $product->quantity)" />
                            </div>
                        </div>
                        
                        <!-- Shown if variable -->
                        <div x-show="type === 'variable'" x-cloak class="p-4 bg-slate-50 rounded-md border border-slate-300 text-sm text-slate-600">
                            <strong>Note:</strong> Pricing and inventory for variable products are managed at the Variant level.
                            <div class="mt-3">
                                <x-admin.button type="button" variant="secondary" class="w-full justify-center">Manage Variants (Coming Soon)</x-admin.button>
                            </div>
                        </div>
                    </x-admin.card>
                </div>

                <!-- Shipping Dimensions -->
                <x-admin.card title="Shipping & Dimensions">
                    <div class="grid grid-cols-4 gap-4">
                        <x-admin.input type="number" step="0.01" name="weight" label="Weight (kg)" :value="old('weight', $product->weight)" />
                        <x-admin.input type="number" step="0.01" name="length" label="Length (cm)" :value="old('length', $product->length)" />
                        <x-admin.input type="number" step="0.01" name="width" label="Width (cm)" :value="old('width', $product->width)" />
                        <x-admin.input type="number" step="0.01" name="height" label="Height (cm)" :value="old('height', $product->height)" />
                    </div>
                </x-admin.card>
                
                <!-- SEO -->
                <x-admin.card title="Search Engine Optimization">
                    <div class="space-y-4">
                        <x-admin.input name="meta_title" label="Meta Title" :value="old('meta_title', $product->meta_title)" />
                        
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Meta Description</label>
                            <x-admin.textarea name="meta_description" rows="3">{{ old('meta_description', $product->meta_description) }}</x-admin.textarea>
                            @error('meta_description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>

            </div>

            <!-- Sidebar Content -->
            <div class="space-y-6">
                <!-- Status & Visibility -->
                <x-admin.card title="Status & Visibility">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Status</label>
                            <x-admin.select name="status">
                                <option value="draft" @selected(old('status', $product->status) == 'draft')>Draft</option>
                                <option value="published" @selected(old('status', $product->status) == 'published')>Published</option>
                                <option value="archived" @selected(old('status', $product->status) == 'archived')>Archived</option>
                            </x-admin.select>
                        </div>
                        
                        <div class="pt-2">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                <span class="text-sm font-bold text-slate-900">Feature this product</span>
                            </label>
                        </div>
                    </div>
                </x-admin.card>

                <!-- Organization / Categories -->
                <x-admin.card title="Organization">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">Categories</label>
                            <div class="max-h-64 overflow-y-auto w-full rounded-md border border-slate-300 bg-surface p-3 space-y-2">
                                @forelse($categories as $category)
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" 
                                            @checked(is_array(old('categories')) ? in_array($category->id, old('categories')) : $product->categories->contains($category->id)) 
                                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                        <span class="text-sm text-slate-900 flex-1">{{ $category->name }}</span>
                                    </label>
                                @empty
                                    <span class="text-xs text-slate-500">No categories available. <a href="{{ route('admin.catalog.categories.create') }}" class="text-slate-900 underline hover:text-slate-700">Create one</a></span>
                                @endforelse
                            </div>
                            @error('categories')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>

                <!-- Media -->
                <x-admin.card title="Primary Media">
                    <div class="space-y-4">
                        @if($product->primary_image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($product->primary_image) }}" alt="{{ $product->name }}" class="w-full h-auto rounded-md border border-slate-300 object-cover">
                            </div>
                        @endif
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">{{ $product->primary_image ? 'Replace Image' : 'Upload Image' }}</label>
                            <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-slate-100 file:text-slate-900 hover:file:bg-slate-200 transition cursor-pointer border border-slate-300 rounded-md p-1 bg-surface">
                            @error('featured_image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>

                <div class="flex justify-end space-x-3">
                    <x-admin.button href="{{ route('admin.catalog.products.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">Update Product</x-admin.button>
                </div>
            </div>
        </div>
    </form>
@endsection
