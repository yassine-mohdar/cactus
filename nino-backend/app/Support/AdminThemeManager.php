<?php

namespace App\Support;

class AdminThemeManager
{
    public function active(): string
    {
        $activeTheme = (string) config('admin_theme.active', 'nino-v1');
        $themes = config('admin_theme.themes', []);

        return array_key_exists($activeTheme, $themes) ? $activeTheme : 'nino-v1';
    }

    /**
     * @return array{label?: string, view_path?: string, vite?: array<int, string>}
     */
    public function definition(): array
    {
        $themes = config('admin_theme.themes', []);

        return $themes[$this->active()] ?? $themes['nino-v1'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function viteEntries(): array
    {
        $entries = $this->definition()['vite'] ?? null;

        return is_array($entries) && $entries !== []
            ? array_values($entries)
            : ['resources/themes/nino-v1/app.css', 'resources/js/app.js'];
    }

    public function stylesheetEntry(): string
    {
        foreach ($this->viteEntries() as $entry) {
            if (str_ends_with($entry, '.css')) {
                return $entry;
            }
        }

        return 'resources/themes/nino-v1/app.css';
    }

    /**
     * @return array<int, string>
     */
    public function scriptEntries(): array
    {
        $entries = array_values(array_filter(
            $this->viteEntries(),
            static fn (string $entry): bool => str_ends_with($entry, '.js')
        ));

        return $entries !== [] ? $entries : ['resources/js/app.js'];
    }

    public function viewPath(): string
    {
        return (string) ($this->definition()['view_path'] ?? 'themes/nino-v1');
    }
}
