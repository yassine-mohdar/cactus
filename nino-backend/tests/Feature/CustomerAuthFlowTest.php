<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customers\Services\CustomerAccountService;
use App\Modules\IAM\Notifications\CustomerPasswordSetupNotification;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class CustomerAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_active_customer_can_login_view_account_home_and_logout(): void
    {
        $customer = User::factory()->create([
            'name' => 'Customer Login',
            'first_name' => 'Customer',
            'last_name' => 'Login',
            'email' => 'customer.login@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->post(route('customer.login.store'), [
            'email' => $customer->email,
            'password' => 'SecretPass123!',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('customer.account.home'));
        $this->assertAuthenticatedAs($customer);

        $this->get(route('customer.account.home'))
            ->assertOk()
            ->assertSeeText($customer->email);

        $this->post(route('customer.logout'))
            ->assertRedirect(route('customer.login'));

        $this->assertGuest();
    }

    public function test_staff_cannot_use_customer_auth_surface_and_customers_cannot_enter_admin_dashboard(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff.login@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $customer = User::factory()->create([
            'email' => 'customer.boundary@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => 'customer',
            'status' => 'active',
        ]);

        $this->post(route('customer.login.store'), [
            'email' => $staff->email,
            'password' => 'SecretPass123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('customer.account.home'));

        auth()->logout();

        $this->actingAs($staff)
            ->get(route('customer.account.home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_checkout_created_customer_receives_password_setup_link_and_can_complete_setup(): void
    {
        Notification::fake();

        $customer = app(CustomerAccountService::class)->createFromCheckout([
            'email' => 'setup.customer@example.test',
            'first_name' => 'Setup',
            'last_name' => 'Customer',
        ]);

        $setupUrl = null;

        Notification::assertSentTo(
            $customer,
            CustomerPasswordSetupNotification::class,
            function (CustomerPasswordSetupNotification $notification) use (&$setupUrl): bool {
                $setupUrl = $notification->setupUrl;

                return str_contains($notification->setupUrl, '/account/password/setup/');
            }
        );

        $this->assertNotNull($setupUrl);

        $this->get($setupUrl)
            ->assertOk()
            ->assertSeeText($customer->email);

        $token = basename((string) parse_url($setupUrl, PHP_URL_PATH));

        $this->post(route('customer.password.setup.store'), [
            'email' => $customer->email,
            'token' => $token,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect(route('customer.account.home'));

        $this->assertAuthenticatedAs($customer->fresh());
        $this->assertTrue(Hash::check('NewSecurePass123!', $customer->fresh()->password));
    }

    public function test_checkout_auto_account_generation_rejects_existing_non_customer_identity(): void
    {
        User::factory()->create([
            'email' => 'staff.identity@example.test',
            'type' => 'staff',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Checkout auto-account generation can only reuse existing customer identities.');

        app(CustomerAccountService::class)->createFromCheckout([
            'email' => 'staff.identity@example.test',
            'first_name' => 'Staff',
            'last_name' => 'Identity',
        ]);
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
