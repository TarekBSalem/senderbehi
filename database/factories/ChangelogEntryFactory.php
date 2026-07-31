<?php

namespace Database\Factories;

use App\Models\ChangelogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChangelogEntryFactory extends Factory
{
    protected $model = ChangelogEntry::class;

    public function definition(): array
    {
        return [
            'version' => fake()->semver(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'released_at' => fake()->dateTimeBetween('-3 months'),
            'is_published' => true,
        ];
    }
}
