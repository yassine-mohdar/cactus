<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Services\IntegrationConnectionTestService;
use Illuminate\Http\Request;

class IntegrationSettingController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly IntegrationConnectionTestService $tester,
    ) {}

    public function index()
    {
        IntegrationSetting::ensureDefaultsExist();

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
        $validated = $request->validate($this->rulesFor($integration));

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

    public function test(IntegrationSetting $integration)
    {
        $result = $this->tester->test($integration);

        return redirect()
            ->route('admin.notifications.integrations.index')
            ->with($result['status'], $result['message']);
    }

    private function rulesFor(IntegrationSetting $integration): array
    {
        $enabledRule = $integration->is_enabled ? 'nullable' : 'required_if:is_enabled,1';

        return match ($integration->provider) {
            'smtp' => [
                'is_enabled' => 'nullable|boolean',
                'credentials' => 'nullable|array',
                'credentials.host' => "{$enabledRule}|string|max:255",
                'credentials.port' => "{$enabledRule}|integer|min:1|max:65535",
                'credentials.username' => 'nullable|string|max:255',
                'credentials.password' => 'nullable|string|max:255',
                'credentials.encryption' => 'nullable|in:tls,ssl,',
                'credentials.from_address' => "{$enabledRule}|email|max:150",
                'credentials.from_name' => 'nullable|string|max:150',
            ],
            'twilio' => [
                'is_enabled' => 'nullable|boolean',
                'credentials' => 'nullable|array',
                'credentials.account_sid' => "{$enabledRule}|string|max:100",
                'credentials.auth_token' => "{$enabledRule}|string|max:255",
                'credentials.from_number' => "{$enabledRule}|string|max:30",
            ],
            'whatsapp_api' => [
                'is_enabled' => 'nullable|boolean',
                'credentials' => 'nullable|array',
                'credentials.access_token' => "{$enabledRule}|string|max:255",
                'credentials.phone_number_id' => "{$enabledRule}|string|max:100",
                'credentials.business_account_id' => "{$enabledRule}|string|max:100",
                'credentials.api_version' => 'nullable|string|max:20',
            ],
            default => [
                'is_enabled' => 'nullable|boolean',
                'credentials' => 'nullable|array',
            ],
        };
    }
}
