<?php

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MailLog;
use App\Models\SuppressionList;
use App\Models\Template;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->template = Template::factory()->create();
    $this->campaign = Campaign::factory()->draft()->create([
        'user_id' => $this->user->id,
        'template_id' => $this->template->id,
    ]);
});

describe('Mail logging on send', function () {
    test('sendToRecipient logs a sent event with smtp source and attempt', function () {
        $recipient = CampaignRecipient::factory()->pending()->create(['campaign_id' => $this->campaign->id]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->andReturn(true);

        (new CampaignService($emailService))->sendToRecipient($this->campaign, $recipient);

        $this->assertDatabaseHas('mail_logs', [
            'event' => 'sent',
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $recipient->id,
            'user_id' => $this->user->id,
            'recipient_email' => $recipient->email,
            'smtp_source' => 'default',
            'attempt' => 1,
            'error_message' => null,
        ]);
    });

    test('sendToRecipient logs a failed event with the error message', function () {
        $recipient = CampaignRecipient::factory()->pending()->create(['campaign_id' => $this->campaign->id]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->andThrow(new Exception('SMTP connection refused'));

        (new CampaignService($emailService))->sendToRecipient($this->campaign, $recipient);

        $this->assertDatabaseHas('mail_logs', [
            'event' => 'failed',
            'recipient_id' => $recipient->id,
            'smtp_source' => 'default',
            'attempt' => 1,
            'error_message' => 'SMTP connection refused',
        ]);
    });

    test('sendToRecipient logs a bounced event for suppressed recipients', function () {
        SuppressionList::create(['email' => 'blocked@example.com', 'reason' => 'manual']);

        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
            'email' => 'blocked@example.com',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')->never();

        (new CampaignService($emailService))->sendToRecipient($this->campaign, $recipient);

        $this->assertDatabaseHas('mail_logs', [
            'event' => 'bounced',
            'recipient_id' => $recipient->id,
            'error_message' => 'Recipient is in suppression list',
        ]);
    });

    test('SendCampaignEmail failed() logs a job_failed event', function () {
        $recipient = CampaignRecipient::factory()->pending()->create(['campaign_id' => $this->campaign->id]);

        (new SendCampaignEmail($this->campaign, $recipient))->failed(new RuntimeException('Queue failure'));

        $this->assertDatabaseHas('mail_logs', [
            'event' => 'job_failed',
            'recipient_id' => $recipient->id,
            'error_message' => 'Queue failure',
        ]);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $recipient->id, 'status' => 'failed']);
        $this->assertDatabaseHas('campaigns', ['id' => $this->campaign->id, 'status' => 'failed']);
    });
});

describe('Admin mail log management', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    test('non-admin users cannot access the mail logs page', function () {
        $this->actingAs($this->user)
            ->get(route('admin.mail-logs.index'))
            ->assertStatus(403);
    });

    test('admin can view mail logs with stats', function () {
        MailLog::factory()->failed()->create(['campaign_id' => $this->campaign->id, 'user_id' => $this->user->id]);
        MailLog::factory()->sent()->create(['campaign_id' => $this->campaign->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.mail-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/mail-logs')
                ->has('logs.data', 2)
                ->where('stats.failed_total', 1)
                ->where('stats.sent_24h', 1)
            );
    });

    test('admin can filter logs by event', function () {
        MailLog::factory()->failed()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'recipient_email' => 'fail@example.com',
        ]);
        MailLog::factory()->sent()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'recipient_email' => 'ok@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.mail-logs.index', ['event' => 'failed']))
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.recipient_email', 'fail@example.com')
            );
    });

    test('admin can search logs by recipient email', function () {
        MailLog::factory()->failed()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'recipient_email' => 'fail@example.com',
        ]);
        MailLog::factory()->sent()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'recipient_email' => 'ok@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.mail-logs.index', ['search' => 'ok@example.com']))
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.recipient_email', 'ok@example.com')
            );
    });

    test('admin can delete an individual log', function () {
        $log = MailLog::factory()->failed()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.mail-logs.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('mail_logs', ['id' => $log->id]);
    });

    test('admin can clear all logs', function () {
        MailLog::factory()->count(3)->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.mail-logs.clear'))
            ->assertRedirect();

        expect(MailLog::count())->toBe(0);
    });
});
