<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_users_support_profile_fields_and_marketing_preference(): void
    {
        $customer = User::factory()->customer()->create([
            'first_name' => '  Sara ',
            'last_name' => ' El Idrissi ',
            'username' => ' Sara.ID ',
            'marketing_opt_in' => true,
        ]);

        $customer->refresh();

        $this->assertTrue($customer->isCustomer());
        $this->assertSame('Sara', $customer->first_name);
        $this->assertSame('El Idrissi', $customer->last_name);
        $this->assertSame('sara.id', $customer->username);
        $this->assertTrue($customer->marketing_opt_in);
        $this->assertSame('Sara El Idrissi', $customer->full_name);
    }

    public function test_customer_account_states_are_explicit_and_queryable(): void
    {
        $active = User::factory()->customer()->create(['status' => User::STATUS_ACTIVE]);
        $inactive = User::factory()->customer()->inactive()->create();
        $suspended = User::factory()->customer()->suspended()->create();

        $this->assertSame(
            [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUSPENDED],
            User::availableStatuses(),
        );

        $this->assertTrue($active->isActive());
        $this->assertTrue($inactive->isInactive());
        $this->assertTrue($suspended->isSuspended());

        $this->assertSame([$active->id], User::query()->customers()->active()->pluck('id')->all());
        $this->assertSame([$inactive->id], User::query()->customers()->inactive()->pluck('id')->all());
        $this->assertSame([$suspended->id], User::query()->customers()->suspended()->pluck('id')->all());
    }

    public function test_customer_factory_defaults_to_customer_type_when_requested(): void
    {
        $customer = User::factory()->customer()->create();

        $this->assertSame(User::TYPE_CUSTOMER, $customer->type);
        $this->assertSame(User::STATUS_ACTIVE, $customer->status);
        $this->assertContains(User::TYPE_CUSTOMER, User::availableTypes());
    }
}
