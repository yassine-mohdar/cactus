<?php

namespace App\Modules\Settings\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdminMediaLibraryService
{
    /**
     * @return Collection<int, array{path:string,url:string,label:string}>
     */
    public function imageOptions(array $directories = []): Collection
    {
        $disk = Storage::disk('public');
        $directories = $directories === [] ? [''] : $directories;

        return collect($directories)
            ->flatMap(function (string $directory) use ($disk) {
                return collect($disk->allFiles($directory));
            })
            ->filter(fn (string $path) => preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path) === 1)
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => $disk->url($path),
                'label' => $path,
            ]);
    }
}
