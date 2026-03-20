<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IAM\Services\SessionManagementService;
use App\Modules\IAM\Services\UserAvatarService;
use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct(
        private readonly SessionManagementService $sessions,
        private readonly UserAvatarService $avatars,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * Update the customer's profile details.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'marketing_opt_in' => 'nullable|boolean',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user->update(collect($validated)->except('avatar')->all());

        if ($request->hasFile('avatar')) {
            $this->avatars->replace($user, $request->file('avatar'));
        }

        $user->refresh();

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

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}
