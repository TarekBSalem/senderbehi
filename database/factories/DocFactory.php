<?php

namespace Database\Factories;

use App\Models\Doc;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocFactory extends Factory
{
    protected $model = Doc::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => str($title)->slug(),
            'title' => $title,
            'content' => fake()->paragraphs(5, true),
            'category' => fake()->randomElement(['Getting Started', 'Campaigns', 'Templates', 'Account']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
