<?php

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\SubscriptionPlan;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $freePlan = SubscriptionPlan::where('code', 'free')->first();
    $this->user = User::factory()->create(['subscription_plan_id' => $freePlan->id]);
    $this->template = Template::factory()->create();
});

describe('Scheduling a campaign', function () {
    test('campaign send with future scheduled_at sets status to scheduled', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $campaign->update(['scheduled_at' => now()->addHour()]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'scheduled',
        ]);
    });

    test('campaign send with past scheduled_at sends immediately', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $campaign->update(['scheduled_at' => now()->subHour()]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
        ]);
    });

    test('user can schedule a campaign and send it now from scheduled status', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
        ]);
    });

    test('user can set scheduled_at on campaign update', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $future = now()->addDay()->format('Y-m-d H:i:s');

        $response = $this->put(route('campaigns.update', $campaign), [
            'scheduled_at' => $future,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'scheduled_at' => $future,
        ]);
    });
});

describe('Artisan command: campaigns:send-scheduled', function () {
    test('dispatches campaigns whose scheduled_at has passed', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $campaign = Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->subMinutes(5),
        ]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $this->artisan('campaigns:send-scheduled')
            ->assertSuccessful();

        Queue::assertPushed(SendCampaignEmail::class, 3);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
        ]);
    });

    test('ignores campaigns with future scheduled_at', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $campaign = Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
        ]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $this->artisan('campaigns:send-scheduled')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    });

    test('marks campaign as failed if no pending recipients', function () {
        $this->actingAs($this->user);

        Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $this->artisan('campaigns:send-scheduled')
            ->assertSuccessful();

        $this->assertDatabaseHas('campaigns', [
            'status' => 'failed',
        ]);
    });
});
