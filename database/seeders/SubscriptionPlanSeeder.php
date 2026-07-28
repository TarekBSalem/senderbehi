<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'code' => 'free',
                'stripe_price_id' => null,
                'price_cents' => 0,
                'currency' => 'usd',
                'email_limit' => 500,
                'features' => json_encode([
                    'templates' => true,
                    'csv_import' => true,
                    'basic_stats' => true,
                    'custom_smtp' => false,
                    'analytics' => false,
                    'api_access' => false,
                    'team_members' => 1,
                ]),
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Starter',
                'code' => 'starter',
                'stripe_price_id' => null,
                'price_cents' => 900,
                'currency' => 'usd',
                'email_limit' => 5000,
                'features' => json_encode([
                    'templates' => true,
                    'csv_import' => true,
                    'basic_stats' => true,
                    'custom_smtp' => true,
                    'analytics' => false,
                    'api_access' => false,
                    'team_members' => 1,
                ]),
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'code' => 'pro',
                'stripe_price_id' => null,
                'price_cents' => 2900,
                'currency' => 'usd',
                'email_limit' => 25000,
                'features' => json_encode([
                    'templates' => true,
                    'csv_import' => true,
                    'basic_stats' => true,
                    'custom_smtp' => true,
                    'analytics' => true,
                    'api_access' => false,
                    'team_members' => 3,
                ]),
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'code' => 'business',
                'stripe_price_id' => null,
                'price_cents' => 7900,
                'currency' => 'usd',
                'email_limit' => 100000,
                'features' => json_encode([
                    'templates' => true,
                    'csv_import' => true,
                    'basic_stats' => true,
                    'custom_smtp' => true,
                    'analytics' => true,
                    'api_access' => true,
                    'team_members' => 10,
                ]),
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        DB::table('subscription_plans')->insert($plans);
    }
}
