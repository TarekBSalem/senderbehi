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

    $this->freePlan = SubscriptionPlan::where('code', 'free')->first();
    $this->starterPlan = SubscriptionPlan::where('code', 'starter')->first();
});

describe('plan assignment', function () {
    test('admin can assign a plan to a user', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.plan', $user), [
                'plan_id' => $this->freePlan->id,
            ])
            ->assertSessionHas('success');

        expect($user->fresh()->subscription_plan_id)->toBe($this->freePlan->id);
    });

    test('admin can remove a plan from a user', function () {
        $user = User::factory()->create(['subscription_plan_id' => $this->freePlan->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.users.plan', $user), [
                'plan_id' => null,
            ])
            ->assertSessionHas('success');

        expect($user->fresh()->subscription_plan_id)->toBeNull();
    });

    test('non-admin cannot assign plans', function () {
        $user = User::factory()->create();
        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        $this->actingAs($regularUser)
            ->put(route('admin.users.plan', $user), [
                'plan_id' => $this->freePlan->id,
            ])
            ->assertStatus(403);
    });

    test('plan validation rejects non-existent plan', function () {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.plan', $user), [
                'plan_id' => 99999,
            ])
            ->assertSessionHasErrors('plan_id');
    });
});

describe('quota enforcement', function () {
    test('user with no plan cannot send', function () {
        $user = User::factory()->create();

        expect($user->canSend())->toBeFalse();
        expect($user->quotaRemaining())->toBe(0);
    });

    test('user with plan can send within limit', function () {
        $user = User::factory()->create(['subscription_plan_id' => $this->freePlan->id]);

        expect($user->canSend())->toBeTrue();
        expect($user->quotaRemaining())->toBe(500);
    });

    test('user cannot send when over limit', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->freePlan->id,
            'monthly_email_count' => 500,
            'monthly_email_reset_at' => now(),
        ]);

        expect($user->canSend())->toBeFalse();
        expect($user->quotaRemaining())->toBe(0);
    });

    test('user can send when partially used', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->starterPlan->id,
            'monthly_email_count' => 3000,
            'monthly_email_reset_at' => now(),
        ]);

        expect($user->canSend())->toBeTrue();
        expect($user->canSend(2000))->toBeTrue();
        expect($user->canSend(2001))->toBeFalse();
        expect($user->quotaRemaining())->toBe(2000);
    });

    test('increment increases monthly count', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->freePlan->id,
            'monthly_email_count' => 0,
        ]);

        $user->incrementMonthlyEmailCount();
        expect($user->fresh()->monthly_email_count)->toBe(1);

        $user->incrementMonthlyEmailCount(5);
        expect($user->fresh()->monthly_email_count)->toBe(6);
    });

    test('monthly count resets at new month', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->freePlan->id,
            'monthly_email_count' => 450,
            'monthly_email_reset_at' => now()->startOfMonth()->subDay(),
        ]);

        expect($user->quotaRemaining())->toBe(500);
        expect($user->fresh()->monthly_email_count)->toBe(0);
    });
});

describe('campaign send quota check', function () {
    test('send fails when user exceeds monthly limit', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->freePlan->id,
            'monthly_email_count' => 500,
            'monthly_email_reset_at' => now(),
        ]);
        $user->assignRole('User');

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        $campaign->recipients()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHas('error');
    });

    test('send succeeds when within monthly limit', function () {
        $user = User::factory()->create([
            'subscription_plan_id' => $this->freePlan->id,
            'monthly_email_count' => 0,
            'monthly_email_reset_at' => now(),
        ]);
        $user->assignRole('User');

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        $campaign->recipients()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHas('success');
    });
});
