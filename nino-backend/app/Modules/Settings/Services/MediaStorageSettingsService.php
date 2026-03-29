<?php

namespace App\Modules\Settings\Services;

class MediaStorageSettingsService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function defaultDisk(): string
    {
        return trim((string) $this->settings->get(
            'system',
            'default_media_disk',
            config('media.default_disk', 'public'),
        )) ?: (string) config('media.default_disk', 'public');
    }

    public function avatarDirectory(): string
    {
        return $this->normalizedDirectory(
            (string) $this->settings->get(
                'system',
                'avatar_media_directory',
                config('media.directories.avatars', 'avatars'),
            ),
            (string) config('media.directories.avatars', 'avatars'),
        );
    }

    public function catalogDirectory(): string
    {
        return $this->normalizedDirectory(
            (string) $this->settings->get(
                'system',
                'catalog_media_directory',
                config('media.directories.catalog', 'categories'),
            ),
            (string) config('media.directories.catalog', 'categories'),
        );
    }

    private function normalizedDirectory(string $value, string $fallback): string
    {
        $normalized = trim($value, "/ \t\n\r\0\x0B");

        return $normalized !== '' ? $normalized : $fallback;
    }
}
