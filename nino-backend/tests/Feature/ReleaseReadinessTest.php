<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\LocalDemoDataSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_local_demo_data_seeder_creates_useful_admin_and_operational_records(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(LocalDemoDataSeeder::class);

        $supportAgent = User::query()->where('email', 'support.agent@ninoworld.com')->firstOrFail();
        $shippingAgent = User::query()->where('email', 'shipping.agent@ninoworld.com')->firstOrFail();
        $financeManager = User::query()->where('email', 'finance.manager@ninoworld.com')->firstOrFail();
        $customer = User::query()->where('email', 'qa.customer@example.test')->firstOrFail();

        $this->assertTrue($supportAgent->hasRole('Customer Support Agent'));
        $this->assertTrue($shippingAgent->hasRole('Shipping Agent'));
        $this->assertTrue($financeManager->hasRole('Finance Manager'));
        $this->assertTrue((bool) $customer->community_auto_invite_to_default_group);

        $this->assertDatabaseHas('shipping_methods', ['slug' => 'standard-delivery']);
        $this->assertDatabaseHas('orders', ['reference_number' => 'ORD-DEMO-1001']);
        $this->assertDatabaseHas('payment_transactions', ['reference' => 'TXN-DEMO-1001']);
        $this->assertDatabaseHas('support_issues', ['reference' => 'TKT-DEMO-1001']);
    }

    public function test_application_has_no_pending_migrations_after_refresh_database(): void
    {
        $migrator = app('migrator');
        $paths = array_values(array_unique([
            database_path('migrations'),
            ...$migrator->paths(),
        ]));

        $files = $migrator->getMigrationFiles($paths);
        $ran = $migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        $this->assertSame([], $pending, 'Pending migrations remain after the test database was migrated.');
    }

    public function test_env_example_documents_current_release_variables(): void
    {
        $envExample = File::get(base_path('.env.example'));

        foreach ([
            'ADMIN_THEME=nino-v2',
            'DB_SOCKET=',
            'SESSION_TABLE=sessions',
            'DB_CACHE_TABLE=cache',
            'DB_QUEUE_TABLE=jobs',
            'DB_QUEUE_RETRY_AFTER=90',
            'OBS_SLOW_REQUEST_THRESHOLD_MS=1200',
            'PERF_DASHBOARD_CACHE_TTL_SECONDS=600',
            'PERF_SETTINGS_CACHE_TTL_SECONDS=3600',
        ] as $expectedLine) {
            $this->assertStringContainsString($expectedLine, $envExample);
        }
    }

    public function test_core_admin_release_surfaces_render_safely_on_fresh_data(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('settings.manage', 'web');
        Permission::findOrCreate('finance.manage_gateways', 'web');
        Permission::findOrCreate('reports.viewAny', 'web');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        $staffUser->givePermissionTo([
            'settings.manage',
            'finance.manage_gateways',
            'reports.viewAny',
        ]);

        $this->assertDatabaseCount('gateway_settings', 0);
        $this->assertDatabaseCount('integration_settings', 0);

        $dashboardResponse = $this->actingAs($staffUser)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Control center');

        $settingsResponse = $this->actingAs($staffUser)->get(route('admin.settings.index'));
        $settingsResponse->assertOk();
        $settingsResponse->assertSee('General');

        $gatewaysResponse = $this->actingAs($staffUser)->get(route('admin.gateways.index'));
        $gatewaysResponse->assertOk();
        $gatewaysResponse->assertSee('Payment Gateways');
        $this->assertSame(4, (int) \App\Modules\Payments\Models\GatewaySetting::query()->count());

        $integrationsResponse = $this->actingAs($staffUser)->get(route('admin.notifications.integrations.index'));
        $integrationsResponse->assertOk();
        $integrationsResponse->assertSee('Channel Integrations');
        $this->assertSame(3, (int) \App\Modules\Notifications\Models\IntegrationSetting::query()->count());

        Schema::dropIfExists('observability_events');

        $observabilityResponse = $this->actingAs($staffUser)->get(route('admin.reports.observability'));
        $observabilityResponse->assertOk();
        $observabilityResponse->assertSee('Observability');
        $observabilityResponse->assertSee('Slow Operations');
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
