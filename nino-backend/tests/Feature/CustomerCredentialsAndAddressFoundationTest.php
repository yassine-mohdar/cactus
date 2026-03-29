<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customers\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerCredentialsAndAddressFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_username_through_profile_surface(): void
    {
        $customer = User::factory()->customer()->create([
            'username' => 'legacy.username',
        ]);

        $this->actingAs($customer)
            ->putJson(route('api.customer.profile.update'), [
                'username' => '  New.Handle  ',
            ])
            ->assertOk()
            ->assertJsonPath('user.username', 'new.handle');

        $customer->refresh();

        $this->assertSame('new.handle', $customer->username);
    }

    public function test_customer_can_change_password_from_customer_profile_api(): void
    {
        $customer = User::factory()->customer()->create([
            'password' => Hash::make('CurrentPass123!'),
        ]);

        $this->actingAs($customer)
            ->putJson(route('api.customer.password.update'), [
                'current_password' => 'CurrentPass123!',
                'password' => 'UpdatedPass123!',
                'password_confirmation' => 'UpdatedPass123!',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $this->assertTrue(Hash::check('UpdatedPass123!', $customer->fresh()->password));
    }

    public function test_customer_can_create_billing_address(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson(route('api.customer.addresses.store'), $this->addressPayload(Address::TYPE_BILLING))
            ->assertCreated()
            ->assertJsonPath('address.type', Address::TYPE_BILLING);

        $address = $customer->fresh()->addresses()->latest('id')->firstOrFail();

        $this->assertTrue($address->isBilling());
        $this->assertFalse($address->isShipping());
    }

    public function test_customer_can_create_shipping_address(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson(route('api.customer.addresses.store'), $this->addressPayload(Address::TYPE_SHIPPING))
            ->assertCreated()
            ->assertJsonPath('address.type', Address::TYPE_SHIPPING);

        $address = $customer->fresh()->addresses()->latest('id')->firstOrFail();

        $this->assertTrue($address->isShipping());
        $this->assertFalse($address->isBilling());
    }

    public function test_customer_can_update_and_delete_an_address(): void
    {
        $customer = User::factory()->customer()->create();
        $address = $customer->addresses()->create($this->addressPayload(Address::TYPE_SHIPPING));

        $this->actingAs($customer)
            ->putJson(route('api.customer.addresses.update', $address), [
                'type' => Address::TYPE_SHIPPING,
                'is_default' => true,
                'first_name' => ' Nadia ',
                'last_name' => ' Bennani ',
                'company' => ' NinoWorld ',
                'address_line_1' => ' 45 Ocean Avenue ',
                'address_line_2' => '',
                'city' => ' Rabat ',
                'state' => ' Rabat-Sale-Kenitra ',
                'postal_code' => ' 10000 ',
                'country' => ' ma ',
                'phone' => ' +212611111111 ',
            ])
            ->assertOk()
            ->assertJsonPath('address.city', 'Rabat')
            ->assertJsonPath('address.country', 'MA');

        $address->refresh();

        $this->assertSame('45 Ocean Avenue', $address->address_line_1);
        $this->assertSame('Rabat', $address->city);
        $this->assertSame('Rabat-Sale-Kenitra', $address->state);
        $this->assertSame('10000', $address->postal_code);
        $this->assertSame('MA', $address->country);
        $this->assertNull($address->address_line_2);

        $this->actingAs($customer)
            ->deleteJson(route('api.customer.addresses.destroy', $address))
            ->assertOk();

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_default_address_is_unique_per_type_and_promoted_on_delete(): void
    {
        $customer = User::factory()->customer()->create();

        $first = $customer->addresses()->create($this->addressPayload(Address::TYPE_BILLING));
        $second = $customer->addresses()->create(array_merge(
            $this->addressPayload(Address::TYPE_BILLING),
            [
                'first_name' => 'Leila',
                'postal_code' => '20100',
                'is_default' => false,
            ]
        ));

        $this->actingAs($customer)
            ->putJson(route('api.customer.addresses.update', $second), array_merge(
                $this->addressPayload(Address::TYPE_BILLING),
                [
                    'first_name' => 'Leila',
                    'postal_code' => '20100',
                    'is_default' => true,
                ]
            ))
            ->assertOk();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);

        $this->actingAs($customer)
            ->deleteJson(route('api.customer.addresses.destroy', $second))
            ->assertOk();

        $this->assertTrue($first->fresh()->is_default);
    }

    public function test_first_address_of_a_type_becomes_default_automatically(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson(route('api.customer.addresses.store'), array_merge(
                $this->addressPayload(Address::TYPE_SHIPPING),
                ['is_default' => false]
            ))
            ->assertCreated()
            ->assertJsonPath('address.is_default', true);
    }

    public function test_address_validation_enforces_supported_type_and_location_structure(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson(route('api.customer.addresses.store'), array_merge(
                $this->addressPayload(Address::TYPE_SHIPPING),
                [
                    'type' => 'office',
                    'city' => '   ',
                    'state' => '',
                    'country' => 'Morocco',
                ]
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'city', 'state', 'country']);
    }

    private function addressPayload(string $type): array
    {
        return [
            'type' => $type,
            'is_default' => true,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'company' => 'NinoWorld',
            'address_line_1' => '12 Atlas Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
            'phone' => '+212600000000',
        ];
    }
}
