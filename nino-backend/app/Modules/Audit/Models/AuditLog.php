<?php

namespace App\Modules\Audit\Models;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_type',
        'actor_name',
        'actor_email',
        'action',
        'auditable_type',
        'auditable_id',
        'target_label',
        'old_values',
        'new_values',
        'context',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user(): BelongsTo
    {
        return $this->actor();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // --- Helpers ---

    public static function record(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null,
        array $context = [],
        ?Authenticatable $actor = null,
        ?string $targetLabel = null,
    ): self {
        return app(AuditLogger::class)->log(
            action: $action,
            target: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            notes: $notes,
            context: $context,
            actor: $actor,
            targetLabel: $targetLabel,
        );
    }
}
