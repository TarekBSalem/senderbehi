<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'slug' => str($title)->slug(),
            'title' => $title,
            'content' => fake()->paragraphs(8, true),
            'excerpt' => fake()->paragraph(),
            'published_at' => fake()->dateTimeBetween('-6 months'),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
