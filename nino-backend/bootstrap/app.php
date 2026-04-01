<?php

use App\Http\Middleware\ApplyGeneralSettings;
use App\Http\Middleware\ApplyFinanceSettings;
use App\Http\Middleware\ApplySecuritySettings;
use App\Http\Middleware\ApplySystemSettings;
use App\Http\Middleware\EnsureCustomerUser;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureStaffUser;
use App\Http\Middleware\EnsureStaffTwoFactorIsConfigured;
use App\Http\Middleware\EnsureUserHasAnyPermission;
use App\Http\Middleware\LogSlowOperations;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ApplyGeneralSettings::class,
            ApplyFinanceSettings::class,
            ApplySecuritySettings::class,
        ], append: [
            LogSlowOperations::class,
            ApplySystemSettings::class,
        ]);

        $middleware->preventRequestForgery([
            'api/cart',
            'api/cart/*',
            'api/checkout/process',
        ]);

        $middleware->alias([
            'customer.only' => EnsureCustomerUser::class,
            'feature.enabled' => EnsureFeatureEnabled::class,
            'staff.only' => EnsureStaffUser::class,
            'staff.2fa.enforced' => EnsureStaffTwoFactorIsConfigured::class,
            'permission.any' => EnsureUserHasAnyPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'current_password',
            'remember_token',
            'token',
            'secret',
            'secret_key',
            'client_secret',
            'api_key',
            'access_token',
            'refresh_token',
            'auth_token',
            'private_key',
            'webhook_secret',
            'recovery_code',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'otp',
            'one_time_password',
            'card_number',
            'cvv',
            'cvc',
        ]);
    })->create();
