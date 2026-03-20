<?php

namespace App\Modules\IAM\Models;

use App\Modules\IAM\Support\PermissionNaming;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public const MANAGE_ROLES = 'users.manage_roles';

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function groupKey(): string
    {
        return PermissionNaming::groupKey($this->name);
    }

    public function groupLabel(): string
    {
        return PermissionNaming::groupLabel($this->name);
    }

    public function roles(): BelongsToMany
    {
        /** @var BelongsToMany $relation */
        $relation = parent::roles();

        return $relation;
    }

    public function users(): MorphToMany
    {
        /** @var MorphToMany $relation */
        $relation = parent::users();

        return $relation;
    }
}
