<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'status',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'country',
    ];

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    // --- Scopes ---

    public function scopePlatform($query)
    {
        return $query->where('type', 'platform');
    }

    public function scopeFranchise($query)
    {
        return $query->where('type', 'franchise');
    }

    public function scopeBranch($query)
    {
        return $query->where('type', 'branch');
    }

    public function scopeSupplier($query)
    {
        return $query->where('type', 'supplier');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // --- Helpers ---

    public function isPlatform(): bool
    {
        return $this->type === 'platform';
    }

    public function isFranchise(): bool
    {
        return $this->type === 'franchise';
    }

    public function isBranch(): bool
    {
        return $this->type === 'branch';
    }

    public function isSupplier(): bool
    {
        return $this->type === 'supplier';
    }

    public function getBranches(): HasMany
    {
        return $this->children()->where('type', 'branch');
    }

    public function getFranchises(): HasMany
    {
        return $this->children()->where('type', 'franchise');
    }
}
