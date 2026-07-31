<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'code' => fake()->unique()->slug(1),
            'stripe_price_id' => null,
            'price_cents' => fake()->numberBetween(0, 9900),
            'currency' => 'usd',
            'email_limit' => fake()->numberBetween(100, 100000),
            'features' => [
                'templates' => true,
                'csv_import' => true,
                'basic_stats' => true,
                'custom_smtp' => false,
                'analytics' => false,
                'api_access' => false,
                'team_members' => 1,
            ],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'code' => 'free',
            'price_cents' => 0,
            'email_limit' => 500,
        ]);
    }
}
