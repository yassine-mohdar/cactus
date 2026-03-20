<?php

namespace App\Http\Middleware;

use App\Modules\Settings\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplySecuritySettings
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $lifetime = (int) $this->settings->get('security', 'session_lifetime', config('session.lifetime', 120));

            config([
                'session.lifetime' => max(5, min(1440, $lifetime)),
                'session.encrypt' => true,
                'session.http_only' => true,
            ]);
        } catch (Throwable) {
            config([
                'session.encrypt' => true,
                'session.http_only' => true,
            ]);
        }

        return $next($request);
    }
}
