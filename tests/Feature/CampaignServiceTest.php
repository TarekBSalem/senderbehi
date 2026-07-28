<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Template;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('User');
    $this->template = Template::factory()->create();
    $this->campaign = Campaign::factory()->draft()->create([
        'user_id' => $this->user->id,
        'template_id' => $this->template->id,
    ]);
});

describe('CampaignService Update Stats', function () {
    test('updateCampaignStats increments sent count on success', function () {
        $campaignService = new CampaignService(app(EmailService::class));

        $campaignService->updateCampaignStats($this->campaign, true);
        $campaignService->updateCampaignStats($this->campaign, true);
        $campaignService->updateCampaignStats($this->campaign, true);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'sent_count' => 3,
            'failed_count' => 0,
        ]);
    });

    test('updateCampaignStats increments failed count on failure', function () {
        $campaignService = new CampaignService(app(EmailService::class));

        $campaignService->updateCampaignStats($this->campaign, false);
        $campaignService->updateCampaignStats($this->campaign, false);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'sent_count' => 0,
            'failed_count' => 2,
        ]);
    });

    test('updateCampaignStats marks campaign as sent when no pending remain', function () {
        $campaignService = new CampaignService(app(EmailService::class));

        // Create recipients already marked as sent (simulating after processing)
        CampaignRecipient::factory()->count(2)->sent()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $campaignService->updateCampaignStats($this->campaign, true);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'status' => 'sent',
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
    });

    test('updateCampaignStats marks campaign as partial when some fail', function () {
        $campaignService = new CampaignService(app(EmailService::class));

        CampaignRecipient::factory()->count(2)->sent()->create([
            'campaign_id' => $this->campaign->id,
        ]);
        CampaignRecipient::factory()->count(1)->failed()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        // Process the last one — a failure
        $campaignService->updateCampaignStats($this->campaign, false);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'status' => 'partial',
            'sent_count' => 0,
            'failed_count' => 1,
        ]);
    });

    test('updateCampaignStats marks campaign as partial when all fail', function () {
        $campaignService = new CampaignService(app(EmailService::class));

        CampaignRecipient::factory()->count(3)->failed()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $campaignService->updateCampaignStats($this->campaign, false);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'status' => 'partial',
            'sent_count' => 0,
            'failed_count' => 1,
        ]);
    });
});
