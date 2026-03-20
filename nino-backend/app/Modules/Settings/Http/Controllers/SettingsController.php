<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Settings\Services\SettingsDefinitions;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Show settings page with tab navigation.
     */
    public function index(Request $request)
    {
        $this->authorize('settings.manage');

        $activeTab = $request->get('tab', 'general');
        $groups    = SettingsDefinitions::groups();

        // Validate tab exists
        if (!array_key_exists($activeTab, $groups)) {
            $activeTab = 'general';
        }

        $fields = SettingsDefinitions::forGroup($activeTab);
        $values = $this->settings->group($activeTab);
        
        $integrations = [];
        if ($activeTab === 'mail') {
            IntegrationSetting::ensureDefaultsExist();
            $integrations = IntegrationSetting::orderBy('provider')->get();
        }

        return view('admin.settings.index', compact('groups', 'activeTab', 'fields', 'values', 'integrations'));
    }

    /**
     * Save settings for a specific group/tab.
     */
    public function update(Request $request)
    {
        $this->authorize('settings.manage');

        $group  = $request->input('group', 'general');
        $groups = SettingsDefinitions::groups();

        if (!array_key_exists($group, $groups)) {
            return back()->with('error', 'Invalid settings group.');
        }

        // Validate
        $rules  = SettingsDefinitions::rules($group);
        $validated = $request->validate($rules);
        $fields = SettingsDefinitions::forGroup($group);
        $beforeValues = $this->settings->group($group);

        // Save each field
        foreach ($fields as $field) {
            $key     = $field['key'];
            $value   = $validated[$key] ?? null;

            // Skip null secrets to avoid overwriting with empty
            if ($field['type'] === 'secret' && ($value === null || $value === '')) {
                continue;
            }

            $this->settings->set(
                $group,
                $key,
                $value,
                $field['type'],
                $field['encrypt'] ?? false,
            );
        }

        $this->audit->log(
            action: "settings.{$group}.updated",
            oldValues: $this->onlyTrackedFields($beforeValues, $fields),
            newValues: $this->onlyTrackedFields($this->settings->group($group), $fields),
            notes: ucfirst($group) . ' settings updated',
            context: [
                'group' => $group,
                'changed_keys' => array_keys($validated),
                'source' => 'settings_controller',
            ],
            targetLabel: 'Settings: ' . ($groups[$group] ?? ucfirst($group)),
        );

        return redirect()
            ->route('admin.settings.index', ['tab' => $group])
            ->with('success', ucfirst($group) . ' settings saved successfully.');
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function onlyTrackedFields(array $values, array $fields): array
    {
        $filtered = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $filtered[$key] = $values[$key] ?? null;
        }

        return $filtered;
    }
}
