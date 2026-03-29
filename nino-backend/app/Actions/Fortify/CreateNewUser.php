<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $input = [
            ...$input,
            'name' => $this->normalizeNullableString($input['name'] ?? null),
            'first_name' => $this->normalizeNullableString($input['first_name'] ?? null),
            'last_name' => $this->normalizeNullableString($input['last_name'] ?? null),
            'username' => $this->normalizeUsername($input['username'] ?? null),
            'email' => trim((string) ($input['email'] ?? '')),
        ];

        $validator = Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique(User::class)],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ]);

        $validator->after(function ($validator) use ($input): void {
            $name = trim((string) ($input['name'] ?? ''));
            $firstName = trim((string) ($input['first_name'] ?? ''));
            $lastName = trim((string) ($input['last_name'] ?? ''));

            if ($name === '' && $firstName === '' && $lastName === '') {
                $validator->errors()->add('name', 'A name is required.');
            }
        });

        $validated = $validator->validate();
        [$name, $firstName, $lastName] = $this->resolveProfileIdentity($validated);

        return User::create([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0:string,1:?string,2:?string}
     */
    protected function resolveProfileIdentity(array $validated): array
    {
        $name = trim((string) ($validated['name'] ?? ''));
        $firstName = $this->normalizeNullableString($validated['first_name'] ?? null);
        $lastName = $this->normalizeNullableString($validated['last_name'] ?? null);

        if ($firstName !== null || $lastName !== null) {
            return [
                trim(implode(' ', array_filter([$firstName, $lastName]))),
                $firstName,
                $lastName,
            ];
        }

        [$firstName, $lastName] = $this->splitFullName($name);

        return [$name, $firstName, $lastName];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    protected function splitFullName(string $name): array
    {
        $segments = preg_split('/\s+/', trim($name)) ?: [];
        $segments = array_values(array_filter($segments, fn ($segment): bool => $segment !== ''));

        if ($segments === []) {
            return [null, null];
        }

        $firstName = array_shift($segments);
        $lastName = $segments !== [] ? implode(' ', $segments) : null;

        return [$firstName, $lastName];
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
