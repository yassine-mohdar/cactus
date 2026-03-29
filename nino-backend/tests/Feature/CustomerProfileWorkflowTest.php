<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile_picture_name_fields_and_email(): void
    {
        Storage::fake('public');

        $customer = User::factory()->customer()->create([
            'email' => 'legacy@example.test',
            'email_verified_at' => now(),
            'first_name' => 'Legacy',
            'last_name' => 'Customer',
        ]);

        $response = $this->actingAs($customer)->put(route('api.customer.profile.update'), [
            'first_name' => '  Nadia ',
            'last_name' => ' Bennani  ',
            'email' => '  Nadia.Bennani@Example.test ',
            'avatar' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.first_name', 'Nadia')
            ->assertJsonPath('user.last_name', 'Bennani')
            ->assertJsonPath('user.name', 'Nadia Bennani')
            ->assertJsonPath('user.email', 'nadia.bennani@example.test');

        $customer->refresh();

        $this->assertSame('Nadia', $customer->first_name);
        $this->assertSame('Bennani', $customer->last_name);
        $this->assertSame('Nadia Bennani', $customer->name);
        $this->assertSame('nadia.bennani@example.test', $customer->email);
        $this->assertNull($customer->email_verified_at);
        $this->assertNotNull($customer->avatar);
        Storage::disk('public')->assertExists($customer->avatar);
    }

    public function test_customer_profile_email_must_be_unique(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'profile-owner@example.test',
        ]);

        User::factory()->customer()->create([
            'email' => 'used@example.test',
        ]);

        $this->actingAs($customer)
            ->putJson(route('api.customer.profile.update'), [
                'email' => 'used@example.test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_can_change_password_without_losing_profile_identity_data(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'profile-password@example.test',
            'password' => Hash::make('CurrentPass123!'),
            'first_name' => 'Amina',
            'last_name' => 'Touhami',
        ]);

        $this->actingAs($customer)
            ->putJson(route('api.customer.password.update'), [
                'current_password' => 'CurrentPass123!',
                'password' => 'UpdatedPass123!',
                'password_confirmation' => 'UpdatedPass123!',
            ])
            ->assertOk();

        $customer->refresh();

        $this->assertTrue(Hash::check('UpdatedPass123!', $customer->password));
        $this->assertSame('Amina', $customer->first_name);
        $this->assertSame('Touhami', $customer->last_name);
        $this->assertSame('profile-password@example.test', $customer->email);
    }
}
