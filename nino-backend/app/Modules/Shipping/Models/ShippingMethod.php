<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'carrier',
        'shipping_carrier_id',
        'description',
        'base_cost',
        'free_shipping_threshold',
        'estimated_days',
        'is_enabled',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function shippingCarrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function districtOverrides(): HasMany
    {
        return $this->hasMany(ShippingMethodDistrictOverride::class);
    }

    /**
     * Scope to only enabled methods.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Calculate shipping cost for a given order subtotal.
     */
    public function calculateCost(float $orderSubtotal): float
    {
        if ($this->free_shipping_threshold && $orderSubtotal >= $this->free_shipping_threshold) {
            return 0.00;
        }
        return (float) $this->base_cost;
    }

    public function carrierLabel(): string
    {
        return $this->shippingCarrier?->name
            ?? trim((string) $this->carrier)
            ?: 'Unassigned';
    }

    public function isApiManaged(): bool
    {
        return $this->shippingCarrier?->provider !== null
            && $this->shippingCarrier->provider !== ShippingCarrier::PROVIDER_MANUAL;
    }

    /**
     * @return string[]
     */
    public function allowedCountryCodes(): array
    {
        return collect(data_get($this->metadata ?? [], 'allowed_countries', []))
            ->map(fn ($country) => strtoupper(trim((string) $country)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function supportsCountry(?string $countryCode): bool
    {
        $allowedCountries = $this->allowedCountryCodes();

        if ($allowedCountries === []) {
            return true;
        }

        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return true;
        }

        return in_array($countryCode, $allowedCountries, true);
    }
}
