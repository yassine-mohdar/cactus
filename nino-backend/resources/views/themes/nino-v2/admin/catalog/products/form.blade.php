@php
    $isEditing = isset($product);
    $supportsTags = $supportsTags ?? true;
    $pricingMeta = $pricingMeta ?? ['base_currency' => config('finance.base_currency', 'MAD'), 'active_currencies' => [config('finance.base_currency', 'MAD')], 'multi_currency_enabled' => false];
    $variantMeta = $variantMeta ?? ['supports_variant_media' => true];
    $mediaDisk = config('media.default_disk', 'public');
    $merchandisingProducts = $merchandisingProducts ?? collect();
    $selectedCategories = old('categories');

    if (! is_array($selectedCategories)) {
        $selectedCategories = $isEditing ? $product->categories->pluck('id')->all() : [];
    }

    $selectedCategoryValues = array_map('strval', array_values($selectedCategories));
    $currentType = old('type', $isEditing ? $product->type : 'simple');
    $currentStatus = old('status', $isEditing ? $product->status : 'draft');
    $currentTags = $supportsTags
        ? old('tags', $isEditing ? $product->tags->pluck('name')->implode(', ') : '')
        : '';
    $selectedTagCount = collect(explode(',', (string) $currentTags))
        ->map(fn (string $tag) => trim($tag))
        ->filter()
        ->count();
    $descriptionPreviewSource = old('description', $isEditing ? $product->description : null);
    $descriptionPreviewHtml = trim((string) $descriptionPreviewSource) !== ''
        ? (new \App\Modules\Catalog\Models\Product(['description' => $descriptionPreviewSource]))->renderedDescriptionHtml()
        : null;
    $existingImages = $isEditing ? $product->images->sortBy('sort_order')->values() : collect();
    $featuredImageId = old('featured_image_id', $isEditing ? $product->featuredImage?->id : null);
    $removedImageIds = collect(old('remove_image_ids', []))->map(fn ($id) => (int) $id)->all();
    $selectedRelatedProducts = old('related_products', $isEditing ? $product->relatedProducts->pluck('id')->all() : []);
    $selectedUpsellProducts = old('upsell_products', $isEditing ? $product->upsellProducts->pluck('id')->all() : []);
    $selectedCrossSellProducts = old('cross_sell_products', $isEditing ? $product->crossSellProducts->pluck('id')->all() : []);
    $badgeLabels = old('badge_labels', $isEditing ? implode(', ', $product->badges()) : null);
    $optionsInput = old('options');

    if (! is_array($optionsInput)) {
        $optionsInput = $isEditing
            ? $product->options->map(fn ($option) => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])->values()->all()
            : [['name' => 'Size', 'values' => 'Small, Medium, Large']];
    }

    $variantsInput = old('variants');
    if (! is_array($variantsInput)) {
        $variantsInput = $isEditing
            ? $product->variants->map(fn ($variant) => [
                'sku' => $variant->sku,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'quantity' => $variant->quantity,
                'assignments' => $variant->optionValues
                    ->sortBy([
                        fn ($value) => (int) ($value->option?->position ?? 0),
                        fn ($value) => (string) ($value->option?->name ?? ''),
                    ])
                    ->map(fn ($value) => [
                        'option' => $value->option?->name,
                        'value' => $value->value,
                    ])
                    ->values()
                    ->all(),
                'image_path' => $variant->image_path,
            ])->values()->all()
            : [[
                'sku' => '',
                'price' => '',
                'sale_price' => '',
                'quantity' => 0,
                'assignments' => [['option' => 'Size', 'value' => 'Small']],
                'image_path' => null,
            ]];
    }
@endphp

<div
    x-data="{
        type: @js($currentType),
        selectedCategories: @js($selectedCategoryValues),
        options: @js($optionsInput),
        variants: @js($variantsInput),
        addOption() { this.options.push({ name: '', values: '' }); },
        removeOption(index) {
            if (this.options.length > 1) this.options.splice(index, 1);
        },
        addVariant() {
            this.variants.push({ sku: '', price: '', sale_price: '', quantity: 0, assignments: [{ option: '', value: '' }], image_path: null });
        },
        removeVariant(index) {
            if (this.variants.length > 1) this.variants.splice(index, 1);
        },
        addAssignment(index) {
            this.variants[index].assignments.push({ option: '', value: '' });
        },
        removeAssignment(variantIndex, assignmentIndex) {
            if (this.variants[variantIndex].assignments.length > 1) this.variants[variantIndex].assignments.splice(assignmentIndex, 1);
        }
    }"
    class="form-layout">
    <div class="form-main">
        <x-admin.card title="Core merchandising">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#245848]">Required</span>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Define the identity, internal identifiers, and storefront content that teams will use across catalog, finance, and support workflows.</p>

                <x-admin.input name="name" label="Product name" :value="old('name', $isEditing ? $product->name : null)" :error="$errors->first('name')" required />

                <div class="form-field-grid-3">
                    <x-admin.input name="sku" label="SKU" :value="old('sku', $isEditing ? $product->sku : null)" :error="$errors->first('sku')" />
                    <x-admin.input name="slug" label="Slug" :value="old('slug', $isEditing ? $product->slug : null)" :error="$errors->first('slug')" placeholder="auto-generated-from-name" />
                    <x-admin.input name="barcode" label="Barcode" :value="old('barcode', $isEditing ? $product->barcode : null)" :error="$errors->first('barcode')" />
                </div>

                <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3 text-sm leading-6 text-[#617169]">
                    Use `simple` for products with one sellable inventory record and `variable` when pricing or stock will be handled by variant combinations after the base record is created.
                </div>

                <x-admin.textarea name="short_description" label="Short description" rows="3" :error="$errors->first('short_description')">{{ old('short_description', $isEditing ? $product->short_description : null) }}</x-admin.textarea>

                <x-admin.textarea name="description" label="Full description" rows="8" :error="$errors->first('description')">{{ old('description', $isEditing ? $product->description : null) }}</x-admin.textarea>

                <div class="form-note">
                    Short description is used for compact merchandising cards and quick operational context. Full description supports structured long-form content and basic HTML such as paragraphs, lists, and emphasis tags.
                </div>
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

                    <div class="form-note">
                        Base currency for catalog pricing is <span class="font-semibold text-[#17302A]">{{ $pricingMeta['base_currency'] }}</span>. Sale price must be lower than the regular price. Leave it empty when the product is not currently discounted.
                    </div>

                    <div class="form-note">
                        Cost price is internal-only. Use it for margin tracking and purchasing decisions without exposing it to storefront customers.
                    </div>

                    @if($pricingMeta['multi_currency_enabled'])
                        <div class="form-note">
                            Multi-currency is enabled globally. Products are still authored in the base currency and can be converted downstream into {{ implode(', ', $pricingMeta['active_currencies']) }}.
                        </div>
                    @endif

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

        <x-admin.card title="Variant matrix" x-show="type === 'variable'" x-cloak>
            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-[#17302A]">Option / attribute foundation</h3>
                            <p class="mt-1 text-sm leading-6 text-[#617169]">Define the variant-driving attributes first. Values should be comma-separated within each option.</p>
                        </div>
                        <button type="button" @click="addOption()" class="inline-flex items-center gap-2 rounded-xl border border-[rgba(36,88,72,0.16)] bg-[#EAF3EE] px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#245848]">
                            <span class="material-symbols-outlined text-base">add</span>
                            Add option
                        </button>
                    </div>

                    <template x-for="(option, optionIndex) in options" :key="'option-'+optionIndex">
                        <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] p-4">
                            <div class="form-field-grid-2">
                                <div>
                                    <label class="form-label">Option name</label>
                                    <input x-model="option.name" :name="`options[${optionIndex}][name]`" type="text" class="form-input" placeholder="Size">
                                </div>
                                <div>
                                    <label class="form-label">Values</label>
                                    <input x-model="option.values" :name="`options[${optionIndex}][values]`" type="text" class="form-input" placeholder="Small, Medium, Large">
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" @click="removeOption(optionIndex)" class="text-xs font-semibold uppercase tracking-[0.16em] text-[#C45143]">Remove option</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="form-divider"></div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-[#17302A]">Variant SKUs, pricing, stock, and media</h3>
                            <p class="mt-1 text-sm leading-6 text-[#617169]">Create one row per sellable combination. Use the assignment pairs to link each variant back to the options defined above.</p>
                        </div>
                        <button type="button" @click="addVariant()" class="inline-flex items-center gap-2 rounded-xl border border-[rgba(36,88,72,0.16)] bg-[#EAF3EE] px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#245848]">
                            <span class="material-symbols-outlined text-base">add</span>
                            Add variant
                        </button>
                    </div>

                    <template x-for="(variant, variantIndex) in variants" :key="'variant-'+variantIndex">
                        <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-white p-4 shadow-[0_18px_35px_-28px_rgba(23,48,42,0.28)]">
                            <div class="space-y-4">
                                <div class="form-field-grid-4">
                                    <div>
                                        <label class="form-label">Variant SKU</label>
                                        <input x-model="variant.sku" :name="`variants[${variantIndex}][sku]`" type="text" class="form-input" placeholder="HOODIE-S-BLK">
                                    </div>
                                    <div>
                                        <label class="form-label">Regular price</label>
                                        <input x-model="variant.price" :name="`variants[${variantIndex}][price]`" type="number" step="0.01" min="0" class="form-input" placeholder="199.90">
                                    </div>
                                    <div>
                                        <label class="form-label">Sale price</label>
                                        <input x-model="variant.sale_price" :name="`variants[${variantIndex}][sale_price]`" type="number" step="0.01" min="0" class="form-input" placeholder="179.90">
                                    </div>
                                    <div>
                                        <label class="form-label">Stock quantity</label>
                                        <input x-model="variant.quantity" :name="`variants[${variantIndex}][quantity]`" type="number" min="0" class="form-input" placeholder="0">
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-[#17302A]">Assignments</p>
                                        <button type="button" @click="addAssignment(variantIndex)" class="text-xs font-semibold uppercase tracking-[0.16em] text-[#245848]">Add assignment</button>
                                    </div>

                                    <template x-for="(assignment, assignmentIndex) in variant.assignments" :key="'assignment-'+variantIndex+'-'+assignmentIndex">
                                        <div class="form-field-grid-2">
                                            <div>
                                                <label class="form-label">Option</label>
                                                <input x-model="assignment.option" :name="`variants[${variantIndex}][assignments][${assignmentIndex}][option]`" type="text" class="form-input" placeholder="Size">
                                            </div>
                                            <div class="flex items-end gap-3">
                                                <div class="flex-1">
                                                    <label class="form-label">Value</label>
                                                    <input x-model="assignment.value" :name="`variants[${variantIndex}][assignments][${assignmentIndex}][value]`" type="text" class="form-input" placeholder="Medium">
                                                </div>
                                                <button type="button" @click="removeAssignment(variantIndex, assignmentIndex)" class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#C45143]">Remove</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="space-y-3">
                                    <label class="form-label">Variant image</label>
                                    <input :name="`variant_images[${variantIndex}]`" type="file" accept="image/*" class="form-upload">
                                    @if($isEditing)
                                        <template x-if="variant.image_path">
                                            <p class="text-xs text-[#617169]">A stored variant image already exists and will be replaced only if a new file is uploaded.</p>
                                        </template>
                                    @endif
                                </div>

                                <div class="flex justify-end">
                                    <button type="button" @click="removeVariant(variantIndex)" class="text-xs font-semibold uppercase tracking-[0.16em] text-[#C45143]">Remove variant</button>
                                </div>
                            </div>
                        </div>
                    </template>
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

                <div class="form-note">
                    Use zeros only when the product truly has no measurable shipping profile. Positive values keep carrier estimates and branch handling accurate.
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Search visibility">
            <div class="space-y-5">
                <p class="form-copy">Override search and social metadata only when the product needs a tailored search snippet, canonical target, or social preview beyond the storefront content.</p>

                <x-admin.input name="meta_title" label="Meta title" :value="old('meta_title', $isEditing ? $product->meta_title : null)" :error="$errors->first('meta_title')" />

                <x-admin.textarea name="meta_description" label="Meta description" rows="3" :error="$errors->first('meta_description')">{{ old('meta_description', $isEditing ? $product->meta_description : null) }}</x-admin.textarea>

                <div class="form-divider"></div>

                <x-admin.input
                    name="canonical_url"
                    label="Canonical URL"
                    :value="old('canonical_url', $isEditing ? $product->canonical_url : null)"
                    :error="$errors->first('canonical_url')"
                    placeholder="https://www.ninoworld.com/products/example-product" />

                <div class="form-note">
                    Leave canonical URL empty to fall back to the default storefront path for this product slug.
                </div>

                <div class="form-divider"></div>

                <div class="form-field-grid-2">
                    <x-admin.input
                        name="og_title"
                        label="OG title"
                        :value="old('og_title', $isEditing ? $product->og_title : null)"
                        :error="$errors->first('og_title')"
                        placeholder="Social card title" />

                    <x-admin.input
                        name="og_image"
                        label="OG image URL"
                        :value="old('og_image', $isEditing ? $product->og_image : null)"
                        :error="$errors->first('og_image')"
                        placeholder="https://cdn.ninoworld.com/catalog/example-og.jpg" />
                </div>

                <x-admin.textarea name="og_description" label="OG description" rows="3" :error="$errors->first('og_description')">{{ old('og_description', $isEditing ? $product->og_description : null) }}</x-admin.textarea>

                <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3 text-sm leading-6 text-[#617169]">
                    If OG fields are empty, the product will fall back to its SEO title, SEO description, and featured image for social sharing cards.
                </div>

                <label class="surface-selection flex items-start gap-3">
                    <input type="hidden" name="noindex" value="0">
                    <input type="checkbox" name="noindex" value="1" @checked(old('noindex', $isEditing ? $product->noindex : false)) class="mt-1 size-4 rounded border-[rgba(145,133,109,0.34)] text-[#245848] focus:ring-[#245848]">
                    <span class="block">
                        <span class="block text-sm font-semibold text-[#17302A]">Noindex this product</span>
                        <span class="mt-1 block text-sm leading-6 text-[#617169]">Use this when the product should remain operationally available but excluded from search indexing.</span>
                    </span>
                </label>
            </div>
        </x-admin.card>

        <x-admin.card title="Merchandising links">
            <div class="space-y-6">
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-[#17302A]">Related products</h3>
                        <p class="mt-1 text-sm leading-6 text-[#617169]">Suggest adjacent items that help customers continue browsing similar products.</p>
                    </div>
                    <div class="max-h-52 space-y-3 overflow-y-auto pr-1">
                        @forelse($merchandisingProducts as $candidate)
                            <label class="block cursor-pointer">
                                <input type="checkbox" name="related_products[]" value="{{ $candidate->id }}" class="peer sr-only" @checked(in_array($candidate->id, $selectedRelatedProducts, true))>
                                <div class="surface-selection-active">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-[#17302A]">{{ $candidate->name }}</p>
                                            <p class="mt-1 font-mono text-[10px] tracking-[0.18em] text-[#617169]">{{ $candidate->sku ?: 'NO-SKU' }}</p>
                                        </div>
                                        <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">check_circle</span>
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="form-note">Create more products to build related merchandising sets.</div>
                        @endforelse
                    </div>
                </div>

                <div class="form-divider"></div>

                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-[#17302A]">Upsells</h3>
                        <p class="mt-1 text-sm leading-6 text-[#617169]">Highlight higher-value alternatives or premium bundles during the buying journey.</p>
                    </div>
                    <div class="max-h-52 space-y-3 overflow-y-auto pr-1">
                        @forelse($merchandisingProducts as $candidate)
                            <label class="block cursor-pointer">
                                <input type="checkbox" name="upsell_products[]" value="{{ $candidate->id }}" class="peer sr-only" @checked(in_array($candidate->id, $selectedUpsellProducts, true))>
                                <div class="surface-selection-active">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-[#17302A]">{{ $candidate->name }}</p>
                                            <p class="mt-1 font-mono text-[10px] tracking-[0.18em] text-[#617169]">{{ $candidate->sku ?: 'NO-SKU' }}</p>
                                        </div>
                                        <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">check_circle</span>
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="form-note">Create more products to build upsell sets.</div>
                        @endforelse
                    </div>
                </div>

                <div class="form-divider"></div>

                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-[#17302A]">Cross-sells</h3>
                        <p class="mt-1 text-sm leading-6 text-[#617169]">Attach companion products that belong in cart, checkout, or post-add-to-cart moments.</p>
                    </div>
                    <div class="max-h-52 space-y-3 overflow-y-auto pr-1">
                        @forelse($merchandisingProducts as $candidate)
                            <label class="block cursor-pointer">
                                <input type="checkbox" name="cross_sell_products[]" value="{{ $candidate->id }}" class="peer sr-only" @checked(in_array($candidate->id, $selectedCrossSellProducts, true))>
                                <div class="surface-selection-active">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-[#17302A]">{{ $candidate->name }}</p>
                                            <p class="mt-1 font-mono text-[10px] tracking-[0.18em] text-[#617169]">{{ $candidate->sku ?: 'NO-SKU' }}</p>
                                        </div>
                                        <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">check_circle</span>
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="form-note">Create more products to build cross-sell sets.</div>
                        @endforelse
                    </div>
                </div>

                <div class="form-divider"></div>

                <div class="space-y-3">
                    <x-admin.input
                        name="badge_labels"
                        label="Badge labels"
                        :value="$badgeLabels"
                        :error="$errors->first('badge_labels')"
                        placeholder="Limited drop, Bestseller, Gift ready" />
                    <div class="form-note">
                        Enter comma-separated badge labels for lightweight merchandising callouts.
                    </div>
                </div>
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

                <div class="form-note">
                    Draft keeps the product internal, published makes it storefront-ready, and archived retires it from active merchandising while preserving its history.
                </div>

                <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3 text-sm leading-6 text-[#617169]">
                    <div><span class="font-semibold text-[#17302A]">SEO title:</span> {{ old('meta_title', $isEditing ? $product->seoTitle() : null) ?: 'Uses product name by default' }}</div>
                    <div class="mt-2"><span class="font-semibold text-[#17302A]">Canonical:</span> {{ old('canonical_url', $isEditing ? $product->canonicalUrl() : null) ?: 'Will follow the default storefront product URL' }}</div>
                    <div class="mt-2"><span class="font-semibold text-[#17302A]">Social card:</span> {{ old('og_title', $isEditing ? $product->ogTitle() : null) ?: 'Falls back to SEO title' }}</div>
                </div>

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

        @if($supportsTags)
            <x-admin.card title="Tags">
                <x-slot:header>
                    <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">
                        {{ $selectedTagCount ? $selectedTagCount . ' tag' . ($selectedTagCount > 1 ? 's' : '') : 'Optional' }}
                    </span>
                </x-slot:header>

                <div class="space-y-4">
                    <p class="form-copy">Use lightweight merchandising tags for search, internal pick lists, and quick operational segmentation.</p>

                    <x-admin.input
                        name="tags"
                        label="Tags"
                        :value="$currentTags"
                        :error="$errors->first('tags')"
                        placeholder="new arrival, bestseller, giftable" />

                    <div class="form-note">
                        Enter comma-separated tags. Existing tags are reused automatically, and duplicates are merged by normalized slug.
                    </div>
                </div>
            </x-admin.card>
        @endif

        <x-admin.card title="Product media">
            <div class="space-y-5">
                <p class="form-copy">Manage the lead image, supporting gallery, editorial alt text, and display order used across merchandising cards and catalog detail pages.</p>

                @if($isEditing && $product->primary_image)
                    <img src="{{ Storage::disk($mediaDisk)->url($product->primary_image) }}" alt="{{ $product->name }}" class="h-48 w-full rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] object-cover shadow-[0_18px_38px_-28px_rgba(23,48,42,0.45)]">
                @endif

                <div class="space-y-3">
                    <label class="block text-sm font-semibold text-[#17302A]">Featured image upload</label>
                    <input type="file" name="featured_image" accept="image/*" class="form-upload">
                    <x-admin.input
                        name="featured_image_alt"
                        label="Featured image alt text"
                        :value="old('featured_image_alt', $isEditing ? $product->featuredImage?->alt_text : null)"
                        :error="$errors->first('featured_image_alt')"
                        placeholder="Front-facing plush product shot" />
                    <div class="form-note">
                        Uploading a new featured image moves it to the front of the gallery and replaces the current lead visual.
                    </div>
                </div>

                @error('featured_image')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="form-divider"></div>

                <div class="space-y-3">
                    <label class="block text-sm font-semibold text-[#17302A]">Gallery uploads</label>
                    <input type="file" name="gallery_images[]" accept="image/*" multiple class="form-upload">
                    <div class="form-note">
                        Add supporting visuals in bulk. New gallery images inherit a safe default alt text and can be refined immediately below after the product is saved.
                    </div>
                    @error('gallery_images')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('gallery_images.*')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if($isEditing && $existingImages->isNotEmpty())
                    <div class="form-divider"></div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-semibold text-[#17302A]">Existing gallery</h3>
                            <p class="mt-1 text-sm leading-6 text-[#617169]">Set the featured image, refine alt text, and tune display order without leaving the product form.</p>
                        </div>

                        <div class="space-y-4">
                            @foreach($existingImages as $image)
                                @php
                                    $mediaMeta = old('media_meta.' . $image->id, [
                                        'alt_text' => $image->alt_text,
                                        'sort_order' => $image->sort_order,
                                    ]);
                                    $isMarkedForRemoval = in_array($image->id, $removedImageIds, true);
                                    $isSelectedFeatured = (string) $featuredImageId === (string) $image->id;
                                @endphp
                                <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] p-4 {{ $isMarkedForRemoval ? 'opacity-60' : '' }}">
                                    <div class="flex flex-col gap-4 md:flex-row">
                                        <img
                                            src="{{ Storage::disk($mediaDisk)->url($image->path) }}"
                                            alt="{{ $image->altTextLabel() }}"
                                            class="h-28 w-full rounded-2xl border border-[rgba(145,133,109,0.18)] object-cover md:w-36">

                                        <div class="flex-1 space-y-4">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <label class="inline-flex items-center gap-2 text-sm font-medium text-[#17302A]">
                                                    <input type="radio" name="featured_image_id" value="{{ $image->id }}" @checked($isSelectedFeatured) class="size-4 border-[rgba(145,133,109,0.34)] text-[#245848] focus:ring-[#245848]">
                                                    Set as featured
                                                </label>
                                                <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#6E7D75]">
                                                    Order {{ old('media_meta.' . $image->id . '.sort_order', $image->sort_order) }}
                                                </span>
                                            </div>

                                            <div class="form-field-grid-2">
                                                <x-admin.input
                                                    :name="'media_meta[' . $image->id . '][alt_text]'"
                                                    label="Alt text"
                                                    :value="$mediaMeta['alt_text'] ?? null"
                                                    :error="$errors->first('media_meta.' . $image->id . '.alt_text')"
                                                    placeholder="Side view of the product" />
                                                <x-admin.input
                                                    type="number"
                                                    min="0"
                                                    :name="'media_meta[' . $image->id . '][sort_order]'"
                                                    label="Display order"
                                                    :value="$mediaMeta['sort_order'] ?? $image->sort_order"
                                                    :error="$errors->first('media_meta.' . $image->id . '.sort_order')" />
                                            </div>

                                            <label class="inline-flex items-center gap-2 text-sm text-[#617169]">
                                                <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}" @checked($isMarkedForRemoval) class="size-4 rounded border-[rgba(145,133,109,0.34)] text-[#C45143] focus:ring-[#C45143]">
                                                Remove this image on save
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                    <span class="meta-label">Featured</span>
                    <span class="meta-value">{{ old('is_featured', $isEditing ? $product->is_featured : false) ? 'Yes' : 'No' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">URL handle</span>
                    <span class="meta-value">{{ old('slug', $isEditing ? $product->slug : null) ?: 'Generated from name' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Inventory model</span>
                    <span class="meta-value">{{ $currentType === 'variable' ? 'Variant managed' : 'Direct quantity' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Pricing</span>
                    <span class="meta-value">
                        @if(old('sale_price', $isEditing ? $product->sale_price : null))
                            Sale active · {{ $pricingMeta['base_currency'] }}
                        @elseif(old('price', $isEditing ? $product->price : null))
                            Base price set · {{ $pricingMeta['base_currency'] }}
                        @else
                            Price not set
                        @endif
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Internal cost</span>
                    <span class="meta-value">
                        @if(old('cost_price', $isEditing ? $product->cost_price : null))
                            Captured
                        @else
                            Not set
                        @endif
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Shipping profile</span>
                    <span class="meta-value">
                        @if(old('weight', $isEditing ? $product->weight : null) || old('length', $isEditing ? $product->length : null) || old('width', $isEditing ? $product->width : null) || old('height', $isEditing ? $product->height : null))
                            Dimensions entered
                        @else
                            Not set
                        @endif
                    </span>
                </div>
                @if($supportsTags)
                    <div class="meta-row">
                        <span class="meta-label">Tags</span>
                        <span class="meta-value">{{ $selectedTagCount ? $selectedTagCount . ' applied' : 'No tags yet' }}</span>
                    </div>
                @endif
                <div class="meta-row">
                    <span class="meta-label">Media</span>
                    <span class="meta-value">
                        @if($isEditing && $existingImages->isNotEmpty())
                            {{ $existingImages->count() }} stored
                        @elseif(old('featured_image'))
                            Upload queued
                        @else
                            No media yet
                        @endif
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Storage</span>
                    <span class="meta-value">{{ $mediaDisk }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Merch links</span>
                    <span class="meta-value">
                        {{ count($selectedRelatedProducts) + count($selectedUpsellProducts) + count($selectedCrossSellProducts) }} linked
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Badges</span>
                    <span class="meta-value">{{ $badgeLabels ? count(array_filter(array_map('trim', explode(',', $badgeLabels)))) . ' defined' : 'No badges yet' }}</span>
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

        <x-admin.card title="Content Preview">
            <div class="space-y-4">
                <p class="form-copy">Quick editorial sanity check before saving the product narrative into the catalog.</p>

                @if($short = old('short_description', $isEditing ? $product->short_description : null))
                    <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">Short description</p>
                        <p class="mt-2 text-sm leading-6 text-[#17302A]">{{ $short }}</p>
                    </div>
                @endif

                @if($descriptionPreviewHtml)
                    <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">Full description preview</p>
                        <div class="prose prose-sm mt-3 max-w-none text-[#17302A]">
                            {!! $descriptionPreviewHtml !!}
                        </div>
                    </div>
                @else
                    <div class="form-note">
                        Full description preview will appear here once content is added.
                    </div>
                @endif
            </div>
        </x-admin.card>

        @if($isEditing && isset($recentAuditLogs) && $recentAuditLogs->isNotEmpty())
            <x-admin.card title="Recent audit activity">
                <div class="space-y-4">
                    @foreach($recentAuditLogs as $auditLog)
                        <div class="rounded-2xl border border-[rgba(145,133,109,0.14)] bg-[#FBFAF7] px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-[#17302A]">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $auditLog->action)) }}</p>
                                    <p class="mt-1 text-xs text-[#617169]">{{ $auditLog->actor_name ?: 'System' }} · {{ optional($auditLog->created_at)->diffForHumans() }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.14)] bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">{{ strtoupper($auditLog->context['method'] ?? 'SYS') }}</span>
                            </div>
                            @if($auditLog->notes)
                                <p class="mt-2 text-sm leading-6 text-[#617169]">{{ $auditLog->notes }}</p>
                            @endif
                            @if(($auditLog->new_values ?? []) !== [])
                                <div class="mt-3 font-mono text-[10px] tracking-[0.14em] text-[#617169]">
                                    {{ collect(array_keys($auditLog->new_values))->take(4)->implode(' · ') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        @endif
    </div>
</div>
