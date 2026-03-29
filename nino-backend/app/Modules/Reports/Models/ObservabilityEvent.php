<?php

namespace App\Modules\Reports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservabilityEvent extends Model
{
    protected $fillable = [
        'event_type',
        'source',
        'severity',
        'name',
        'route_name',
        'method',
        'url',
        'status_code',
        'duration_ms',
        'user_id',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'status_code' => 'integer',
        'user_id' => 'integer',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
