<?php

namespace App\Modules\Settings\Services;

use Illuminate\Validation\Rules\Password;

class PasswordPolicyService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function rule(): Password
    {
        $rule = Password::min($this->minimumLength());

        if ($this->boolean('password_require_mixed_case', true)) {
            $rule = $rule->mixedCase();
        }

        if ($this->boolean('password_require_numbers', true)) {
            $rule = $rule->numbers();
        }

        if ($this->boolean('password_require_symbols', false)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    /**
     * @return array<int, mixed>
     */
    public function requiredRules(): array
    {
        return ['required', 'string', $this->rule(), 'confirmed'];
    }

    /**
     * @return array<int, mixed>
     */
    public function optionalRules(): array
    {
        return ['nullable', 'string', $this->rule(), 'confirmed'];
    }

    private function minimumLength(): int
    {
        return max(6, min(30, (int) $this->settings->get('security', 'password_min_length', 8)));
    }

    private function boolean(string $key, bool $default): bool
    {
        return (bool) $this->settings->get('security', $key, $default);
    }
}
