<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\ReactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    protected $table = 'community_reactions';

    protected $fillable = [
        'reactable_type',
        'reactable_id',
        'user_id',
        'type',
        'metadata',
    ];

    protected $casts = [
        'type' => ReactionType::class,
        'metadata' => 'array',
    ];

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForType($query, ReactionType $type)
    {
        return $query->where('type', $type);
    }
}
