<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'source_path', 'target_path', 'status_code',
        'is_active', 'hit_count', 'last_hit_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find a redirect for the given path and record the hit.
     */
    public static function resolve(string $path): ?self
    {
        $redirect = self::active()->where('source_path', ltrim($path, '/'))->first();

        if ($redirect) {
            $redirect->increment('hit_count');
            $redirect->update(['last_hit_at' => now()]);
        }

        return $redirect;
    }
}
