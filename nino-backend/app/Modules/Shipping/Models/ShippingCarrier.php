<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCarrier extends Model
{
    public const PROVIDER_MANUAL = 'manual';
    public const PROVIDER_SENDIT = 'sendit';

    protected $fillable = [
        'code',
        'name',
        'provider',
        'is_enabled',
        'tracking_url_template',
        'credentials',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(ShippingCarrierDistrict::class)->orderBy('city')->orderBy('district_name');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function isSendit(): bool
    {
        return $this->provider === self::PROVIDER_SENDIT;
    }

    public function isConfigured(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (! $this->isSendit()) {
            return true;
        }

        return filled($this->getCredential('public_key'))
            && filled($this->getCredential('secret_key'))
            && filled($this->setting('pickup_district_id'));
    }

    public function getCredential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }
}
