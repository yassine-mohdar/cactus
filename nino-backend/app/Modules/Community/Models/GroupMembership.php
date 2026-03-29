<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\GroupMembershipRole;
use App\Modules\Community\Enums\GroupMembershipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMembership extends Model
{
    protected $table = 'community_group_memberships';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'invited_by',
        'metadata',
    ];

    protected $casts = [
        'role' => GroupMembershipRole::class,
        'status' => GroupMembershipStatus::class,
        'joined_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', GroupMembershipStatus::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === GroupMembershipStatus::ACTIVE;
    }
}
