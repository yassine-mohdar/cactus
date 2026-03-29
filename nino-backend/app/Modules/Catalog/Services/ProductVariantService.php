<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductOption;
use App\Modules\Catalog\Models\ProductOptionValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function __construct(
        private readonly MediaStorageSettingsService $mediaSettings,
    ) {}

    /**
     * @param  array<int, array{name?: string|null, values?: string|null}>  $optionPayloads
     * @param  array<int, array{
     *     sku?: string|null,
     *     price?: string|int|float|null,
     *     sale_price?: string|int|float|null,
     *     quantity?: string|int|null,
     *     assignments?: array<int, array{option?: string|null, value?: string|null}>|null
     * }>  $variantPayloads
     * @param  array<int|string, UploadedFile>  $variantImages
     */
    public function sync(Product $product, array $optionPayloads, array $variantPayloads, array $variantImages = []): void
    {
        if (! $product->isVariable()) {
            $this->purge($product);

            return;
        }

        $product->loadMissing(['options.values', 'variants']);
        $this->deleteExistingVariantImages($product);
        $product->variants()->delete();
        $product->options()->delete();

        $options = $this->createOptions($product, $optionPayloads);
        $optionValueMap = $this->buildOptionValueMap($options);

        foreach ($this->normalizedVariants($variantPayloads) as $index => $variantPayload) {
            $variant = $product->variants()->create([
                'sku' => $variantPayload['sku'],
                'price' => $this->normalizeDecimal($variantPayload['price']),
                'sale_price' => $this->normalizeDecimal($variantPayload['sale_price']),
                'quantity' => max(0, (int) ($variantPayload['quantity'] ?? 0)),
                'image_path' => $this->storeVariantImage($product, $variantImages[$index] ?? null),
            ]);

            $assignmentIds = collect($variantPayload['assignments'] ?? [])
                ->map(function (array $assignment) use ($optionValueMap): ?int {
                    $optionKey = Str::lower(trim((string) ($assignment['option'] ?? '')));
                    $valueKey = Str::lower(trim((string) ($assignment['value'] ?? '')));

                    return $optionValueMap[$optionKey][$valueKey] ?? null;
                })
                ->filter()
                ->values()
                ->all();

            $variant->optionValues()->sync($assignmentIds);
        }

        $product->unsetRelation('options');
        $product->unsetRelation('variants');
    }

    public function purge(Product $product): void
    {
        $product->loadMissing(['options.values', 'variants']);
        $this->deleteExistingVariantImages($product);
        $product->variants()->delete();
        $product->options()->delete();
        $product->unsetRelation('options');
        $product->unsetRelation('variants');
    }

    /**
     * @param  array<int, array{name?: string|null, values?: string|null}>  $optionPayloads
     * @return Collection<int, ProductOption>
     */
    private function createOptions(Product $product, array $optionPayloads): Collection
    {
        return collect($optionPayloads)
            ->map(function (array $payload, int $index) use ($product): ?ProductOption {
                $name = trim((string) ($payload['name'] ?? ''));
                $values = collect(explode(',', (string) ($payload['values'] ?? '')))
                    ->map(fn (string $value) => trim(preg_replace('/\s+/', ' ', $value) ?? ''))
                    ->filter()
                    ->unique(fn (string $value) => Str::lower($value))
                    ->values();

                if ($name === '' || $values->isEmpty()) {
                    return null;
                }

                $option = $product->options()->create([
                    'name' => $name,
                    'position' => $index,
                ]);

                foreach ($values as $valueIndex => $value) {
                    $option->values()->create([
                        'value' => $value,
                        'position' => $valueIndex,
                    ]);
                }

                return $option->fresh('values');
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return array<string, array<string, int>>
     */
    private function buildOptionValueMap(Collection $options): array
    {
        $map = [];

        foreach ($options as $option) {
            $optionKey = Str::lower($option->name);
            foreach ($option->values as $value) {
                $map[$optionKey][Str::lower($value->value)] = (int) $value->id;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array{
     *     sku?: string|null,
     *     price?: string|int|float|null,
     *     sale_price?: string|int|float|null,
     *     quantity?: string|int|null,
     *     assignments?: array<int, array{option?: string|null, value?: string|null}>|null
     * }>  $variantPayloads
     * @return Collection<int, array{
     *     sku: string,
     *     price: string|int|float|null,
     *     sale_price: string|int|float|null,
     *     quantity: string|int|null,
     *     assignments: array<int, array{option?: string|null, value?: string|null}>
     * }>
     */
    private function normalizedVariants(array $variantPayloads): Collection
    {
        return collect($variantPayloads)
            ->map(function (array $variant): ?array {
                $sku = trim((string) ($variant['sku'] ?? ''));

                if ($sku === '') {
                    return null;
                }

                $assignments = collect($variant['assignments'] ?? [])
                    ->map(fn (array $assignment) => [
                        'option' => trim((string) ($assignment['option'] ?? '')),
                        'value' => trim((string) ($assignment['value'] ?? '')),
                    ])
                    ->filter(fn (array $assignment) => $assignment['option'] !== '' && $assignment['value'] !== '')
                    ->values()
                    ->all();

                return [
                    'sku' => $sku,
                    'price' => $variant['price'] ?? null,
                    'sale_price' => $variant['sale_price'] ?? null,
                    'quantity' => $variant['quantity'] ?? 0,
                    'assignments' => $assignments,
                ];
            })
            ->filter()
            ->values();
    }

    private function normalizeDecimal(string|int|float|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function storeVariantImage(Product $product, mixed $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }

        return $image->store($this->variantDirectory($product), $this->mediaSettings->defaultDisk());
    }

    private function variantDirectory(Product $product): string
    {
        return trim($this->mediaSettings->catalogDirectory().'/products/'.$product->getKey().'/variants', '/');
    }

    private function deleteExistingVariantImages(Product $product): void
    {
        $disk = $this->mediaSettings->defaultDisk();

        foreach ($product->variants as $variant) {
            if (is_string($variant->image_path) && $variant->image_path !== '' && Storage::disk($disk)->exists($variant->image_path)) {
                Storage::disk($disk)->delete($variant->image_path);
            }
        }
    }
}
