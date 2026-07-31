<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('authorization', function () {
    test('guests cannot view plans', function () {
        $response = $this->get(route('admin.subscription-plans.index'));
        $response->assertRedirect(route('login'));
    });

    test('non-admin users cannot view plans', function () {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user)
            ->get(route('admin.subscription-plans.index'))
            ->assertStatus(403);
    });

    test('super admin can view plans', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk();
    });
});

describe('index', function () {
    test('displays plans paginated', function () {
        SubscriptionPlan::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/subscription-plans/index')
            ->has('plans.data', 3)
        );
    });

    test('filters by search', function () {
        SubscriptionPlan::factory()->create(['name' => 'Enterprise Plan', 'code' => 'enterprise']);
        SubscriptionPlan::factory()->create(['name' => 'Basic', 'code' => 'basic']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.index', ['search' => 'Enterprise']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/subscription-plans/index')
            ->has('plans.data', 1)
            ->where('plans.data.0.name', 'Enterprise Plan')
        );
    });

    test('filters by status', function () {
        SubscriptionPlan::factory()->count(2)->create(['is_active' => true]);
        SubscriptionPlan::factory()->inactive()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.index', ['is_active' => '1']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('plans.data', 2)
        );
    });

    test('shows empty state when no plans', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('plans.data', 0)
        );
    });
});

describe('create', function () {
    test('renders create form', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/subscription-plans/create'));
    });

    test('stores a new plan', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subscription-plans.store'), [
                'name' => 'Starter',
                'code' => 'starter',
                'price_cents' => 900,
                'currency' => 'usd',
                'email_limit' => 5000,
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Starter',
            'code' => 'starter',
            'price_cents' => 900,
            'email_limit' => 5000,
        ]);
    });

    test('store validates required fields', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subscription-plans.store'), []);

        $response->assertSessionHasErrors(['name', 'code', 'price_cents', 'currency', 'email_limit']);
    });

    test('store validates unique code', function () {
        SubscriptionPlan::factory()->create(['code' => 'starter']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.subscription-plans.store'), [
                'name' => 'Starter',
                'code' => 'starter',
                'price_cents' => 900,
                'currency' => 'usd',
                'email_limit' => 5000,
            ]);

        $response->assertSessionHasErrors('code');
    });

    test('store accepts zero price for free plan', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subscription-plans.store'), [
                'name' => 'Free',
                'code' => 'free',
                'price_cents' => 0,
                'currency' => 'usd',
                'email_limit' => 500,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscription_plans', [
            'code' => 'free',
            'price_cents' => 0,
        ]);
    });
});

describe('edit', function () {
    test('renders edit form with plan data', function () {
        $plan = SubscriptionPlan::factory()->create(['name' => 'Pro']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.subscription-plans.edit', $plan));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/subscription-plans/edit')
            ->where('plan.name', 'Pro')
        );
    });

    test('updates a plan', function () {
        $plan = SubscriptionPlan::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.subscription-plans.update', $plan), [
                'name' => 'Updated Name',
                'code' => $plan->code,
                'price_cents' => 4900,
                'currency' => 'usd',
                'email_limit' => 50000,
                'sort_order' => 2,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Updated Name',
            'price_cents' => 4900,
        ]);
    });

    test('update validates required fields', function () {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.subscription-plans.update', $plan), []);

        $response->assertSessionHasErrors(['name', 'code', 'price_cents', 'currency', 'email_limit']);
    });

    test('update allows same code for same plan', function () {
        $plan = SubscriptionPlan::factory()->create(['code' => 'pro']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.subscription-plans.update', $plan), [
                'name' => 'Pro Updated',
                'code' => 'pro',
                'price_cents' => 2900,
                'currency' => 'usd',
                'email_limit' => 25000,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Pro Updated',
        ]);
    });
});

describe('destroy', function () {
    test('deletes a plan', function () {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.subscription-plans.destroy', $plan));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    });
});
