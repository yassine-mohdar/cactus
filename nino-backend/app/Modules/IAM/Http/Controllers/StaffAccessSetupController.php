<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\IAM\Http\Requests\CompleteStaffAccessSetupRequest;
use App\Modules\IAM\Services\AdminHomeRouteService;
use App\Modules\IAM\Services\SessionManagementService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffAccessSetupController extends Controller
{
    public function __construct(
        private readonly AdminHomeRouteService $homeRoutes,
        private readonly SessionManagementService $sessions,
    ) {}

    public function show(Request $request, string $token)
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        return view('admin.auth.staff-access-setup', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(CompleteStaffAccessSetupRequest $request): RedirectResponse
    {
        $staff = User::query()
            ->where('email', (string) $request->validated('email'))
            ->first();

        if (! $staff instanceof User || ! $staff->isStaff()) {
            throw ValidationException::withMessages([
                'email' => __('Only staff accounts can use this access setup link.'),
            ]);
        }

        $status = Password::broker($this->broker())->reset(
            $request->safe()->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'status' => $user->status === 'suspended' ? 'suspended' : 'active',
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

        Auth::login($staff, true);
        $request->session()->regenerate();
        $staff->recordLogin();

        return redirect()
            ->intended(route($this->homeRoutes->routeNameFor($staff)))
            ->with('success', 'Your staff access password has been set successfully.');
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
