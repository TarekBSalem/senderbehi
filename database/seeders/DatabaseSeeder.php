<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create Super Admin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('Super Admin');

        // Create test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('User');

        $this->call([
            CategorySeeder::class,
            TemplateSeeder::class,
            SubscriptionPlanSeeder::class,
            DocSeeder::class,
            BlogPostSeeder::class,
            ChangelogSeeder::class,
        ]);

        // Admins bypass usage quotas (User::canSend), but assign the top
        // plan so the seeded admin always has a valid plan on record.
        $business = SubscriptionPlan::where('code', 'business')->first();

        $admin->update(['subscription_plan_id' => $business?->id]);
    }
}
