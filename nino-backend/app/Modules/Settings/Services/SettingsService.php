<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /** Cache key prefix */
    private const CACHE_PREFIX = 'settings:';

    /**
     * Get a single setting value.
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $all = $this->group($group);

        return $all[$key] ?? $default;
    }

    /**
     * Get all settings for a group, cached.
     */
    public function group(string $group): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . $group,
            $this->cacheTtlSeconds(),
            fn () => $this->loadGroup($group)
        );
    }

    /**
     * Set a single setting value.
     */
    public function set(string $group, string $key, mixed $value, string $type = 'string', bool $encrypt = false): void
    {
        $stored = Setting::prepareValue($value, $type, $encrypt);

        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'type' => $type, 'is_encrypted' => $encrypt]
        );

        $this->invalidateGroup($group);
    }

    /**
     * Bulk-set settings for a group from an associative array.
     * Each item: ['key' => ..., 'value' => ..., 'type' => ..., 'encrypt' => ...]
     */
    public function setMany(string $group, array $items): void
    {
        if ($items === []) {
            return;
        }

        $timestamp = now();
        $payload = [];

        foreach ($items as $item) {
            $payload[] = [
                'group' => $group,
                'key' => $item['key'],
                'value' => Setting::prepareValue(
                    $item['value'],
                    $item['type'] ?? 'string',
                    $item['encrypt'] ?? false,
                ),
                'type' => $item['type'] ?? 'string',
                'is_encrypted' => $item['encrypt'] ?? false,
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ];
        }

        Setting::query()->upsert(
            $payload,
            ['group', 'key'],
            ['value', 'type', 'is_encrypted', 'updated_at']
        );

        $this->invalidateGroup($group);
    }

    /**
     * Invalidate the cache for a settings group.
     */
    public function invalidateGroup(string $group): void
    {
        Cache::forget(self::CACHE_PREFIX . $group);
    }

    /**
     * Invalidate all settings cache.
     */
    public function invalidateAll(): void
    {
        $groups = Setting::select('group')->distinct()->pluck('group');
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . $group);
        }
    }

    /**
     * Load raw settings from DB for a group.
     */
    private function loadGroup(string $group): array
    {
        return Setting::where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->getTypedValue()])
            ->toArray();
    }

    /**
     * Get all groups available.
     */
    public function allGroups(): array
    {
        return Setting::select('group')->distinct()->pluck('group')->toArray();
    }

    private function cacheTtlSeconds(): int
    {
        return max(0, (int) config('performance.settings_cache_ttl_seconds', 3600));
    }
}
