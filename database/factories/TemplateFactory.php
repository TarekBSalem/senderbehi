<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'subject' => fake()->sentence(),
            'body_html' => '<html><body><h1>Hello {{name}}</h1><p>{{content}}</p></body></html>',
            'category' => fake()->randomElement(['newsletter', 'promotional', 'transactional', 'welcome']),
            'thumbnail' => null,
            'variables' => ['name', 'content'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
