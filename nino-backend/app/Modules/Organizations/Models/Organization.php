<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    public const TYPE_PLATFORM = 'platform';
    public const TYPE_FRANCHISE = 'franchise';
    public const TYPE_BRANCH = 'branch';
    public const TYPE_SUPPLIER = 'supplier';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

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

    protected static function newFactory(): Factory
    {
        return OrganizationFactory::new();
    }

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
        return $query->where('type', self::TYPE_PLATFORM);
    }

    public function scopeFranchise($query)
    {
        return $query->where('type', self::TYPE_FRANCHISE);
    }

    public function scopeBranch($query)
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    public function scopeSupplier($query)
    {
        return $query->where('type', self::TYPE_SUPPLIER);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // --- Helpers ---

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isFranchise(): bool
    {
        return $this->type === self::TYPE_FRANCHISE;
    }

    public function isBranch(): bool
    {
        return $this->type === self::TYPE_BRANCH;
    }

    public function isSupplier(): bool
    {
        return $this->type === self::TYPE_SUPPLIER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getBranches(): HasMany
    {
        return $this->children()->where('type', self::TYPE_BRANCH);
    }

    public function getFranchises(): HasMany
    {
        return $this->children()->where('type', self::TYPE_FRANCHISE);
    }

    public function supportsChildType(string $childType): bool
    {
        return match ($this->type) {
            self::TYPE_PLATFORM => $childType === self::TYPE_FRANCHISE,
            self::TYPE_FRANCHISE => $childType === self::TYPE_BRANCH,
            default => false,
        };
    }
}
