<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IAM\Services\SessionManagementService;
use App\Modules\IAM\Services\UserAvatarService;
use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(
        private readonly SessionManagementService $sessions,
        private readonly UserAvatarService $avatars,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    public function edit(Request $request)
    {
        return view('customer.account.profile', [
            'customer' => $request->user(),
        ]);
    }

    /**
     * Update the customer's profile details.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ['nullable', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'marketing_opt_in' => 'nullable|boolean',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $profileData = collect($validated)
            ->except('avatar')
            ->when(
                array_key_exists('email', $validated),
                function ($data) use ($user, $validated) {
                    $normalizedEmail = Str::lower(trim((string) $validated['email']));

                    return $data
                        ->put('email', $normalizedEmail)
                        ->when($normalizedEmail !== $user->email, fn ($payload) => $payload->put('email_verified_at', null));
                }
            )
            ->all();

        $user->forceFill($profileData)->save();

        if ($request->hasFile('avatar')) {
            $this->avatars->replace($user, $request->file('avatar'));
        }

        $user->refresh();

        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return redirect()
                ->route('customer.account.profile.edit')
                ->with('success', 'Profile updated successfully.');
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Update the customer's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => $this->passwordPolicy->requiredRules(),
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ]);

        $this->sessions->invalidateUserSessions(
            $request->user(),
            $request->session()->getId(),
        );

        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return redirect()
                ->route('customer.account.profile.edit')
                ->with('success', 'Password updated successfully.');
        }

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}
