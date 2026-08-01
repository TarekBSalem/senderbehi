<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailLog>
 */
class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'user_id' => User::factory(),
            'recipient_email' => fake()->safeEmail(),
            'event' => 'sent',
            'smtp_source' => 'default',
            'error_message' => null,
            'attempt' => 1,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'sent',
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'bounced',
            'error_message' => 'Recipient is in suppression list',
        ]);
    }
}
