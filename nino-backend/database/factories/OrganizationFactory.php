<?php

namespace Database\Factories;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => Organization::TYPE_BRANCH,
            'status' => Organization::STATUS_ACTIVE,
            'code' => 'ORG-'.fake()->unique()->numerify('###'),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'country' => fake()->optional()->countryCode(),
        ];
    }

    public function platform(): static
    {
        return $this->state(fn (): array => [
            'type' => Organization::TYPE_PLATFORM,
            'code' => 'PLT-'.fake()->unique()->numerify('###'),
            'parent_id' => null,
        ]);
    }

    public function franchise(?Organization $platform = null): static
    {
        return $this->state(fn (): array => [
            'type' => Organization::TYPE_FRANCHISE,
            'code' => 'FR-'.fake()->unique()->numerify('###'),
            'parent_id' => $platform?->id ?? Organization::factory()->platform(),
        ]);
    }

    public function branch(?Organization $franchise = null): static
    {
        return $this->state(fn (): array => [
            'type' => Organization::TYPE_BRANCH,
            'code' => 'BR-'.fake()->unique()->numerify('###'),
            'parent_id' => $franchise?->id ?? Organization::factory()->franchise(),
        ]);
    }
}
