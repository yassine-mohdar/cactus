<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Jobs\ProcessProductImageJob;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaService
{
    public function __construct(
        private readonly MediaStorageSettingsService $mediaSettings,
    ) {}

    /**
     * @param  array{
     *   featured_image?: UploadedFile|null,
     *   featured_image_alt?: string|null,
     *   gallery_images?: array<int, UploadedFile>|null,
     *   featured_image_id?: int|string|null,
     *   media_meta?: array<int|string, array{alt_text?: string|null, sort_order?: int|string|null}>,
     *   remove_image_ids?: array<int|string>|null
     * }  $payload
     */
    public function sync(Product $product, array $payload): void
    {
        $disk = $this->disk();
        $product->loadMissing('images');

        $existingImages = $product->images->keyBy('id');
        $removeIds = collect($payload['remove_image_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $existingImages->has($id))
            ->values();

        foreach ($removeIds as $imageId) {
            $this->deleteImage($existingImages[$imageId], $disk);
            $existingImages->forget($imageId);
        }

        $mediaMeta = collect($payload['media_meta'] ?? []);
        foreach ($existingImages as $image) {
            $meta = $mediaMeta->get((string) $image->id, $mediaMeta->get($image->id, []));

            $image->forceFill([
                'alt_text' => $this->normalizeAltText($meta['alt_text'] ?? $image->alt_text),
                'sort_order' => $this->normalizeSortOrder($meta['sort_order'] ?? $image->sort_order),
                'is_featured' => false,
            ])->save();
        }

        $newFeaturedImage = null;
        if (($payload['featured_image'] ?? null) instanceof UploadedFile) {
            $newFeaturedImage = $this->storeUploadedImage(
                $product,
                $payload['featured_image'],
                $this->normalizeAltText($payload['featured_image_alt'] ?? null),
                true,
                $disk,
            );
        }

        collect($payload['gallery_images'] ?? [])
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values()
            ->each(fn (UploadedFile $file) => $this->storeUploadedImage(
                $product,
                $file,
                null,
                false,
                $disk,
            ));

        $images = $product->images()->orderBy('sort_order')->orderBy('id')->get();
        $featuredImageId = $newFeaturedImage?->id
            ?? $this->validFeaturedSelection($payload['featured_image_id'] ?? null, $images)
            ?? $images->firstWhere('is_featured', true)?->id
            ?? $images->first()?->id;

        $orderedImages = $images->sortBy([
            fn (ProductImage $image) => $image->id === $featuredImageId ? -1 : 0,
            fn (ProductImage $image) => (int) $image->sort_order,
            fn (ProductImage $image) => (int) $image->id,
        ])->values();

        foreach ($orderedImages as $index => $image) {
            $image->forceFill([
                'is_featured' => $image->id === $featuredImageId,
                'sort_order' => $index,
                'alt_text' => $this->normalizeAltText($image->alt_text) ?: $this->defaultAltText($product, $image->id === $featuredImageId, $index),
            ])->save();
        }

        $product->unsetRelation('images');
        $product->unsetRelation('featuredImage');
    }

    public function purge(Product $product): void
    {
        $disk = $this->disk();
        $product->loadMissing('images');

        foreach ($product->images as $image) {
            $this->deleteImage($image, $disk);
        }
    }

    public function disk(): string
    {
        return $this->mediaSettings->defaultDisk();
    }

    public function directory(Product $product): string
    {
        return trim($this->mediaSettings->catalogDirectory().'/products/'.$product->getKey(), '/');
    }

    private function storeUploadedImage(
        Product $product,
        UploadedFile $image,
        ?string $altText,
        bool $isFeatured,
        string $disk,
    ): ProductImage {
        $path = $image->store($this->directory($product), $disk);

        $storedImage = $product->images()->create([
            'path' => $path,
            'is_featured' => $isFeatured,
            'sort_order' => (int) ($product->images()->max('sort_order') ?? -1) + 1,
            'alt_text' => $altText,
        ]);

        if ((bool) config('media.processing.enabled', true)) {
            ProcessProductImageJob::dispatch($storedImage->id);
        }

        return $storedImage;
    }

    private function deleteImage(ProductImage $image, string $disk): void
    {
        if ($image->path !== '' && Storage::disk($disk)->exists($image->path)) {
            Storage::disk($disk)->delete($image->path);
        }

        $image->delete();
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     */
    private function validFeaturedSelection(int|string|null $candidate, Collection $images): ?int
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }

        $id = (int) $candidate;

        return $images->contains(fn (ProductImage $image) => $image->id === $id) ? $id : null;
    }

    private function normalizeSortOrder(int|string|null $value): int
    {
        return max(0, (int) ($value ?? 0));
    }

    private function normalizeAltText(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? Str::limit($normalized, 255, '') : null;
    }

    private function defaultAltText(Product $product, bool $isFeatured, int $index): string
    {
        $label = $isFeatured ? $product->name : $product->name.' gallery image '.($index + 1);

        return Str::limit($label, 255, '');
    }
}
