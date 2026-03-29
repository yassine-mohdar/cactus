<?php

namespace App\Modules\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    public const TYPE_BILLING = 'billing';
    public const TYPE_SHIPPING = 'shipping';

    public const TYPES = [
        self::TYPE_BILLING,
        self::TYPE_SHIPPING,
    ];

    public const COUNTRY_CODE_PATTERN = '/^[A-Z]{2}$/';

    protected $fillable = [
        'user_id',
        'type',
        'is_default',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBilling($query)
    {
        return $query->where('type', self::TYPE_BILLING);
    }

    public function scopeShipping($query)
    {
        return $query->where('type', self::TYPE_SHIPPING);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function isBilling(): bool
    {
        return $this->type === self::TYPE_BILLING;
    }

    public function isShipping(): bool
    {
        return $this->type === self::TYPE_SHIPPING;
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }
}
