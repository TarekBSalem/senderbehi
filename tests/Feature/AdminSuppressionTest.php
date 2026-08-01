<?php

use App\Models\Campaign;
use App\Models\SuppressionList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->campaign = Campaign::factory()->create();
});

test('non-admin users cannot access the suppression list page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.suppressions.index'))
        ->assertStatus(403);
});

test('admin can view suppression list with stats', function () {
    SuppressionList::create(['email' => 'bounce@example.com', 'reason' => 'bounce']);
    SuppressionList::create(['email' => 'complaint@example.com', 'reason' => 'complaint']);
    SuppressionList::create(['email' => 'manual@example.com', 'reason' => 'manual']);

    $this->actingAs($this->admin)
        ->get(route('admin.suppressions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/suppressions')
            ->has('suppressions.data', 3)
            ->where('stats.total', 3)
            ->where('stats.bounce', 1)
            ->where('stats.complaint', 1)
            ->where('stats.manual', 1)
        );
});

test('admin can search suppressions by email', function () {
    SuppressionList::create(['email' => 'bounce@example.com', 'reason' => 'bounce']);
    SuppressionList::create(['email' => 'other@example.com', 'reason' => 'manual']);

    $this->actingAs($this->admin)
        ->get(route('admin.suppressions.index', ['search' => 'bounce@example.com']))
        ->assertInertia(fn ($page) => $page
            ->has('suppressions.data', 1)
            ->where('suppressions.data.0.email', 'bounce@example.com')
        );
});

test('admin can filter suppressions by reason', function () {
    SuppressionList::create(['email' => 'bounce@example.com', 'reason' => 'bounce']);
    SuppressionList::create(['email' => 'manual@example.com', 'reason' => 'manual']);

    $this->actingAs($this->admin)
        ->get(route('admin.suppressions.index', ['reason' => 'bounce']))
        ->assertInertia(fn ($page) => $page
            ->has('suppressions.data', 1)
            ->where('suppressions.data.0.email', 'bounce@example.com')
        );
});

test('admin can add an email to the suppression list', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.suppressions.store'), [
            'email' => 'blocked@example.com',
            'reason' => 'manual',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('suppression_list', [
        'email' => 'blocked@example.com',
        'reason' => 'manual',
    ]);
});

test('store validates email and reason', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.suppressions.store'), [
            'email' => 'not-an-email',
            'reason' => 'unknown',
        ])
        ->assertSessionHasErrors(['email', 'reason']);

    expect(SuppressionList::count())->toBe(0);
});

test('store rejects duplicate emails', function () {
    SuppressionList::create(['email' => 'blocked@example.com', 'reason' => 'bounce']);

    $this->actingAs($this->admin)
        ->post(route('admin.suppressions.store'), [
            'email' => 'blocked@example.com',
            'reason' => 'manual',
        ])
        ->assertSessionHasErrors('email');

    expect(SuppressionList::count())->toBe(1);
});

test('admin can delete an individual suppression entry', function () {
    $suppression = SuppressionList::create(['email' => 'blocked@example.com', 'reason' => 'bounce']);

    $this->actingAs($this->admin)
        ->delete(route('admin.suppressions.destroy', $suppression))
        ->assertRedirect();

    $this->assertDatabaseMissing('suppression_list', ['id' => $suppression->id]);
});

test('non-admin cannot delete a suppression entry', function () {
    $suppression = SuppressionList::create(['email' => 'blocked@example.com', 'reason' => 'bounce']);

    $this->actingAs($this->user)
        ->delete(route('admin.suppressions.destroy', $suppression))
        ->assertStatus(403);

    $this->assertDatabaseHas('suppression_list', ['id' => $suppression->id]);
});

test('admin can clear the suppression list', function () {
    SuppressionList::create(['email' => 'a@example.com', 'reason' => 'bounce']);
    SuppressionList::create(['email' => 'b@example.com', 'reason' => 'manual']);

    $this->actingAs($this->admin)
        ->delete(route('admin.suppressions.clear'))
        ->assertRedirect();

    expect(SuppressionList::count())->toBe(0);
});

test('suppression list page shows campaign name', function () {
    SuppressionList::create([
        'email' => 'blocked@example.com',
        'reason' => 'bounce',
        'campaign_id' => $this->campaign->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.suppressions.index'))
        ->assertInertia(fn ($page) => $page
            ->where('suppressions.data.0.campaign.name', $this->campaign->name)
        );
});
