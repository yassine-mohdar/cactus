<?php

namespace App\Support;

use Illuminate\Support\Collection;

class GlobalAddressDirectory
{
    private static ?Collection $directory = null;

    /**
     * @return array<int, array{value:string,label:string,meta:?string,search:string}>
     */
    public static function stateOptions(?string $countryCode): array
    {
        $country = self::country($countryCode);

        if ($country === null) {
            return [];
        }

        return collect($country['states'] ?? [])
            ->filter(fn (array $state) => filled($state['name'] ?? null))
            ->sortBy(fn (array $state) => mb_strtolower((string) $state['name']))
            ->map(fn (array $state) => [
                'value' => (string) $state['name'],
                'label' => (string) $state['name'],
                'meta' => filled($state['code'] ?? null) ? (string) $state['code'] : null,
                'search' => mb_strtolower(trim((string) ($state['name'] ?? '')).' '.trim((string) ($state['code'] ?? ''))),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string,meta:?string,search:string}>
     */
    public static function cityOptions(?string $countryCode, ?string $stateName = null): array
    {
        $country = self::country($countryCode);

        if ($country === null) {
            return [];
        }

        $states = collect($country['states'] ?? []);
        $cities = collect();

        if (filled($stateName)) {
            $state = $states
                ->first(fn (array $entry) => mb_strtolower((string) ($entry['name'] ?? '')) === mb_strtolower(trim((string) $stateName)));

            if ($state !== null) {
                $cities = collect($state['cities'] ?? []);
            }
        }

        if ($cities->isEmpty() && $states->isNotEmpty() && ! filled($stateName)) {
            return [];
        }

        if ($cities->isEmpty()) {
            $cities = collect($country['cities'] ?? []);
        }

        return $cities
            ->map(fn ($city) => trim((string) $city))
            ->filter()
            ->unique(fn (string $city) => mb_strtolower($city))
            ->sortBy(fn (string $city) => mb_strtolower($city))
            ->map(fn (string $city) => [
                'value' => $city,
                'label' => $city,
                'meta' => filled($stateName) ? trim((string) $stateName) : 'City',
                'search' => mb_strtolower($city.' '.trim((string) $stateName)),
            ])
            ->values()
            ->all();
    }

    private static function country(?string $countryCode): ?array
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return null;
        }

        return self::directory()->get($countryCode);
    }

    private static function directory(): Collection
    {
        if (self::$directory !== null) {
            return self::$directory;
        }

        $path = resource_path('data/address/global-address-directory.json');
        $payload = file_exists($path) ? file_get_contents($path) : false;
        $decoded = is_string($payload) ? json_decode($payload, true) : [];

        self::$directory = collect(is_array($decoded) ? $decoded : []);

        return self::$directory;
    }
}
