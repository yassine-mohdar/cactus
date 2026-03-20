<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Notifications\Models\IntegrationSetting;
use Illuminate\Http\Request;

class IntegrationSettingController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function index()
    {
        // Auto-seed default providers if they don't exist
        $this->seedDefaults();

        $integrations = IntegrationSetting::orderBy('provider')->get();

        $stats = [
            'channels' => $integrations->count(),
            'enabled' => $integrations->where('is_enabled', true)->count(),
            'configured' => $integrations->filter(fn (IntegrationSetting $integration) => $integration->isConfigured())->count(),
            'credential_fields' => $integrations->sum(fn (IntegrationSetting $integration) => $integration->filledCredentialCount()),
        ];

        return view('admin.notifications.integrations.index', compact('integrations', 'stats'));
    }

    public function update(Request $request, IntegrationSetting $integration)
    {
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'credentials' => 'nullable|array',
        ]);

        $before = [
            'is_enabled' => $integration->is_enabled,
            'credentials' => $integration->credentials ?? [],
        ];

        // Smart merge: preserve existing secrets when masked values are submitted
        $newCredentials = $validated['credentials'] ?? [];
        $existingCredentials = $integration->credentials ?? [];

        foreach ($newCredentials as $key => $value) {
            if ($value === '*******' || $value === '********') {
                $newCredentials[$key] = $existingCredentials[$key] ?? '';
            }
        }

        $integration->update([
            'is_enabled' => $request->has('is_enabled'),
            'credentials' => $newCredentials,
        ]);

        $this->audit->log(
            action: 'notifications.integration.updated',
            target: $integration,
            oldValues: $before,
            newValues: [
                'is_enabled' => $integration->is_enabled,
                'credentials' => $integration->credentials ?? [],
            ],
            notes: "{$integration->name} integration settings updated",
            context: [
                'provider' => $integration->provider,
                'source' => 'integration_setting_controller',
            ],
        );

        return redirect()->route('admin.notifications.integrations.index')
            ->with('success', "{$integration->name} settings updated.");
    }

    private function seedDefaults(): void
    {
        foreach (IntegrationSetting::defaultDefinitions() as $default) {
            IntegrationSetting::firstOrCreate(
                ['provider' => $default['provider']],
                $default
            );
        }
    }
}
