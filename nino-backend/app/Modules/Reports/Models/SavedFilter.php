<?php

namespace App\Modules\Reports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFilter extends Model
{
    protected $fillable = ['name', 'module', 'filters', 'is_default', 'created_by'];

    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForUser($query, ?int $userId = null)
    {
        return $query->where('created_by', $userId ?? auth()->id());
    }

    /**
     * Get the URL with saved filters applied.
     */
    public function toQueryString(): string
    {
        return http_build_query($this->filters ?? []);
    }
}
