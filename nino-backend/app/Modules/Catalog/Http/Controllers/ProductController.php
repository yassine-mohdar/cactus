<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductTag;
use App\Modules\Catalog\Services\ProductMediaService;
use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Finance\Services\FinanceSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly FinanceSettingsService $financeSettings,
        private readonly ProductMediaService $productMedia,
        private readonly ProductVariantService $productVariants,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $supportsTags = Product::supportsTags();
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => (string) $request->string('status'),
            'category' => $request->filled('category') ? (int) $request->integer('category') : null,
            'type' => (string) $request->string('type'),
            'stock' => (string) $request->string('stock'),
        ];

        $productsQuery = Product::with(['categories', 'featuredImage'])->withCount([
            'images',
            'variants',
            'relatedProducts',
            'upsellProducts',
            'crossSellProducts',
        ])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = $filters['search'];

                $query->where(function ($searchQuery) use ($term): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%')
                        ->orWhere('slug', 'like', '%'.$term.'%')
                        ->orWhere('barcode', 'like', '%'.$term.'%');
                });
            })
            ->when(in_array($filters['status'], Product::STATUSES, true), fn ($query) => $query->where('status', $filters['status']))
            ->when(in_array($filters['type'], Product::TYPES, true), fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['category'] !== null, fn ($query) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereKey($filters['category'])))
            ->when($filters['stock'] === 'low', fn ($query) => $query->where('quantity', '<=', 5))
            ->when($filters['stock'] === 'out', fn ($query) => $query->where('quantity', '<=', 0))
            ->when($filters['stock'] === 'in', fn ($query) => $query->where('quantity', '>', 5))
            ->orderBy('id', 'desc');

        if ($supportsTags) {
            $productsQuery->with('tags');
        }

        $products = $productsQuery->paginate(25)->withQueryString();
        $summaryBaseQuery = Product::query();
        $categories = Category::query()->ordered()->get(['id', 'name']);

        $summary = [
            'total_products' => (clone $summaryBaseQuery)->count(),
            'published' => (clone $summaryBaseQuery)->published()->count(),
            'drafts' => (clone $summaryBaseQuery)->draft()->count(),
            'archived' => (clone $summaryBaseQuery)->archived()->count(),
            'featured' => (clone $summaryBaseQuery)->featured()->count(),
            'low_stock' => (clone $summaryBaseQuery)->where('quantity', '<=', 5)->count(),
        ];

        $pricingMeta = $this->pricingMeta();

        return view('admin.catalog.products.index', compact('products', 'summary', 'supportsTags', 'pricingMeta', 'filters', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::orderBy('name')->get();
        $merchandisingProducts = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'status']);
        $supportsTags = Product::supportsTags();
        $pricingMeta = $this->pricingMeta();
        $variantMeta = $this->variantMeta();

        return view('admin.catalog.products.create', compact('categories', 'merchandisingProducts', 'supportsTags', 'pricingMeta', 'variantMeta'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(Product::TYPES)],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'sku' => 'nullable|string|unique:products,sku',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'barcode' => 'nullable|string|unique:products,barcode',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url|max:2048',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:2048',
            'noindex' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:4096',
            'options' => 'nullable|array',
            'options.*.name' => 'nullable|string|max:80',
            'options.*.values' => 'nullable|string|max:500',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.assignments' => 'nullable|array',
            'variants.*.assignments.*.option' => 'nullable|string|max:80',
            'variants.*.assignments.*.value' => 'nullable|string|max:120',
            'variant_images' => 'nullable|array',
            'variant_images.*' => 'nullable|image|max:4096',
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer|exists:products,id',
            'upsell_products' => 'nullable|array',
            'upsell_products.*' => 'integer|exists:products,id',
            'cross_sell_products' => 'nullable|array',
            'cross_sell_products.*' => 'integer|exists:products,id',
            'badge_labels' => 'nullable|string|max:500',
        ]);

        $this->validateVariantPayload($request, null);

        $product = new Product($validated);
        // Explicit boolean cast check
        $product->is_featured = $request->boolean('is_featured', false);
        $product->noindex = $request->boolean('noindex', false);
        $product->save();
        
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        $this->syncProductTags($product, $request->input('tags'));

        $this->productMedia->sync($product, [
            'featured_image' => $request->file('featured_image'),
            'featured_image_alt' => $request->input('featured_image_alt'),
            'gallery_images' => $request->file('gallery_images', []),
        ]);
        $this->productVariants->sync(
            $product,
            $request->input('options', []),
            $request->input('variants', []),
            $request->file('variant_images', []),
        );
        $this->syncMerchandising($product, $request);

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->loadMissing([
            'categories',
            'images',
            'featuredImage',
            'options.values',
            'variants.optionValues.option',
            'relatedProducts',
            'upsellProducts',
            'crossSellProducts',
        ]);

        $supportsTags = Product::supportsTags();

        if ($supportsTags) {
            $product->loadMissing('tags');
        }

        $categories = Category::orderBy('name')->get();
        $merchandisingProducts = Product::query()
            ->whereKeyNot($product->getKey())
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'status']);
        $pricingMeta = $this->pricingMeta();
        $variantMeta = $this->variantMeta();
        $recentAuditLogs = AuditLog::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $product->id)
            ->latest('id')
            ->limit(6)
            ->get();

        return view('admin.catalog.products.edit', compact('product', 'categories', 'merchandisingProducts', 'supportsTags', 'pricingMeta', 'variantMeta', 'recentAuditLogs'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $pricingBefore = $this->pricingSnapshot($product);
        $stateBefore = $this->stateSnapshot($product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(Product::TYPES)],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url|max:2048',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:2048',
            'noindex' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'featured_image_id' => [
                'nullable',
                'integer',
                Rule::exists(ProductImage::class, 'id')->where(fn ($query) => $query->where('product_id', $product->id)),
            ],
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:4096',
            'media_meta' => 'nullable|array',
            'media_meta.*.alt_text' => 'nullable|string|max:255',
            'media_meta.*.sort_order' => 'nullable|integer|min:0|max:999999',
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => [
                'integer',
                Rule::exists(ProductImage::class, 'id')->where(fn ($query) => $query->where('product_id', $product->id)),
            ],
            'options' => 'nullable|array',
            'options.*.name' => 'nullable|string|max:80',
            'options.*.values' => 'nullable|string|max:500',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.assignments' => 'nullable|array',
            'variants.*.assignments.*.option' => 'nullable|string|max:80',
            'variants.*.assignments.*.value' => 'nullable|string|max:120',
            'variant_images' => 'nullable|array',
            'variant_images.*' => 'nullable|image|max:4096',
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer|exists:products,id',
            'upsell_products' => 'nullable|array',
            'upsell_products.*' => 'integer|exists:products,id',
            'cross_sell_products' => 'nullable|array',
            'cross_sell_products.*' => 'integer|exists:products,id',
            'badge_labels' => 'nullable|string|max:500',
        ]);

        $this->validateVariantPayload($request, $product);

        $product->fill($validated);
        $product->is_featured = $request->boolean('is_featured', false);
        $product->noindex = $request->boolean('noindex', false);
        if (!$request->has('is_featured')) {
            $product->is_featured = false;
        }

        $product->save();
        
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        } else {
            $product->categories()->detach();
        }

        $this->syncProductTags($product, $request->input('tags'));

        $this->productMedia->sync($product, [
            'featured_image' => $request->file('featured_image'),
            'featured_image_alt' => $request->input('featured_image_alt'),
            'featured_image_id' => $request->input('featured_image_id'),
            'gallery_images' => $request->file('gallery_images', []),
            'media_meta' => $request->input('media_meta', []),
            'remove_image_ids' => $request->input('remove_image_ids', []),
        ]);
        $this->productVariants->sync(
            $product,
            $request->input('options', []),
            $request->input('variants', []),
            $request->file('variant_images', []),
        );
        $this->syncMerchandising($product, $request);

        $product->refresh();
        $pricingAfter = $this->pricingSnapshot($product);
        $stateAfter = $this->stateSnapshot($product);

        if ($pricingBefore !== $pricingAfter) {
            $this->audit->log(
                action: 'catalog.product.price_changed',
                target: $product,
                oldValues: $pricingBefore,
                newValues: $pricingAfter,
                notes: 'Product pricing updated',
                context: [
                    'module' => 'catalog',
                    'source' => 'product_controller',
                ],
            );
        }

        if ($stateBefore !== $stateAfter) {
            $this->audit->log(
                action: 'catalog.product.updated',
                target: $product,
                oldValues: $stateBefore,
                newValues: $stateAfter,
                notes: 'Product details updated',
                context: [
                    'module' => 'catalog',
                    'source' => 'product_controller',
                ],
            );
        }

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $snapshot = $this->stateSnapshot($product);
        $pricing = $this->pricingSnapshot($product);

        $this->productVariants->purge($product);
        $this->productMedia->purge($product);
        $this->syncProductTags($product, null);
        $product->categories()->detach();
        $product->delete();

        $this->audit->log(
            action: 'catalog.product.deleted',
            target: $product,
            oldValues: array_merge($snapshot, $pricing),
            newValues: [],
            notes: 'Product deleted from catalog',
            context: [
                'module' => 'catalog',
                'source' => 'product_controller',
            ],
        );

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product deleted successfully.');
    }

    public function updateStatus(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Product::STATUSES)],
        ]);

        $oldStatus = $product->status;
        $newStatus = $validated['status'];

        if ($oldStatus !== $newStatus) {
            $product->status = $newStatus;
            $product->save();

            $this->audit->log(
                action: 'catalog.product.status_changed',
                target: $product,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                notes: 'Product publishing status changed',
                context: [
                    'module' => 'catalog',
                    'source' => 'product_controller',
                    'workflow' => 'single_status_transition',
                ],
            );
        }

        return redirect()->route('admin.catalog.products.index')->with('success', 'Product status updated successfully.');
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['publish', 'draft', 'archive', 'delete'])],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::query()->whereIn('id', $validated['product_ids'])->get();

        if ($products->isEmpty()) {
            return redirect()->route('admin.catalog.products.index')->with('error', 'No products were selected.');
        }

        $action = $validated['action'];
        $affectedCount = 0;

        foreach ($products as $product) {
            if ($action === 'delete') {
                $this->authorize('delete', $product);

                $snapshot = $this->stateSnapshot($product);
                $pricing = $this->pricingSnapshot($product);
                $this->productVariants->purge($product);
                $this->productMedia->purge($product);
                $this->syncProductTags($product, null);
                $product->categories()->detach();
                $product->delete();

                $this->audit->log(
                    action: 'catalog.product.bulk_deleted',
                    target: $product,
                    oldValues: array_merge($snapshot, $pricing),
                    newValues: [],
                    notes: 'Product deleted through bulk action',
                    context: [
                        'module' => 'catalog',
                        'source' => 'product_controller',
                        'bulk_action' => true,
                    ],
                );

                $affectedCount++;
                continue;
            }

            $this->authorize('update', $product);

            $newStatus = match ($action) {
                'publish' => Product::STATUS_PUBLISHED,
                'draft' => Product::STATUS_DRAFT,
                default => Product::STATUS_ARCHIVED,
            };

            if ($product->status === $newStatus) {
                continue;
            }

            $oldStatus = $product->status;
            $product->status = $newStatus;
            $product->save();

            $this->audit->log(
                action: 'catalog.product.bulk_status_changed',
                target: $product,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                notes: 'Product status changed through bulk action',
                context: [
                    'module' => 'catalog',
                    'source' => 'product_controller',
                    'bulk_action' => true,
                    'requested_action' => $action,
                ],
            );

            $affectedCount++;
        }

        return redirect()->route('admin.catalog.products.index')->with('success', "Bulk action completed for {$affectedCount} product(s).");
    }

    /**
     * @return array<string, float|null>
     */
    private function pricingSnapshot(Product $product): array
    {
        return [
            'price' => $product->price !== null ? (float) $product->price : null,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stateSnapshot(Product $product): array
    {
        return Arr::only($product->fresh()->toArray(), [
            'name',
            'slug',
            'type',
            'status',
            'sku',
            'barcode',
            'quantity',
            'is_featured',
            'meta_title',
            'meta_description',
            'canonical_url',
            'og_title',
            'og_description',
            'og_image',
            'noindex',
        ]);
    }

    private function syncProductTags(Product $product, ?string $rawTags): void
    {
        if (! Product::supportsTags()) {
            return;
        }

        $names = collect(explode(',', (string) $rawTags))
            ->map(fn (string $tag) => trim(preg_replace('/\s+/', ' ', $tag) ?? ''))
            ->filter(fn (string $tag) => Str::slug($tag) !== '')
            ->filter()
            ->unique(fn (string $tag) => Str::slug($tag))
            ->values();

        if ($names->isEmpty()) {
            $product->tags()->detach();

            return;
        }

        $tagIds = $names->map(function (string $name): int {
            $slug = Str::slug($name);

            $tag = ProductTag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            return (int) $tag->id;
        });

        $product->tags()->sync($tagIds->all());
    }

    /**
     * @return array{base_currency:string,active_currencies:array<int,string>,multi_currency_enabled:bool}
     */
    private function pricingMeta(): array
    {
        return [
            'base_currency' => $this->financeSettings->baseCurrency(),
            'active_currencies' => $this->financeSettings->activeCurrencies(),
            'multi_currency_enabled' => $this->financeSettings->multiCurrencyEnabled(),
        ];
    }

    /**
     * @return array{supports_variant_media:bool}
     */
    private function variantMeta(): array
    {
        return [
            'supports_variant_media' => true,
        ];
    }

    private function validateVariantPayload(Request $request, ?Product $product): void
    {
        $type = $request->input('type');
        $variantRows = collect($request->input('variants', []))
            ->filter(fn (array $variant) => trim((string) ($variant['sku'] ?? '')) !== '');

        if ($type !== Product::TYPE_VARIABLE && $variantRows->isNotEmpty()) {
            $request->validate([
                'variants' => function () {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'variants' => 'Variants can only be configured for variable products.',
                    ]);
                },
            ]);
        }

        if ($type !== Product::TYPE_VARIABLE) {
            return;
        }

        $optionMap = collect($request->input('options', []))
            ->map(function (array $option): ?array {
                $name = trim((string) ($option['name'] ?? ''));
                $values = collect(explode(',', (string) ($option['values'] ?? '')))
                    ->map(fn (string $value) => trim(preg_replace('/\s+/', ' ', $value) ?? ''))
                    ->filter()
                    ->unique(fn (string $value) => Str::lower($value))
                    ->values();

                if ($name === '' || $values->isEmpty()) {
                    return null;
                }

                return [
                    'name' => $name,
                    'values' => $values->map(fn (string $value) => Str::lower($value))->all(),
                ];
            })
            ->filter()
            ->values();

        $normalizedOptionNames = $optionMap->map(fn (array $option) => Str::lower($option['name']));
        if ($normalizedOptionNames->uniqueStrict()->count() !== $normalizedOptionNames->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'options' => 'Each option name must be unique within the variable product.',
            ]);
        }

        $availableAssignments = $optionMap
            ->mapWithKeys(fn (array $option) => [Str::lower($option['name']) => $option['values']])
            ->all();

        $skuRows = $variantRows->pluck('sku')->map(fn ($sku) => trim((string) $sku));
        if ($skuRows->uniqueStrict()->count() !== $skuRows->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variants' => 'Each variant SKU must be unique within the product.',
            ]);
        }

        $existingVariantIds = $product?->variants()->pluck('id') ?? collect();
        $conflictingSkus = \App\Modules\Catalog\Models\ProductVariant::query()
            ->when($product !== null, fn ($query) => $query->whereNotIn('id', $existingVariantIds))
            ->whereIn('sku', $skuRows->all())
            ->pluck('sku')
            ->all();

        if ($conflictingSkus !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variants' => 'Variant SKU already exists: '.implode(', ', $conflictingSkus),
            ]);
        }

        foreach ($variantRows as $index => $variant) {
            $price = $variant['price'] ?? null;
            $salePrice = $variant['sale_price'] ?? null;

            if ($salePrice !== null && $salePrice !== '' && ($price === null || $price === '' || (float) $salePrice > (float) $price)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "variants.$index.sale_price" => 'Variant sale price must be lower than or equal to the variant regular price.',
                ]);
            }

            $assignments = collect($variant['assignments'] ?? [])
                ->map(fn (array $assignment) => [
                    'option' => Str::lower(trim((string) ($assignment['option'] ?? ''))),
                    'value' => Str::lower(trim((string) ($assignment['value'] ?? ''))),
                ])
                ->filter(fn (array $assignment) => $assignment['option'] !== '' && $assignment['value'] !== '')
                ->values();

            if ($assignments->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "variants.$index.assignments" => 'Each variant must include at least one option assignment.',
                ]);
            }

            foreach ($assignments as $assignment) {
                $allowedValues = $availableAssignments[$assignment['option']] ?? null;

                if (! is_array($allowedValues) || ! in_array($assignment['value'], $allowedValues, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "variants.$index.assignments" => 'Variant assignments must match the defined option names and values.',
                    ]);
                }
            }
        }
    }

    private function syncMerchandising(Product $product, Request $request): void
    {
        $product->relatedProducts()->sync($this->filteredMerchandisingIds($request->input('related_products', []), $product));
        $product->upsellProducts()->sync($this->filteredMerchandisingIds($request->input('upsell_products', []), $product));
        $product->crossSellProducts()->sync($this->filteredMerchandisingIds($request->input('cross_sell_products', []), $product));

        $product->forceFill([
            'badge_labels' => collect(explode(',', (string) $request->input('badge_labels')))
                ->map(fn (string $badge) => trim(preg_replace('/\s+/', ' ', $badge) ?? ''))
                ->filter()
                ->unique(fn (string $badge) => Str::lower($badge))
                ->values()
                ->all(),
        ])->save();
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function filteredMerchandisingIds(array $ids, Product $product): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== (int) $product->id)
            ->unique()
            ->values()
            ->all();
    }
}
