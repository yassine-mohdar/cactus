<?php

namespace App\Http\Middleware;

use App\Modules\Settings\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        private readonly FeatureFlagService $featureFlags,
    ) {}

    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        foreach ($features as $feature) {
            if (! $this->featureFlags->isEnabled($feature)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
