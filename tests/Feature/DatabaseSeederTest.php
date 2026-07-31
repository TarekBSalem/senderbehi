<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates an admin with Super Admin role and Business plan', function () {
    $this->seed();

    $admin = User::where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->hasRole('Super Admin'))->toBeTrue();
    expect($admin->subscription_plan_id)->toBe(
        SubscriptionPlan::where('code', 'business')->first()->id
    );
    expect($admin->canSend(100000))->toBeTrue();
});

test('database seeder creates a test user with the User role', function () {
    $this->seed();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('User'))->toBeTrue();
});
