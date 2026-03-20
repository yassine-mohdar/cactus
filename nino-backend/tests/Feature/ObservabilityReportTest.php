<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Models\PaymentLog;
use App\Modules\Reports\Models\ObservabilityEvent;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_observability_report_renders_existing_incidents_and_queue_health(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        Permission::findOrCreate('reports.viewAny', 'web');
        $staffUser->givePermissionTo('reports.viewAny');

        NotificationLog::create([
            'event' => NotificationEvent::PAYMENT_FAILED,
            'channel' => NotificationChannel::EMAIL,
            'status' => NotificationStatus::FAILED,
            'recipient' => 'ops@ninoworld.test',
            'subject' => 'Payment failed',
            'body' => 'Gateway rejected the payment.',
            'error_message' => 'SMTP timeout',
            'attempts' => 2,
            'max_attempts' => 3,
        ]);

        PaymentLog::create([
            'gateway' => 'stripe',
            'direction' => 'inbound',
            'event_type' => 'webhook',
            'is_successful' => false,
            'error_message' => 'Signature mismatch',
            'error_code' => 'INVALID_SIGNATURE',
            'response_payload' => ['status' => 'error'],
        ]);

        DB::table('jobs')->insert([
            'queue' => 'notifications',
            'payload' => json_encode(['displayName' => 'App\\Modules\\Notifications\\Jobs\\SendNotificationJob']),
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(10)->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\ImportOrders']),
            'exception' => "RuntimeException: Queue worker exploded\n#0 /srv/app",
            'failed_at' => now(),
        ]);

        ObservabilityEvent::create([
            'event_type' => 'slow_request',
            'source' => 'http',
            'severity' => 'warning',
            'name' => 'admin.reports.orders',
            'route_name' => 'admin.reports.orders',
            'method' => 'GET',
            'url' => 'http://localhost/admin/reports/orders',
            'status_code' => 200,
            'duration_ms' => 1840,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('admin.reports.observability'));

        $response->assertOk();
        $response->assertSee('Observability');
        $response->assertSee('SMTP timeout');
        $response->assertSee('stripe');
        $response->assertSee('ImportOrders');
        $response->assertSee('1,840 ms');
    }

    public function test_observability_report_gracefully_renders_when_optional_tables_are_missing(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        Permission::findOrCreate('reports.viewAny', 'web');
        $staffUser->givePermissionTo('reports.viewAny');

        Schema::dropIfExists('observability_events');

        $response = $this->actingAs($staffUser)->get(route('admin.reports.observability'));

        $response->assertOk();
        $response->assertSee('Observability');
        $response->assertSee('Slow Operations');
    }

    public function test_slow_web_request_is_logged_into_observability_events(): void
    {
        config(['observability.slow_request_threshold_ms' => 1]);

        Route::middleware('web')->get('/__test/observability/slow', function () {
            usleep(15_000);

            return response('slow route complete');
        })->name('test.observability.slow');

        $response = $this->get('/__test/observability/slow');

        $response->assertOk();

        $event = ObservabilityEvent::query()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame('slow_request', $event->event_type);
        $this->assertSame('test.observability.slow', $event->route_name);
        $this->assertGreaterThanOrEqual(1, $event->duration_ms);
    }

    public function test_payment_callback_exceptions_are_logged_to_payment_logs(): void
    {
        $this->app->instance(CmiPaymentGateway::class, new class extends CmiPaymentGateway
        {
            public function verifyPayment(Request $request): PaymentResponse
            {
                throw new \RuntimeException('CMI callback exploded');
            }

            public function handleWebhook(Request $request): PaymentResponse
            {
                throw new \RuntimeException('CMI webhook exploded');
            }
        });

        $response = $this->post(route('payment.cmi.webhook'), [
            'oid' => 'ORD-OBS-001',
            'status' => 'failed',
        ]);

        $response->assertOk();
        $response->assertSee('FAILURE');

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'cmi',
            'event_type' => 'error',
            'error_code' => 'WEBHOOK_EXCEPTION',
        ]);
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
