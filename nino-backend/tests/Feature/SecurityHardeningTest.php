<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Notifications\CustomerResetPasswordNotification;
use App\Modules\IAM\Services\StaffAccessSetupService;
use App\Modules\Settings\Models\Setting;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_staff_is_redirected_to_two_factor_setup_when_forced_two_factor_is_enabled(): void
    {
        $this->storeSecuritySetting('force_2fa', true, 'boolean');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.security.two-factor.setup'));
    }

    public function test_two_factor_setup_reveals_qr_after_password_confirmation(): void
    {
        $this->activateAdminTheme('nino-v2');
        $this->storeSecuritySetting('force_2fa', true, 'boolean');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($staffUser)->post(route('admin.security.two-factor.prepare'), [
            'current_password' => 'password',
        ]);

        $response->assertRedirect(route('admin.security.two-factor.setup'));

        $staffUser->refresh();

        $this->assertNotNull($staffUser->two_factor_secret);
        $this->assertNull($staffUser->two_factor_confirmed_at);

        $setupResponse = $this->actingAs($staffUser)
            ->withSession(['security.two_factor_setup.password_confirmed_at' => now()->timestamp])
            ->get(route('admin.security.two-factor.setup'));

        $setupResponse->assertOk();
        $setupResponse->assertSee('Authenticator QR');
        $setupResponse->assertSee('Recovery codes');
    }

    public function test_confirmed_two_factor_user_is_redirected_to_challenge_during_custom_login(): void
    {
        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('NINOWORLD-2FA-SECRET'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode([
                'recovery-code-one',
                'recovery-code-two',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->from(route('login'))->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->assertSame($user->getKey(), session('login.id'));
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }

    public function test_security_settings_middleware_applies_session_lifetime_and_encryption(): void
    {
        $this->storeSecuritySetting('session_lifetime', 45, 'integer');

        Route::middleware('web')->get('/__test/security/session', function () {
            return response()->json([
                'lifetime' => config('session.lifetime'),
                'encrypt' => config('session.encrypt'),
                'http_only' => config('session.http_only'),
            ]);
        });

        $response = $this->get('/__test/security/session');

        $response->assertOk();
        $response->assertJson([
            'lifetime' => 45,
            'encrypt' => true,
            'http_only' => true,
        ]);
    }

    public function test_sensitive_inputs_are_not_flashed_back_after_validation_failure(): void
    {
        Route::middleware('web')->post('/__test/security/redaction', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'name' => ['required', 'string'],
            ]);

            return response()->noContent();
        });

        $response = $this->from('/__test/security/redaction')->post('/__test/security/redaction', [
            'comment' => 'keep-me',
            'current_password' => 'secret-password',
            'api_key' => 'provider-key',
            'recovery_code' => 'recovery-secret',
            'two_factor_secret' => 'two-factor-secret',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('keep-me', session()->getOldInput('comment'));
        $this->assertNull(session()->getOldInput('current_password'));
        $this->assertNull(session()->getOldInput('api_key'));
        $this->assertNull(session()->getOldInput('recovery_code'));
        $this->assertNull(session()->getOldInput('two_factor_secret'));
    }

    public function test_password_policy_settings_are_enforced_for_customer_password_reset(): void
    {
        Notification::fake();

        $this->storeSecuritySetting('password_min_length', 12, 'integer');
        $this->storeSecuritySetting('password_require_mixed_case', true, 'boolean');
        $this->storeSecuritySetting('password_require_numbers', true, 'boolean');
        $this->storeSecuritySetting('password_require_symbols', true, 'boolean');

        $customer = User::factory()->create([
            'email' => 'strict.customer@example.test',
            'password' => Hash::make('OldPass123!'),
            'type' => 'customer',
            'status' => 'active',
        ]);

        $this->post(route('customer.password.email'), [
            'email' => $customer->email,
        ])->assertSessionHas('status');

        $resetUrl = null;

        Notification::assertSentTo(
            $customer,
            CustomerResetPasswordNotification::class,
            function (CustomerResetPasswordNotification $notification) use (&$resetUrl): bool {
                $resetUrl = $notification->resetUrl;

                return true;
            }
        );

        $token = basename((string) parse_url((string) $resetUrl, PHP_URL_PATH));

        $this->from(route('customer.password.reset', ['token' => $token, 'email' => $customer->email]))
            ->post(route('customer.password.reset.store'), [
                'token' => $token,
                'email' => $customer->email,
                'password' => 'weakpass123',
                'password_confirmation' => 'weakpass123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_password_policy_settings_are_enforced_for_staff_access_setup(): void
    {
        $this->storeSecuritySetting('password_min_length', 12, 'integer');
        $this->storeSecuritySetting('password_require_mixed_case', true, 'boolean');
        $this->storeSecuritySetting('password_require_numbers', true, 'boolean');
        $this->storeSecuritySetting('password_require_symbols', true, 'boolean');

        $staff = User::factory()->create([
            'email' => 'strict.staff@example.test',
            'type' => 'staff',
            'status' => 'inactive',
        ]);

        $setupUrl = app(StaffAccessSetupService::class)->createSetupUrl($staff, 'security_test');

        $token = basename((string) parse_url($setupUrl, PHP_URL_PATH));

        $this->from($setupUrl)
            ->post(route('staff.access.setup.store'), [
                'email' => $staff->email,
                'token' => $token,
                'password' => 'weakpass123',
                'password_confirmation' => 'weakpass123',
            ])
            ->assertSessionHasErrors('password');
    }

    private function storeSecuritySetting(string $key, mixed $value, string $type): void
    {
        Setting::updateOrCreate(
            ['group' => 'security', 'key' => $key],
            [
                'value' => Setting::prepareValue($value, $type, false),
                'type' => $type,
                'is_encrypted' => false,
            ],
        );
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
