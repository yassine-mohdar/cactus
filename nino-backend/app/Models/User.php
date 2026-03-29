<?php

namespace App\Models;

use App\Modules\Customers\Models\Address;
use App\Modules\IAM\Notifications\CustomerResetPasswordNotification;
use App\Modules\IAM\Notifications\StaffResetPasswordNotification;
use App\Modules\Orders\Models\Order;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Community\Models\GroupMembership;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Impersonate, TwoFactorAuthenticatable;

    public const TYPE_STAFF = 'staff';
    public const TYPE_CUSTOMER = 'customer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'username',
        'marketing_opt_in',
        'community_auto_invite_to_default_group',
        'community_default_group_invited_at',
        'email',
        'password',
        'type',
        'status',
        'phone',
        'avatar',
        'organization_id',
        'organization_scope',
        'last_login_at',
        'last_login_ip',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->normalizeIdentityAttributes();
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
            'community_auto_invite_to_default_group' => 'boolean',
            'community_default_group_invited_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function availableTypes(): array
    {
        return [
            self::TYPE_STAFF,
            self::TYPE_CUSTOMER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function availableStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SUSPENDED,
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        
        return $this->name ?? '';
    }

    // --- Relationships ---

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function communityGroupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class, 'user_id');
    }

    // --- Scopes ---

    public function scopeStaff($query)
    {
        return $query->where('type', self::TYPE_STAFF);
    }

    public function scopeCustomers($query)
    {
        return $query->where('type', self::TYPE_CUSTOMER);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    // --- Helpers ---

    public function isStaff(): bool
    {
        return $this->type === self::TYPE_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function hasConfirmedTwoFactorAuthentication(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication();
    }

    public function hasOrganizationScope(string $scope): bool
    {
        return $this->organization_scope === $scope;
    }

    public function recordLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function normalizeIdentityAttributes(): void
    {
        $this->first_name = $this->normalizeNullableString($this->first_name);
        $this->last_name = $this->normalizeNullableString($this->last_name);
        $this->phone = $this->normalizeNullableString($this->phone);
        $this->avatar = $this->normalizeNullableString($this->avatar);
        $this->username = $this->normalizeUsername($this->username);
        $this->name = $this->resolveDisplayName();
    }

    public function sendPasswordResetNotification($token): void
    {
        $expiryMinutes = max(1, (int) config('auth.passwords.'.config('fortify.passwords', config('auth.defaults.passwords', 'users')).'.expire', 60));

        if ($this->isCustomer()) {
            $this->notify(new CustomerResetPasswordNotification(
                resetUrl: route('customer.password.reset', [
                    'token' => $token,
                    'email' => $this->getEmailForPasswordReset(),
                ]),
                expiryMinutes: $expiryMinutes,
            ));

            return;
        }

        $this->notify(new StaffResetPasswordNotification(
            resetUrl: route('password.reset', [
                'token' => $token,
                'email' => $this->getEmailForPasswordReset(),
            ]),
            expiryMinutes: $expiryMinutes,
        ));
    }

    /**
     * Determine if the user can impersonate others.
     */
    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Determine if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        // Don't allow impersonating other super admins
        return ! $this->isSuperAdmin();
    }

    protected function resolveDisplayName(): string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        if ($fullName !== '') {
            return $fullName;
        }

        $name = $this->normalizeNullableString($this->name);

        if ($name !== null) {
            return $name;
        }

        return Str::before((string) $this->email, '@');
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    protected function normalizeUsername(mixed $value): ?string
    {
        $username = $this->normalizeNullableString($value);

        return $username !== null ? Str::lower($username) : null;
    }
}
