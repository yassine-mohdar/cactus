<?php

namespace App\Http\Middleware;

use App\Modules\Settings\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplyGeneralSettings
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = (string) $this->settings->get('general', 'timezone', config('app.timezone', 'UTC'));
            $locale = (string) $this->settings->get('general', 'locale', config('app.locale', 'en'));

            if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            if ($locale !== '') {
                config(['app.locale' => $locale]);
                app()->setLocale($locale);
            }
        } catch (Throwable) {
            // Fail open to the framework defaults if settings storage is unavailable.
        }

        return $next($request);
    }
}
