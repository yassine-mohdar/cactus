@php
    $isEditing = isset($product);
    $selectedCategories = old('categories');

    if (! is_array($selectedCategories)) {
        $selectedCategories = $isEditing ? $product->categories->pluck('id')->all() : [];
    }

    $selectedCategoryValues = array_map('strval', array_values($selectedCategories));
    $currentType = old('type', $isEditing ? $product->type : 'simple');
    $currentStatus = old('status', $isEditing ? $product->status : 'draft');
@endphp

<div x-data="{ type: @js($currentType), selectedCategories: @js($selectedCategoryValues) }" class="form-layout">
    <div class="form-main">
        <x-admin.card title="Core merchandising">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#245848]">Required</span>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Define the identity, internal identifiers, and storefront content that teams will use across catalog, finance, and support workflows.</p>

                <x-admin.input name="name" label="Product name" :value="old('name', $isEditing ? $product->name : null)" :error="$errors->first('name')" required />

                <div class="form-field-grid-2">
                    <x-admin.input name="sku" label="SKU" :value="old('sku', $isEditing ? $product->sku : null)" :error="$errors->first('sku')" />
                    <x-admin.input name="barcode" label="Barcode" :value="old('barcode', $isEditing ? $product->barcode : null)" :error="$errors->first('barcode')" />
                </div>

                <x-admin.textarea name="short_description" label="Short description" rows="3" :error="$errors->first('short_description')">{{ old('short_description', $isEditing ? $product->short_description : null) }}</x-admin.textarea>

                <x-admin.textarea name="description" label="Full description" rows="8" :error="$errors->first('description')">{{ old('description', $isEditing ? $product->description : null) }}</x-admin.textarea>
            </div>
        </x-admin.card>

        <x-admin.card title="Pricing and inventory">
            <div class="space-y-5">
                <p class="form-copy">Choose whether stock is managed directly on the base product or delegated to variants, then define the commercial pricing model.</p>

                <x-admin.select name="type" label="Product type" x-model="type" :error="$errors->first('type')">
                    <option value="simple">Simple product</option>
                    <option value="variable">Variable product</option>
                </x-admin.select>

                <div x-show="type === 'simple'" x-transition.opacity.duration.200ms class="space-y-5">
                    <div class="form-field-grid-3">
                        <x-admin.input type="number" step="0.01" name="price" label="Regular price" :value="old('price', $isEditing ? $product->price : null)" :error="$errors->first('price')" />
                        <x-admin.input type="number" step="0.01" name="sale_price" label="Sale price" :value="old('sale_price', $isEditing ? $product->sale_price : null)" :error="$errors->first('sale_price')" />
                        <x-admin.input type="number" step="0.01" name="cost_price" label="Cost price" :value="old('cost_price', $isEditing ? $product->cost_price : null)" :error="$errors->first('cost_price')" />
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-field-grid-2">
                        <x-admin.input type="number" name="quantity" label="Available quantity" :value="old('quantity', $isEditing ? $product->quantity : 0)" :error="$errors->first('quantity')" />
                    </div>
                </div>

                <div x-show="type === 'variable'" x-cloak class="form-note">
                    Pricing and inventory will be managed at the variant level after the base product is created. Keep the parent product focused on catalog structure and merchandising.
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Shipping and fulfillment">
            <div class="space-y-5">
                <p class="form-copy">Provide dimensions and handling information so shipping, warehousing, and carrier estimates remain accurate.</p>

                <div class="form-field-grid-4">
                    <x-admin.input type="number" step="0.01" name="weight" label="Weight (kg)" :value="old('weight', $isEditing ? $product->weight : null)" :error="$errors->first('weight')" />
                    <x-admin.input type="number" step="0.01" name="length" label="Length (cm)" :value="old('length', $isEditing ? $product->length : null)" :error="$errors->first('length')" />
                    <x-admin.input type="number" step="0.01" name="width" label="Width (cm)" :value="old('width', $isEditing ? $product->width : null)" :error="$errors->first('width')" />
                    <x-admin.input type="number" step="0.01" name="height" label="Height (cm)" :value="old('height', $isEditing ? $product->height : null)" :error="$errors->first('height')" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Search visibility">
            <div class="space-y-5">
                <p class="form-copy">Override search-engine metadata only when the product needs a tailored title or description beyond the storefront content.</p>

                <x-admin.input name="meta_title" label="Meta title" :value="old('meta_title', $isEditing ? $product->meta_title : null)" :error="$errors->first('meta_title')" />

                <x-admin.textarea name="meta_description" label="Meta description" rows="3" :error="$errors->first('meta_description')">{{ old('meta_description', $isEditing ? $product->meta_description : null) }}</x-admin.textarea>
            </div>
        </x-admin.card>
    </div>

    <div class="form-sidebar">
        <x-admin.card title="Publishing">
            <div class="space-y-5">
                <p class="form-copy">Control how the product appears internally and when it becomes eligible for storefront placement.</p>

                <x-admin.select name="status" label="Status" :error="$errors->first('status')">
                    <option value="draft" @selected($currentStatus === 'draft')>Draft</option>
                    <option value="published" @selected($currentStatus === 'published')>Published</option>
                    <option value="archived" @selected($currentStatus === 'archived')>Archived</option>
                </x-admin.select>

                <label class="surface-selection flex items-start gap-3">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $isEditing ? $product->is_featured : false)) class="mt-1 size-4 rounded border-[rgba(145,133,109,0.34)] text-[#245848] focus:ring-[#245848]">
                    <span class="block">
                        <span class="block text-sm font-semibold text-[#17302A]">Feature this product</span>
                        <span class="mt-1 block text-sm leading-6 text-[#617169]">Promote it in curated collections and internal merchandising shortlists.</span>
                    </span>
                </label>
            </div>
        </x-admin.card>

        <x-admin.card title="Category placement">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]" x-text="selectedCategories.length ? selectedCategories.length + ' selected' : 'Optional'"></span>
            </x-slot:header>

            <div class="space-y-4">
                <p class="form-copy">Place the product into the right catalog collections so navigation, reporting, and promotions stay aligned.</p>

                <div class="max-h-[26rem] space-y-3 overflow-y-auto pr-1">
                    @forelse($categories as $category)
                        <label class="block cursor-pointer">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" x-model="selectedCategories" class="peer sr-only" @checked(in_array($category->id, $selectedCategories, true))>
                            <div class="surface-selection-active">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#17302A]">{{ $category->name }}</p>
                                        <p class="mt-1 text-xs leading-5 text-[#617169]">Include this product in the {{ \Illuminate\Support\Str::lower($category->name) }} collection.</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">check_circle</span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="form-note">
                            No categories exist yet. Create one first to organize the new product cleanly.
                            <a href="{{ route('admin.catalog.categories.create') }}" class="ml-1 font-semibold text-[#245848] underline underline-offset-4 hover:text-[#173B31]">Create category</a>
                        </div>
                    @endforelse
                </div>

                @error('categories')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-admin.card>

        <x-admin.card title="Primary media">
            <div class="space-y-4">
                <p class="form-copy">Upload a clean lead image for merchandising cards, cart previews, and support lookups.</p>

                @if($isEditing && $product->primary_image)
                    <img src="{{ Storage::url($product->primary_image) }}" alt="{{ $product->name }}" class="h-48 w-full rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] object-cover shadow-[0_18px_38px_-28px_rgba(23,48,42,0.45)]">
                @endif

                <input type="file" name="featured_image" accept="image/*" class="form-upload">

                @error('featured_image')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-admin.card>

        <x-admin.card title="Ready to save">
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">Mode</span>
                    <span class="meta-value">{{ $isEditing ? 'Updating existing product' : 'Creating new product' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Catalog status</span>
                    <span class="meta-value">{{ \Illuminate\Support\Str::headline($currentStatus) }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Inventory model</span>
                    <span class="meta-value">{{ $currentType === 'variable' ? 'Variant managed' : 'Direct quantity' }}</span>
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-admin.button href="{{ route('admin.catalog.products.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">
                        {{ $isEditing ? 'Update Product' : 'Save Product' }}
                    </x-admin.button>
                </div>
            </x-slot:footer>
        </x-admin.card>
    </div>
</div>
