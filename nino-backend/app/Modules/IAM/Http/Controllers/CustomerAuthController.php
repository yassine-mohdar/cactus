<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\IAM\Http\Requests\CustomerLoginRequest;
use App\Modules\IAM\Services\AdminHomeRouteService;
use App\Modules\Customers\Services\CustomerAccountPresenter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\LockoutResponse;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly AdminHomeRouteService $homeRoutes,
        private readonly CustomerAccountPresenter $accountPresenter,
    ) {}

    public function showLogin(Request $request)
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        return view('customer.auth.login');
    }

    public function login(CustomerLoginRequest $request, PrepareAuthenticatedSession $prepareAuthenticatedSession)
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            return app(LockoutResponse::class)->toResponse($request);
        }

        if (! Auth::attempt([
            'email' => (string) $request->validated('email'),
            'password' => (string) $request->validated('password'),
            'type' => 'customer',
            'status' => 'active',
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match an active customer account.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $prepareAuthenticatedSession->handle($request, function (Request $request) {
            $request->user()?->recordLogin();

            return redirect()->intended(route('customer.account.home'));
        });
    }

    public function home(Request $request)
    {
        $customer = $request->user();
        $overview = $this->accountPresenter->overview($customer);

        return view('customer.account.home', [
            'customer' => $customer,
            'overview' => $overview,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
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

    private function throttleKey(Request $request): string
    {
        $email = Str::lower((string) $request->string('email'));

        return Str::transliterate($email.'|'.$request->ip().'|customer');
    }
}
