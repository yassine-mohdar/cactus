<?php

namespace App\Http\Middleware;

use App\Modules\Settings\Services\FeatureFlagService;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use App\Modules\Settings\Services\SystemSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplySystemSettings
{
    public function __construct(
        private readonly SystemSettingsService $systemSettings,
        private readonly MediaStorageSettingsService $mediaSettings,
        private readonly FeatureFlagService $featureFlags,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            config([
                'media.default_disk' => $this->mediaSettings->defaultDisk(),
                'media.directories.avatars' => $this->mediaSettings->avatarDirectory(),
                'media.directories.catalog' => $this->mediaSettings->catalogDirectory(),
                'features.promotions' => $this->featureFlags->isEnabled('promotions'),
                'features.cms' => $this->featureFlags->isEnabled('cms'),
                'features.community' => $this->featureFlags->isEnabled('community'),
            ]);

            if (
                $this->systemSettings->maintenanceModeEnabled()
                && ! $this->shouldBypassMaintenance($request)
            ) {
                return response($this->systemSettings->maintenanceMessage(), 503);
            }
        } catch (Throwable) {
            // Fail open to avoid locking the application when settings storage is unavailable.
        }

        return $next($request);
    }

    private function shouldBypassMaintenance(Request $request): bool
    {
        if ($request->is('login') || $request->is('logout') || $request->is('up')) {
            return true;
        }

        $user = $request->user();

        return $this->systemSettings->maintenanceBypassStaff()
            && $user
            && $user->type === 'staff';
    }
}
