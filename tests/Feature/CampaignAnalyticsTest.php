<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailTracking;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->template = Template::factory()->create();
    $this->campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'template_id' => $this->template->id,
        'status' => 'sent',
        'sent_count' => 10,
        'failed_count' => 1,
    ]);
});

describe('Campaign Analytics Access', function () {
    test('guests cannot view analytics', function () {
        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertRedirect(route('login'));
    });

    test('user can view own campaign analytics', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/analytics')
            ->has('campaign')
            ->has('stats')
            ->has('timeline')
            ->has('device_breakdown')
            ->has('browser_breakdown')
            ->has('recipients')
        );
    });

    test('user cannot view other users campaign analytics', function () {
        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.analytics', $otherCampaign));
        $response->assertForbidden();
    });

    test('analytics page shows draft message', function () {
        $this->actingAs($this->user);

        $draftCampaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'template_id' => $this->template->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('campaigns.analytics', $draftCampaign));
        $response->assertOk();
    });
});

describe('Campaign Analytics Data', function () {
    test('stats return correct values', function () {
        $this->actingAs($this->user);

        CampaignRecipient::factory()->count(5)->create([
            'campaign_id' => $this->campaign->id,
            'status' => 'sent',
        ]);

        CampaignRecipient::factory()->count(2)->create([
            'campaign_id' => $this->campaign->id,
            'status' => 'bounced',
        ]);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_sent', 10)
            ->where('stats.total_failed', 1)
            ->where('stats.total_bounced', 2)
            ->where('stats.total_pending', 0)
        );
    });

    test('tracking events are counted correctly', function () {
        $this->actingAs($this->user);

        $recipients = CampaignRecipient::factory()->count(3)->create([
            'campaign_id' => $this->campaign->id,
            'status' => 'sent',
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[0]->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'occurred_at' => now(),
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[0]->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'occurred_at' => now(),
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[1]->id,
            'type' => 'click',
            'ip_address' => '127.0.0.1',
            'occurred_at' => now(),
        ]);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where('stats.unique_opens', 1)
            ->where('stats.unique_clicks', 1)
            ->where('stats.total_open_events', 2)
            ->where('stats.total_click_events', 1)
            ->where('stats.open_rate', 10)
            ->where('stats.click_rate', 10)
        );
    });

    test('recipient data includes tracking info', function () {
        $this->actingAs($this->user);

        $recipient = CampaignRecipient::factory()->create([
            'campaign_id' => $this->campaign->id,
            'status' => 'sent',
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipient->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile Safari',
            'occurred_at' => now(),
        ]);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has('recipients', 1)
            ->where('recipients.0.email', $recipient->email)
            ->where('recipients.0.opened', true)
            ->where('recipients.0.clicked', false)
            ->where('recipients.0.device', 'Mobile')
        );
    });

    test('device breakdown parses user agents', function () {
        $this->actingAs($this->user);

        $recipients = CampaignRecipient::factory()->count(3)->create([
            'campaign_id' => $this->campaign->id,
            'status' => 'sent',
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[0]->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'occurred_at' => now(),
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[1]->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile Safari',
            'occurred_at' => now(),
        ]);

        EmailTracking::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipients[2]->id,
            'type' => 'open',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0) Safari',
            'occurred_at' => now(),
        ]);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has('device_breakdown', 3)
        );
    });

    test('no tracking data returns zeros', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.analytics', $this->campaign));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where('stats.unique_opens', 0)
            ->where('stats.unique_clicks', 0)
            ->where('stats.open_rate', 0)
            ->where('stats.click_rate', 0)
            ->where('stats.click_to_open_rate', 0)
            ->where('stats.bounce_rate', 0)
            ->where('stats.total_bounced', 0)
        );
    });
});

describe('Campaign Analytics Export', function () {
    test('guests cannot export analytics', function () {
        $response = $this->get(route('campaigns.analytics.export', $this->campaign));
        $response->assertRedirect(route('login'));
    });

    test('user can export own campaign analytics', function () {
        $this->actingAs($this->user);

        CampaignRecipient::factory()->create([
            'campaign_id' => $this->campaign->id,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'sent',
        ]);

        $response = $this->get(route('campaigns.analytics.export', $this->campaign));
        $response->assertOk();
        $response->assertJsonStructure(['csv', 'filename']);
        $response->assertSee('test@example.com');
    });

    test('user cannot export other users campaign analytics', function () {
        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.analytics.export', $otherCampaign));
        $response->assertForbidden();
    });

    test('export csv contains correct headers', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.analytics.export', $this->campaign));
        $response->assertOk();
        $response->assertJson(fn ($json) => $json
            ->has('csv')
            ->has('filename')
            ->where('filename', "campaign-{$this->campaign->id}-analytics.csv")
        );
    });
});
