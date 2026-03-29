<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customers\Models\Address;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerAccountManagementScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
        Storage::fake(config('media.disk', 'public'));
    }

    public function test_customer_can_render_and_update_profile_edit_screen(): void
    {
        $customer = User::factory()->customer()->create([
            'password' => Hash::make('CurrentPass123!'),
            'first_name' => 'Nadia',
            'last_name' => 'Old',
            'username' => 'nadia.old',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.account.profile.edit'))
            ->assertOk()
            ->assertSeeText('Edit profile');

        $this->actingAs($customer)
            ->from(route('customer.account.profile.edit'))
            ->put(route('customer.account.profile.update'), [
                'first_name' => 'Nadia',
                'last_name' => 'Updated',
                'email' => 'nadia.updated@example.test',
                'username' => 'nadia.updated',
                'marketing_opt_in' => '1',
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect(route('customer.account.profile.edit'))
            ->assertSessionHas('success');

        $customer->refresh();

        $this->assertSame('Updated', $customer->last_name);
        $this->assertSame('nadia.updated@example.test', $customer->email);
        $this->assertSame('nadia.updated', $customer->username);
        $this->assertTrue((bool) $customer->marketing_opt_in);
        $this->assertNotNull($customer->avatar);
    }

    public function test_customer_can_render_and_manage_addresses_screen(): void
    {
        $customer = User::factory()->customer()->create([
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
        ]);

        $address = $customer->addresses()->create($this->addressPayload(Address::TYPE_SHIPPING, true));

        $this->actingAs($customer)
            ->get(route('customer.account.addresses.index'))
            ->assertOk()
            ->assertSeeText('Manage addresses')
            ->assertSee('value="12 Atlas Street"', false);

        $this->actingAs($customer)
            ->from(route('customer.account.addresses.index'))
            ->post(route('customer.account.addresses.store'), $this->addressPayload(Address::TYPE_BILLING, false))
            ->assertRedirect(route('customer.account.addresses.index'))
            ->assertSessionHas('success');

        $this->actingAs($customer)
            ->from(route('customer.account.addresses.index'))
            ->put(route('customer.account.addresses.update', $address), array_merge(
                $this->addressPayload(Address::TYPE_SHIPPING, true),
                ['city' => 'Rabat', 'postal_code' => '10000']
            ))
            ->assertRedirect(route('customer.account.addresses.index'))
            ->assertSessionHas('success');

        $customer->refresh();

        $this->assertSame('Rabat', $address->fresh()->city);
        $this->assertCount(2, $customer->addresses);
    }

    public function test_customer_can_change_password_from_profile_screen(): void
    {
        $customer = User::factory()->customer()->create([
            'password' => Hash::make('CurrentPass123!'),
        ]);

        $this->actingAs($customer)
            ->from(route('customer.account.profile.edit'))
            ->put(route('customer.account.password.update'), [
                'current_password' => 'CurrentPass123!',
                'password' => 'NewProfilePass123!',
                'password_confirmation' => 'NewProfilePass123!',
            ])
            ->assertRedirect(route('customer.account.profile.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewProfilePass123!', $customer->fresh()->password));
    }

    private function addressPayload(string $type, bool $isDefault): array
    {
        return [
            'type' => $type,
            'is_default' => $isDefault,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'company' => 'NinoWorld',
            'address_line_1' => '12 Atlas Street',
            'address_line_2' => null,
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
            'phone' => '+212600000000',
        ];
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
