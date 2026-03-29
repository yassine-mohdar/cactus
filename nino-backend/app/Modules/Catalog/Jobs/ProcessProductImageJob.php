<?php

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessProductImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productImageId,
    ) {
        $this->onQueue((string) config('media.processing.queue', 'media'));
    }

    public function handle(MediaStorageSettingsService $mediaSettings): void
    {
        $image = ProductImage::query()->with('product')->find($this->productImageId);

        if (! $image || ! $image->product) {
            return;
        }

        if (! Storage::disk($mediaSettings->defaultDisk())->exists($image->path)) {
            return;
        }

        if (trim((string) $image->alt_text) === '') {
            $image->forceFill([
                'alt_text' => Str::limit(
                    $image->is_featured ? $image->product->name : $image->product->name.' gallery image',
                    255,
                    '',
                ),
            ])->save();
        }
    }
}
