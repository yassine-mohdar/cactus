<?php

namespace App\Http\Middleware;

use App\Modules\Reports\Services\ObservabilityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogSlowOperations
{
    public function __construct(
        private readonly ObservabilityLogger $observabilityLogger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $threshold = max(1, (int) config('observability.slow_request_threshold_ms', 1200));

        if ($durationMs >= $threshold) {
            $this->observabilityLogger->logSlowRequest($request, $durationMs, $response->getStatusCode());
        }

        return $response;
    }
}
