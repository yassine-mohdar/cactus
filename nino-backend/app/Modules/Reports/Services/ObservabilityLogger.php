<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Models\ObservabilityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class ObservabilityLogger
{
    public function logSlowRequest(Request $request, int $durationMs, int $statusCode): void
    {
        $route = $request->route();
        $name = $route?->getName() ?: sprintf('%s %s', $request->method(), $request->path());

        $payload = [
            'event_type' => 'slow_request',
            'source' => 'http',
            'severity' => $durationMs >= 3000 ? 'critical' : 'warning',
            'name' => $name,
            'route_name' => $route?->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'context' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route_uri' => is_object($route) && method_exists($route, 'uri') ? $route->uri() : null,
            ],
            'occurred_at' => now(),
        ];

        Log::warning('Slow HTTP operation detected', [
            'event_type' => 'slow_request',
            'route_name' => $payload['route_name'],
            'method' => $payload['method'],
            'url' => $payload['url'],
            'duration_ms' => $durationMs,
            'status_code' => $statusCode,
        ]);

        try {
            ObservabilityEvent::create($payload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
