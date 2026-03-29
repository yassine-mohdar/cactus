<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatewaySetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gateway_id',
        'name',
        'is_enabled',
        'mode',
        'credentials',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     * We cast credentials to encrypted:array to ensure API keys are encrypted at rest in the database.
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'metadata' => 'array',
    ];

    /**
     * Helper to safely retrieve a specific credential key (e.g., 'secret_key', 'store_id')
     */
    public function getCredential(string $key, $default = null)
    {
        $creds = $this->credentials ?? [];
        return $creds[$key] ?? $default;
    }
}
