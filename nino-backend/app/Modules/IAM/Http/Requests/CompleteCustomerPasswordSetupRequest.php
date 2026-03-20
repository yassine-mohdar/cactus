<?php

namespace App\Modules\IAM\Http\Requests;

use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class CompleteCustomerPasswordSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => app(PasswordPolicyService::class)->requiredRules(),
        ];
    }
}
