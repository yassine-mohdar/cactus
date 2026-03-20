<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\GroupMembershipPolicy;
use App\Modules\Community\Enums\GroupVisibility;
use App\Modules\Community\Services\DefaultCommunityGroupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $table = 'community_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'visibility',
        'membership_policy',
        'is_active',
        'system_key',
        'owner_id',
        'metadata',
    ];

    protected $casts = [
        'visibility' => GroupVisibility::class,
        'membership_policy' => GroupMembershipPolicy::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class, 'group_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_group_memberships', 'group_id', 'user_id')
            ->withPivot(['role', 'status', 'joined_at', 'invited_by', 'metadata'])
            ->withTimestamps();
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(GroupJoinRequest::class, 'group_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'group_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class, 'group_id');
    }

    public function moderationQueueItems(): HasMany
    {
        return $this->hasMany(ModerationQueueItem::class, 'group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultFoundation($query)
    {
        return $query->where('system_key', DefaultCommunityGroupService::DEFAULT_GROUP_SYSTEM_KEY);
    }

    public function isDefaultGroup(): bool
    {
        return $this->system_key === DefaultCommunityGroupService::DEFAULT_GROUP_SYSTEM_KEY;
    }

    public function allowsJoinRequests(): bool
    {
        return $this->membership_policy === GroupMembershipPolicy::APPROVAL;
    }
}
