<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\IAM\Services\AdminHomeRouteService;
use App\Modules\IAM\Services\SessionManagementService;
use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPasswordResetController extends Controller
{
    public function __construct(
        private readonly AdminHomeRouteService $homeRoutes,
        private readonly SessionManagementService $sessions,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    public function showLinkRequestForm(Request $request)
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        return view('customer.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower((string) $validated['email']);
        $account = User::query()->where('email', $email)->first();

        if ($account instanceof User && ! $account->isCustomer()) {
            throw ValidationException::withMessages([
                'email' => __('The provided email does not belong to a customer account.'),
            ]);
        }

        $status = Password::broker($this->broker())->sendResetLink([
            'email' => $email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return back()->with('status', __($status));
    }

    public function showResetForm(Request $request, string $token)
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        return view('customer.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->passwordPolicy->requiredRules(),
        ]);

        $customer = User::query()
            ->where('email', Str::lower((string) $validated['email']))
            ->first();

        if (! $customer instanceof User || ! $customer->isCustomer()) {
            throw ValidationException::withMessages([
                'email' => __('Only customer accounts can use this password reset flow.'),
            ]);
        }

        $status = Password::broker($this->broker())->reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $this->sessions->invalidateUserSessions($user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        Auth::login($customer, true);
        $request->session()->regenerate();
        $customer->recordLogin();

        return redirect()
            ->route('customer.account.home')
            ->with('success', 'Your customer password has been reset successfully.');
    }

    private function broker(): string
    {
        return (string) config('fortify.passwords', config('auth.defaults.passwords', 'users'));
    }

    private function redirectAuthenticatedUser(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->isStaff()
            ? redirect()->route($this->homeRoutes->routeNameFor($user))
            : redirect()->route('customer.account.home');
    }
}
