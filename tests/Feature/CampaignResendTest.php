<?php

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Template;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\EmailService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $this->user = User::factory()->onPlan('free')->create();
    $this->template = Template::factory()->create();
    $this->campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'template_id' => $this->template->id,
    ]);
});

describe('Recipient resend', function () {
    test('resend re-queues all failed recipients and resets them to pending', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $failed = CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);
        CampaignRecipient::factory()->sent()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign));

        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $failed->id,
            'status' => 'pending',
            'error_message' => null,
        ]);
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->campaign->id,
            'status' => 'sending',
        ]);

        Queue::assertPushed(SendCampaignEmail::class, 1);
        Queue::assertPushed(SendCampaignEmail::class, fn (SendCampaignEmail $job) => $job->recipient->is($failed));
    });

    test('resend only re-queues the selected recipient ids', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $failedA = CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);
        $failedB = CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);
        $sent = CampaignRecipient::factory()->sent()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign), [
            'recipient_ids' => [$failedA->id, $sent->id],
        ]);

        $this->assertDatabaseHas('campaign_recipients', ['id' => $failedA->id, 'status' => 'pending']);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $sent->id, 'status' => 'pending']);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $failedB->id, 'status' => 'failed']);

        Queue::assertPushed(SendCampaignEmail::class, 2);
    });

    test('resend returns an error when there are no resendable recipients', function () {
        $this->actingAs($this->user);

        Queue::fake();

        CampaignRecipient::factory()->pending()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign))->assertStatus(422);

        Queue::assertNotPushed(SendCampaignEmail::class);
    });

    test('resend_all re-queues every recipient regardless of status', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $sent = CampaignRecipient::factory()->sent()->create(['campaign_id' => $this->campaign->id]);
        $failed = CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);
        $pending = CampaignRecipient::factory()->pending()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign), [
            'resend_all' => true,
        ]);

        $this->assertDatabaseHas('campaign_recipients', ['id' => $sent->id, 'status' => 'pending', 'error_message' => null]);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $failed->id, 'status' => 'pending', 'error_message' => null]);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $pending->id, 'status' => 'pending', 'error_message' => null]);

        $this->assertDatabaseHas('campaigns', ['id' => $this->campaign->id, 'status' => 'sending']);

        Queue::assertPushed(SendCampaignEmail::class, 3);
    });

    test('resend_all with recipient_ids still respects the requested selection', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $sent = CampaignRecipient::factory()->sent()->create(['campaign_id' => $this->campaign->id]);
        $failed = CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign), [
            'resend_all' => true,
            'recipient_ids' => [$sent->id],
        ]);

        $this->assertDatabaseHas('campaign_recipients', ['id' => $sent->id, 'status' => 'pending']);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $failed->id, 'status' => 'failed']);

        Queue::assertPushed(SendCampaignEmail::class, 1);
    });

    test('resend is blocked when the monthly quota is exhausted', function () {
        $this->user->monthly_email_count = 500;
        $this->user->monthly_email_reset_at = now();
        $this->user->save();
        $this->actingAs($this->user);

        Queue::fake();

        CampaignRecipient::factory()->failed()->create(['campaign_id' => $this->campaign->id]);

        $this->post(route('recipients.resend', $this->campaign))
            ->assertSessionHas('error');

        Queue::assertNotPushed(SendCampaignEmail::class);
    });

    test('resend requires ownership of the campaign', function () {
        Queue::fake();

        $otherUser = User::factory()->onPlan('free')->create();
        $otherCampaign = Campaign::factory()->create(['user_id' => $otherUser->id]);
        CampaignRecipient::factory()->failed()->create(['campaign_id' => $otherCampaign->id]);

        $this->actingAs($this->user)
            ->post(route('recipients.resend', $otherCampaign))
            ->assertForbidden();

        Queue::assertNotPushed(SendCampaignEmail::class);
    });
});

describe('Attempt and success tracking', function () {
    test('sendToRecipient increments attempt and success counts on success', function () {
        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->andReturn(true);

        $campaignService = new CampaignService($emailService);

        $campaignService->sendToRecipient($this->campaign, $recipient);
        $campaignService->sendToRecipient($this->campaign, $recipient->fresh());

        $recipient->refresh();

        expect($recipient->status)->toBe('sent');
        expect($recipient->attempt_count)->toBe(2);
        expect($recipient->success_count)->toBe(2);
    });

    test('sendToRecipient increments attempt count but not success count on failure', function () {
        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->andThrow(new Exception('SMTP connection failed'));

        $campaignService = new CampaignService($emailService);

        $campaignService->sendToRecipient($this->campaign, $recipient);

        $recipient->refresh();

        expect($recipient->status)->toBe('failed');
        expect($recipient->attempt_count)->toBe(1);
        expect($recipient->success_count)->toBe(0);
    });

    test('campaign returns to sent status after failed recipients are resent successfully', function () {
        $recipient = CampaignRecipient::factory()->failed()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->andReturn(true);

        $campaignService = new CampaignService($emailService);

        $this->campaign->update([
            'status' => 'sending',
            'failed_count' => 1,
        ]);

        $campaignService->sendToRecipient($this->campaign, $recipient);

        $this->campaign->refresh();

        expect($this->campaign->status)->toBe('sent');
        expect($this->campaign->sent_count)->toBe(1);
        expect($this->campaign->failed_count)->toBe(1);
    });
});
