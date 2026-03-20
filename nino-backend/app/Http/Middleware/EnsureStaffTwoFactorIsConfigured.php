<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Settings\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffTwoFactorIsConfigured
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->isStaff() || ! Features::enabled(Features::twoFactorAuthentication())) {
            return $next($request);
        }

        if (! (bool) $this->settings->get('security', 'force_2fa', false)) {
            return $next($request);
        }

        if ($user->hasConfirmedTwoFactorAuthentication() || $request->routeIs('admin.security.two-factor.*')) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('admin.security.two-factor.setup');
    }
}
