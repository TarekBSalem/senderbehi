<?php

use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->admin = User::factory()->admin()->create();
});

describe('authorization', function () {
    test('guests cannot view analytics', function () {
        $this->get(route('admin.analytics.index'))
            ->assertRedirect(route('login'));
    });

    test('non-admin users cannot view analytics', function () {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user)
            ->get(route('admin.analytics.index'))
            ->assertStatus(403);
    });

    test('super admin can view analytics', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/analytics')
                ->has('stats')
                ->has('daily_volume')
                ->has('campaigns_by_status')
                ->has('recent_campaigns')
                ->has('plans')
            );
    });
});

describe('analytics data accuracy', function () {
    test('stats reflect correct totals', function () {
        User::factory()->count(5)->create();
        Campaign::factory()->count(3)->create([
            'user_id' => $this->admin->id,
            'sent_count' => 100,
            'failed_count' => 5,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.total_users', 6)
                ->where('stats.total_campaigns', 3)
                ->where('stats.emails_sent_all_time', 300)
                ->where('stats.emails_failed_all_time', 15)
            );
    });

    test('MRR reflects paid plan assignments', function () {
        $starter = SubscriptionPlan::where('code', 'starter')->first();
        $pro = SubscriptionPlan::where('code', 'pro')->first();

        User::factory()->count(3)->create(['subscription_plan_id' => $starter->id]);
        User::factory()->count(2)->create(['subscription_plan_id' => $pro->id]);

        $expectedMrr = (3 * $starter->price_cents) + (2 * $pro->price_cents);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.mrr_cents', $expectedMrr)
            );
    });

    test('free plan users do not contribute to MRR', function () {
        $free = SubscriptionPlan::where('code', 'free')->first();

        User::factory()->count(10)->create(['subscription_plan_id' => $free->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.mrr_cents', 0)
            );
    });

    test('plan distribution shows correct user counts', function () {
        $free = SubscriptionPlan::where('code', 'free')->first();
        $pro = SubscriptionPlan::where('code', 'pro')->first();

        User::factory()->count(2)->create(['subscription_plan_id' => $free->id]);
        User::factory()->count(3)->create(['subscription_plan_id' => $pro->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->has('plans', 4)
                ->where('plans.0.user_count', 2)
                ->where('plans.2.user_count', 3)
            );
    });

    test('campaigns by status groups correctly', function () {
        Campaign::factory()->count(5)->draft()->create(['user_id' => $this->admin->id]);
        Campaign::factory()->count(3)->sent()->create(['user_id' => $this->admin->id]);
        Campaign::factory()->count(2)->create([
            'user_id' => $this->admin->id,
            'status' => 'failed',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->has('campaigns_by_status', 3)
            );
    });

    test('daily volume returns 31 entries for last 30 days', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->has('daily_volume', 31)
            );
    });

    test('recent campaigns shows latest across all users', function () {
        $otherUser = User::factory()->create();
        Campaign::factory()->count(5)->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->has('recent_campaigns', 5)
            );
    });
});
