<?php

namespace App\Actions\Fortify;

use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Contracts\Validation\Rule;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return app(PasswordPolicyService::class)->requiredRules();
    }
}
