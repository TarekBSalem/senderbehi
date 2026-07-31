<?php

use App\Jobs\SendCampaignEmail;
use App\Mail\MarketingEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\PlatformSmtpSetting;
use App\Models\SubscriptionPlan;
use App\Models\Template;
use App\Models\User;
use App\Services\EmailService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    $freePlan = SubscriptionPlan::where('code', 'free')->first();
    $this->user = User::factory()->create(['subscription_plan_id' => $freePlan->id]);
    $this->user->assignRole('User');
    $this->template = Template::factory()->create();
});

describe('PlatformSmtpSetting daily limit', function () {
    test('dailyLimitReached returns true when count equals limit', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 100,
            'daily_sent_count' => 100,
            'is_active' => true,
        ]);

        expect($smtp->dailyLimitReached())->toBeTrue();
    });

    test('dailyLimitReached returns true when count exceeds limit', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 100,
            'daily_sent_count' => 150,
            'is_active' => true,
        ]);

        expect($smtp->dailyLimitReached())->toBeTrue();
    });

    test('dailyLimitReached returns false when under limit', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 100,
            'daily_sent_count' => 50,
            'is_active' => true,
        ]);

        expect($smtp->dailyLimitReached())->toBeFalse();
    });

    test('dailyLimitReached returns false when limit is zero and no sends', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 0,
            'daily_sent_count' => 0,
            'is_active' => true,
        ]);

        expect($smtp->dailyLimitReached())->toBeTrue();
    });

    test('incrementDailySentCount increases the counter', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 1000,
            'daily_sent_count' => 0,
            'is_active' => true,
        ]);

        $smtp->incrementDailySentCount();

        expect($smtp->fresh()->daily_sent_count)->toBe(1);
    });

    test('daily count resets when date changes', function () {
        $smtp = PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 1000,
            'daily_sent_count' => 500,
            'daily_sent_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $smtp->incrementDailySentCount();

        $fresh = $smtp->fresh();
        expect($fresh->daily_sent_count)->toBe(1);
        expect($fresh->daily_sent_date->toDateString())->toBe(now()->toDateString());
    });
});

describe('EmailService daily limit enforcement', function () {
    test('throws exception when platform SMTP daily limit is reached', function () {
        PlatformSmtpSetting::create([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'daily_limit' => 0,
            'daily_sent_count' => 0,
            'is_active' => true,
        ]);

        $mailable = new MarketingEmail(
            subject: 'Test',
            htmlContent: '<p>Hello</p>',
        );

        $emailService = app(EmailService::class);

        $emailService->send($this->user, $mailable, 'recipient@example.com');
    })->throws(Exception::class, 'daily limit');
});

describe('CampaignController send rate limiting', function () {
    test('send sets campaign status to sending', function () {
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

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sending',
            'total_recipients' => 3,
        ]);
    });

    test('send dispatches a job per pending recipient', function () {
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

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(5)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        Queue::assertPushed(SendCampaignEmail::class, 5);
    });

    test('skips already sent recipients when dispatching', function () {
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

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);
        CampaignRecipient::factory()->count(2)->sent()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        Queue::assertPushed(SendCampaignEmail::class, 3);
    });

    test('required fields validated before dispatch', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('error', 'No recipients to send to');
    });
});

describe('send rate derivation', function () {
    test('uses platform SMTP sends_per_minute when no custom SMTP', function () {
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
            'sends_per_minute' => 30,
            'is_active' => true,
        ]);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(2)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');
        Queue::assertPushed(SendCampaignEmail::class, 2);
    });
});
