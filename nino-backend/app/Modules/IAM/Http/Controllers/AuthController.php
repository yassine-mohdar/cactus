<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IAM\Services\AdminHomeRouteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Lockout;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\LockoutResponse;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\LoginRateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(
        Request $request,
        LoginRateLimiter $limiter,
        RedirectsIfTwoFactorAuthenticatable $redirectIfTwoFactorAuthenticatable,
        PrepareAuthenticatedSession $prepareAuthenticatedSession,
        AdminHomeRouteService $homeRoutes,
    )
    {
        if (config('fortify.lowercase_usernames')) {
            $request->merge([
                'email' => Str::lower((string) $request->input('email')),
            ]);
        }

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($limiter->tooManyAttempts($request)) {
            event(new Lockout($request));

            return app(LockoutResponse::class)->toResponse($request);
        }

        return $redirectIfTwoFactorAuthenticatable->handle($request, function (Request $request) use (
            $limiter,
            $prepareAuthenticatedSession,
            $homeRoutes,
        ) {
            if (! Auth::attempt([
                'email' => (string) $request->input('email'),
                'password' => (string) $request->input('password'),
                'type' => 'staff',
                'status' => 'active',
            ], $request->boolean('remember'))) {
                $limiter->increment($request);

                throw ValidationException::withMessages([
                    'email' => __('The provided credentials do not match our records.'),
                ]);
            }

            return $prepareAuthenticatedSession->handle($request, function (Request $request) use ($homeRoutes) {
                $request->user()?->recordLogin();

                return redirect()->intended(route($homeRoutes->routeNameFor($request->user())));
            });
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
