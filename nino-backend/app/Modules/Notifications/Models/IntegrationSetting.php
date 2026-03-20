<?php

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'provider', 'name', 'is_enabled', 'credentials', 'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'metadata' => 'array',
    ];

    protected $hidden = ['credentials'];

    // ── Scopes ─────────────────────────────────────────────
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    // ── Helpers ─────────────────────────────────────────────
    public static function forProvider(string $provider): ?self
    {
        return self::where('provider', $provider)->first();
    }

    public static function defaultDefinitions(): array
    {
        return [
            [
                'provider' => 'smtp',
                'name' => 'SMTP Email Server',
                'is_enabled' => false,
                'credentials' => [
                    'host' => 'smtp.mailtrap.io',
                    'port' => '2525',
                    'username' => '',
                    'password' => '',
                    'encryption' => 'tls',
                    'from_address' => 'notifications@ninoworld.ma',
                    'from_name' => 'NinoWorld',
                ],
            ],
            [
                'provider' => 'twilio',
                'name' => 'Twilio SMS',
                'is_enabled' => false,
                'credentials' => [
                    'account_sid' => '',
                    'auth_token' => '',
                    'from_number' => '',
                ],
            ],
            [
                'provider' => 'whatsapp_api',
                'name' => 'WhatsApp Business API',
                'is_enabled' => false,
                'credentials' => [
                    'access_token' => '',
                    'phone_number_id' => '',
                    'api_version' => 'v18.0',
                    'business_account_id' => '',
                ],
            ],
        ];
    }

    public function requiredCredentialKeys(): array
    {
        return match ($this->provider) {
            'smtp' => ['host', 'port', 'from_address'],
            'twilio' => ['account_sid', 'auth_token', 'from_number'],
            'whatsapp_api' => ['access_token', 'phone_number_id', 'business_account_id'],
            default => [],
        };
    }

    public function isConfigured(): bool
    {
        $requiredKeys = $this->requiredCredentialKeys();

        if ($requiredKeys === []) {
            return collect($this->credentials ?? [])
                ->contains(fn ($value) => filled($value));
        }

        return collect($requiredKeys)
            ->every(fn (string $key) => filled(data_get($this->credentials, $key)));
    }

    public function filledCredentialCount(): int
    {
        $trackedKeys = $this->requiredCredentialKeys();

        if ($trackedKeys === []) {
            return collect($this->credentials ?? [])
                ->filter(fn ($value) => filled($value))
                ->count();
        }

        return collect($trackedKeys)
            ->filter(fn (string $key) => filled(data_get($this->credentials, $key)))
            ->count();
    }

    public function getCredential(string $key, $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }
}
