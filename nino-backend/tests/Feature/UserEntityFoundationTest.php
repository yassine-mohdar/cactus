<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserEntityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_provides_core_defaults_and_state_helpers(): void
    {
        $staff = User::factory()->create();
        $inactiveCustomer = User::factory()->customer()->inactive()->create();
        $suspendedCustomer = User::factory()->customer()->suspended()->create();

        $this->assertTrue($staff->isStaff());
        $this->assertTrue($staff->isActive());
        $this->assertNotEmpty($staff->name);
        $this->assertNotEmpty($staff->first_name);
        $this->assertNotEmpty($staff->last_name);

        $this->assertTrue($inactiveCustomer->isCustomer());
        $this->assertTrue($inactiveCustomer->isInactive());
        $this->assertFalse($inactiveCustomer->isActive());

        $this->assertTrue($suspendedCustomer->isCustomer());
        $this->assertTrue($suspendedCustomer->isSuspended());
    }

    public function test_create_new_user_creates_an_active_customer_from_legacy_name_input(): void
    {
        $user = app(CreateNewUser::class)->create([
            'name' => 'Nadia Benzakour',
            'email' => 'nadia@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertTrue($user->isCustomer());
        $this->assertTrue($user->isActive());
        $this->assertSame('Nadia', $user->first_name);
        $this->assertSame('Benzakour', $user->last_name);
        $this->assertSame('Nadia Benzakour', $user->name);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_create_new_user_accepts_profile_fields_and_normalizes_username(): void
    {
        $user = app(CreateNewUser::class)->create([
            'name' => '',
            'first_name' => '  Yassine ',
            'last_name' => ' Bennani  ',
            'username' => '  YBennani ',
            'email' => 'yassine@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertSame(User::TYPE_CUSTOMER, $user->type);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertSame('Yassine', $user->first_name);
        $this->assertSame('Bennani', $user->last_name);
        $this->assertSame('Yassine Bennani', $user->name);
        $this->assertSame('ybennani', $user->username);
    }

    public function test_customer_profile_updates_keep_name_and_username_in_sync(): void
    {
        $customer = User::factory()->customer()->create([
            'name' => 'Legacy Customer',
            'first_name' => 'Legacy',
            'last_name' => 'Customer',
            'marketing_opt_in' => false,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($customer)->putJson(route('api.customer.profile.update'), [
            'first_name' => '  Sara ',
            'last_name' => ' El Idrissi  ',
            'username' => '  Sara.ID ',
            'marketing_opt_in' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'Sara El Idrissi')
            ->assertJsonPath('user.username', 'sara.id')
            ->assertJsonPath('user.marketing_opt_in', true);

        $customer->refresh();

        $this->assertSame('Sara', $customer->first_name);
        $this->assertSame('El Idrissi', $customer->last_name);
        $this->assertSame('Sara El Idrissi', $customer->name);
        $this->assertSame('sara.id', $customer->username);
        $this->assertTrue($customer->marketing_opt_in);
        $this->assertTrue($customer->isCustomer());
        $this->assertTrue($customer->isActive());
    }

    public function test_customer_profile_can_store_and_replace_avatar_files(): void
    {
        Storage::fake('public');

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('api.customer.profile.update'), [
                'avatar' => UploadedFile::fake()->image('avatar-one.png'),
            ])
            ->assertOk();

        $firstAvatarPath = $customer->fresh()->avatar;

        $this->assertNotNull($firstAvatarPath);
        Storage::disk('public')->assertExists($firstAvatarPath);

        $this->actingAs($customer->fresh())
            ->put(route('api.customer.profile.update'), [
                'avatar' => UploadedFile::fake()->image('avatar-two.png'),
            ])
            ->assertOk();

        $customer->refresh();

        $this->assertNotSame($firstAvatarPath, $customer->avatar);
        Storage::disk('public')->assertMissing($firstAvatarPath);
        Storage::disk('public')->assertExists($customer->avatar);
    }

    public function test_staff_and_customer_auth_surfaces_record_last_login_metadata(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'last-login-customer@example.test',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $staff = User::factory()->staff()->create([
            'email' => 'last-login-staff@example.test',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.25'])
            ->post(route('customer.login.store'), [
                'email' => $customer->email,
                'password' => 'SecretPass123!',
            ])
            ->assertRedirect(route('customer.account.home'));

        $this->post(route('customer.logout'))->assertRedirect(route('customer.login'));

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.44'])
            ->post(route('login'), [
                'email' => $staff->email,
                'password' => 'SecretPass123!',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotNull($customer->fresh()->last_login_at);
        $this->assertSame('198.51.100.25', $customer->fresh()->last_login_ip);
        $this->assertNotNull($staff->fresh()->last_login_at);
        $this->assertSame('198.51.100.44', $staff->fresh()->last_login_ip);
    }
}
