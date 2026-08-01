<?php

use App\Jobs\SendCampaignEmail;
use App\Mail\MarketingEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\PlatformSmtpSetting;
use App\Models\SmtpSetting;
use App\Models\SubscriptionPlan;
use App\Models\Template;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\EmailService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $freePlan = SubscriptionPlan::where('code', 'free')->first();
    $this->user = User::factory()->create(['subscription_plan_id' => $freePlan->id]);
    $this->template = Template::factory()->create();
});

describe('Campaign content', function () {
    test('campaign can be created with an imported HTML body (no template)', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Custom Campaign',
            'subject' => 'Custom Subject',
            'body_html' => '<h1>Hello {{name}}!</h1>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Custom Campaign',
            'template_id' => null,
            'body_html' => '<h1>Hello {{name}}!</h1>',
        ]);
    });

    test('campaign creation requires a template or an HTML body', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Empty Campaign',
            'subject' => 'Empty Subject',
        ]);

        $response->assertSessionHasErrors('body_html');
    });

    test('campaign can be created from a template', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Template Campaign',
            'subject' => 'Template Subject',
            'template_id' => $this->template->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Template Campaign',
            'template_id' => $this->template->id,
        ]);
    });
});

describe('Campaign custom SMTP', function () {
    test('campaign SMTP credentials are stored encrypted', function () {
        $this->actingAs($this->user);

        $this->post(route('campaigns.store'), [
            'name' => 'Smtp Campaign',
            'subject' => 'Smtp Subject',
            'template_id' => $this->template->id,
            'use_custom_smtp' => true,
            'smtp_host' => 'smtp.custom.com',
            'smtp_port' => 587,
            'smtp_username' => 'user@custom.com',
            'smtp_password' => 'secret-password',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => 'from@custom.com',
            'smtp_from_name' => 'Custom Sender',
        ]);

        $campaign = Campaign::where('name', 'Smtp Campaign')->first();

        expect($campaign->smtp_host)->toBe('smtp.custom.com');
        expect($campaign->smtp_password)->toBe('secret-password');
        expect($campaign->getRawOriginal('smtp_password'))->not->toBe('secret-password');
        expect(Crypt::decryptString($campaign->getRawOriginal('smtp_password')))->toBe('secret-password');
    });

    test('campaign custom SMTP takes priority over user and platform SMTP', function () {
        PlatformSmtpSetting::create([
            'host' => 'platform-smtp.invalid',
            'port' => 587,
            'username' => 'platform@example.com',
            'password' => 'password',
            'from_email' => 'platform@example.com',
            'from_name' => 'Platform',
            'daily_limit' => 1000,
            'is_active' => true,
        ]);

        SmtpSetting::create([
            'user_id' => $this->user->id,
            'is_custom' => true,
            'host' => 'user-smtp.invalid',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'encryption' => 'tls',
            'from_email' => 'user@example.com',
            'from_name' => 'User',
            'sends_per_minute' => 30,
        ]);

        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'use_custom_smtp' => true,
            'smtp_host' => 'campaign-smtp.invalid',
            'smtp_port' => 587,
            'smtp_username' => 'campaign@example.com',
            'smtp_password' => 'password',
            'smtp_encryption' => 'tls',
        ]);

        $mailable = new MarketingEmail(subject: 'Test', htmlContent: '<p>Hello</p>');
        $emailService = app(EmailService::class);

        expect(fn () => $emailService->send($this->user, $mailable, 'r@example.com', $campaign))
            ->toThrow(Exception::class, 'campaign-smtp.invalid');
    });

    test('user custom SMTP is used when campaign has none', function () {
        PlatformSmtpSetting::create([
            'host' => 'platform-smtp.invalid',
            'port' => 587,
            'username' => 'platform@example.com',
            'password' => 'password',
            'from_email' => 'platform@example.com',
            'from_name' => 'Platform',
            'daily_limit' => 1000,
            'is_active' => true,
        ]);

        SmtpSetting::create([
            'user_id' => $this->user->id,
            'is_custom' => true,
            'host' => 'user-smtp.invalid',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'encryption' => 'tls',
            'from_email' => 'user@example.com',
            'from_name' => 'User',
            'sends_per_minute' => 30,
        ]);

        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $mailable = new MarketingEmail(subject: 'Test', htmlContent: '<p>Hello</p>');
        $emailService = app(EmailService::class);

        expect(fn () => $emailService->send($this->user, $mailable, 'r@example.com', $campaign))
            ->toThrow(Exception::class, 'user-smtp.invalid');
    });

    test('default mailer (env) is used when no custom SMTP exists', function () {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'default-smtp.invalid',
            'mail.mailers.smtp.port' => 587,
        ]);

        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $mailable = new MarketingEmail(subject: 'Test', htmlContent: '<p>Hello</p>');
        $emailService = app(EmailService::class);

        expect(fn () => $emailService->send($this->user, $mailable, 'r@example.com', $campaign))
            ->toThrow(Exception::class, 'default-smtp.invalid');
    });
});

describe('Campaign sending settings', function () {
    test('user can update sending settings on a campaign', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->put(route('campaigns.update', $campaign), [
            'sends_per_minute' => 45,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'use_custom_smtp' => true,
            'smtp_host' => 'smtp.later.com',
            'smtp_port' => 465,
            'smtp_username' => 'later@example.com',
            'smtp_password' => 'later-password',
            'smtp_encryption' => 'ssl',
            'smtp_from_email' => 'later@example.com',
            'smtp_from_name' => 'Later Sender',
        ]);

        $response->assertRedirect();

        $campaign->refresh();
        expect($campaign->sends_per_minute)->toBe(45);
        expect($campaign->smtp_host)->toBe('smtp.later.com');
        expect($campaign->smtp_password)->toBe('later-password');
        expect($campaign->smtp_from_name)->toBe('Later Sender');
    });

    test('disabling custom SMTP clears the stored SMTP fields', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create([
            'user_id' => $this->user->id,
            'use_custom_smtp' => true,
            'smtp_host' => 'smtp.turnoff.com',
            'smtp_port' => 587,
            'smtp_username' => 'turnoff@example.com',
            'smtp_password' => 'password',
        ]);

        $this->put(route('campaigns.update', $campaign), [
            'use_custom_smtp' => false,
        ]);

        $campaign->refresh();
        expect($campaign->use_custom_smtp)->toBeFalse();
        expect($campaign->smtp_host)->toBeNull();
        expect($campaign->smtp_password)->toBeNull();
    });

    test('blank SMTP password keeps the existing password', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create([
            'user_id' => $this->user->id,
            'use_custom_smtp' => true,
            'smtp_host' => 'smtp.keep.com',
            'smtp_port' => 587,
            'smtp_username' => 'keep@example.com',
            'smtp_password' => 'original-password',
        ]);

        $this->put(route('campaigns.update', $campaign), [
            'use_custom_smtp' => true,
            'smtp_host' => 'smtp.keep.com',
            'smtp_port' => 587,
            'smtp_username' => 'keep@example.com',
            'smtp_password' => '',
        ]);

        $campaign->refresh();
        expect($campaign->smtp_password)->toBe('original-password');
    });

    test('user can update SMTP settings after the campaign has been sent', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->sent()->create([
            'user_id' => $this->user->id,
            'use_custom_smtp' => true,
            'smtp_host' => 'old-smtp.example.com',
        ]);

        $this->put(route('campaigns.update', $campaign), [
            'use_custom_smtp' => true,
            'smtp_host' => 'new-smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'new@example.com',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => 'new@example.com',
            'smtp_from_name' => 'New Sender',
        ])->assertRedirect();

        $campaign->refresh();
        expect($campaign->smtp_host)->toBe('new-smtp.example.com');
        expect($campaign->smtp_from_name)->toBe('New Sender');
        expect($campaign->status)->toBe('sent');
    });
});

describe('Campaign send rate resolution', function () {
    test('campaign sends_per_minute overrides user and platform settings', function () {
        PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 1000,
            'sends_per_minute' => 10,
            'is_active' => true,
        ]);

        SmtpSetting::create([
            'user_id' => $this->user->id,
            'is_custom' => true,
            'host' => 'smtp.user.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'sends_per_minute' => 20,
        ]);

        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'sends_per_minute' => 30,
        ]);

        expect($campaign->resolveSendsPerMinute())->toBe(30);
    });

    test('user sends_per_minute is used when campaign has no override', function () {
        PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 1000,
            'sends_per_minute' => 10,
            'is_active' => true,
        ]);

        SmtpSetting::create([
            'user_id' => $this->user->id,
            'is_custom' => true,
            'host' => 'smtp.user.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'sends_per_minute' => 20,
        ]);

        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        expect($campaign->resolveSendsPerMinute())->toBe(20);
    });

    test('defaults to 60 when nothing is configured', function () {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        expect($campaign->resolveSendsPerMinute())->toBe(60);
    });
});

describe('CampaignService with imported body', function () {
    test('sendToRecipient renders the campaign body_html and passes the campaign to EmailService', function () {
        $this->campaign = Campaign::factory()->draft()->create([
            'user_id' => $this->user->id,
            'template_id' => null,
            'body_html' => '<h1>Welcome {{name}}!</h1>',
        ]);
        $this->recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
            'name' => 'Jane Doe',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type(MarketingEmail::class), Mockery::type('string'), Mockery::on(fn ($c) => $c->id === $this->campaign->id))
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                expect($mailable->htmlContent)->toContain('<h1>Welcome Jane Doe!</h1>');
                expect($mailable->htmlContent)->not->toContain('{{name}}');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });
});

describe('Scheduled campaign dispatch', function () {
    test('scheduled command uses campaign send rate for job delays', function () {
        $this->actingAs($this->user);

        Queue::fake();

        PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 1000,
            'sends_per_minute' => 60,
            'is_active' => true,
        ]);

        $campaign = Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->subMinute(),
            'sends_per_minute' => 30,
        ]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $this->artisan('campaigns:send-scheduled')->assertSuccessful();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
        ]);

        Queue::assertPushed(SendCampaignEmail::class, 3);

        $delays = collect(Queue::pushed(SendCampaignEmail::class))
            ->map(fn ($job) => $job->delay?->getTimestamp() ?? null)
            ->filter()
            ->values();

        expect($delays)->toHaveCount(2);
    });
});
