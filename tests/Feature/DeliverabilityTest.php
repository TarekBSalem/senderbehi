<?php

use App\Mail\MarketingEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\PlatformSmtpSetting;
use App\Models\SuppressionList;
use App\Models\Template;
use App\Models\User;
use App\Services\BounceService;
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
    $this->recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $this->campaign->id,
    ]);

    PlatformSmtpSetting::create([
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'test',
        'password' => 'test',
        'encryption' => 'tls',
        'from_email' => 'noreply@example.com',
        'from_name' => 'Test',
        'physical_address' => '123 Main St, Springfield, USA',
        'is_active' => true,
    ]);
});

describe('Open Tracking', function () {
    test('tracking pixel returns GIF with correct headers', function () {
        $response = $this->get(route('track.open', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
        $response->assertHeader('Cache-Control');
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    });

    test('tracking pixel logs an open event', function () {
        $this->get(route('track.open', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $this->assertDatabaseHas('email_tracking', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'open',
        ]);
    });

    test('tracking pixel works for non-existent records without error', function () {
        $response = $this->get(route('track.open', [
            'campaign' => 9999,
            'recipient' => 9999,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
    });
});

describe('Click Tracking', function () {
    test('click tracking redirects to original URL', function () {
        $targetUrl = 'https://example.com/product';

        $response = $this->get(route('track.click', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
            'url' => $targetUrl,
        ]));

        $response->assertRedirect($targetUrl);
    });

    test('click tracking logs a click event', function () {
        $targetUrl = 'https://example.com/product';

        $this->get(route('track.click', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
            'url' => $targetUrl,
        ]));

        $this->assertDatabaseHas('email_tracking', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'click',
            'url' => $targetUrl,
        ]);
    });

    test('click tracking redirects to home when no url provided', function () {
        $response = $this->get(route('track.click', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $response->assertRedirect('/');
    });
});

describe('Unsubscribe', function () {
    test('unsubscribe adds email to suppression list', function () {
        $this->get(route('unsubscribe', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $this->assertDatabaseHas('suppression_list', [
            'email' => $this->recipient->email,
            'reason' => 'manual',
        ]);
    });

    test('unsubscribe marks recipient as bounced', function () {
        $this->get(route('unsubscribe', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $this->recipient->id,
            'status' => 'bounced',
        ]);
    });

    test('unsubscribe returns HTML confirmation page', function () {
        $response = $this->get(route('unsubscribe', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type');
        expect($response->headers->get('Content-Type'))->toContain('text/html');
        $response->assertSee('Unsubscribed');
    });

    test('unsubscribe deduplicates suppression entries', function () {
        $this->get(route('unsubscribe', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $this->get(route('unsubscribe', [
            'campaign' => $this->campaign->id,
            'recipient' => $this->recipient->id,
        ]));

        $this->assertDatabaseCount('suppression_list', 1);
    });
});

describe('Suppression Check', function () {
    test('sendToRecipient skips suppressed recipients', function () {
        SuppressionList::create([
            'email' => $this->recipient->email,
            'reason' => 'manual',
            'campaign_id' => $this->campaign->id,
        ]);

        $campaignService = new CampaignService(app(EmailService::class));

        $result = $campaignService->sendToRecipient($this->campaign, $this->recipient);

        expect($result)->toBeFalse();
        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $this->recipient->id,
            'status' => 'bounced',
            'error_message' => 'Recipient is in suppression list',
        ]);
    });
});

describe('MarketingEmail Headers', function () {
    test('marketing email has List-Unsubscribe header', function () {
        $mailable = new MarketingEmail(
            subject: 'Test',
            htmlContent: '<p>Hello</p>',
            unsubscribeUrl: 'https://example.com/unsubscribe/1/1',
        );

        $headers = $mailable->headers();

        expect($headers->text)->toHaveKey('List-Unsubscribe');
        expect($headers->text['List-Unsubscribe'])->toContain('https://example.com/unsubscribe/1/1');
    });

    test('marketing email has List-Unsubscribe-Post header', function () {
        $mailable = new MarketingEmail(
            subject: 'Test',
            htmlContent: '<p>Hello</p>',
        );

        $headers = $mailable->headers();

        expect($headers->text)->toHaveKey('List-Unsubscribe-Post');
        expect($headers->text['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click');
    });
});

describe('CampaignService Tracking HTML', function () {
    test('sendToRecipient injects tracking pixel into email body', function () {
        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                $html = $mailable->htmlContent;

                expect($html)->toContain('/track/open/');
                expect($html)->toContain('width="1" height="1"');
                expect($html)->toContain('style="display:none;"');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient rewrites links for click tracking', function () {
        $this->template->update([
            'body_html' => '<a href="https://example.com">Click me</a>',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                expect($mailable->htmlContent)->toContain('/track/click/');
                expect($mailable->htmlContent)->toContain('url=https%3A%2F%2Fexample.com');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient includes unsubscribe_url in template variables', function () {
        $this->template->update([
            'body_html' => '<a href="{{unsubscribe_url}}">Unsubscribe</a>',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                expect($mailable->htmlContent)->toContain('/unsubscribe/');
                expect($mailable->htmlContent)->not->toContain('{{unsubscribe_url}}');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient includes physical_address in template variables', function () {
        $this->template->update([
            'body_html' => '{{physical_address}}',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                expect($mailable->htmlContent)->toContain('123 Main St, Springfield, USA');
                expect($mailable->htmlContent)->not->toContain('{{physical_address}}');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient does not rewrite unsubscribe links', function () {
        $this->template->update([
            'body_html' => '<a href="{{unsubscribe_url}}">Unsubscribe</a>',
        ]);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                $html = $mailable->htmlContent;

                expect($html)->toContain('/unsubscribe/');
                expect($html)->not->toContain('track/click');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient passes unsubscribe URL to mailable for List-Unsubscribe header', function () {
        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                $headers = $mailable->headers();

                expect($headers->text['List-Unsubscribe'] ?? '')->toContain('/unsubscribe/');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });

    test('sendToRecipient passes return path to mailable', function () {
        config(['mail.bounce_address' => 'bounces@example.com']);

        $emailService = Mockery::mock(EmailService::class);
        $emailService->shouldReceive('send')
            ->once()
            ->andReturnUsing(function ($user, MarketingEmail $mailable, $email) {
                expect($mailable->returnPath)->toBe('bounces@example.com');

                return true;
            });

        $campaignService = new CampaignService($emailService);
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    });
});

describe('Bounce Processing', function () {
    test('BounceService hard bounce adds to suppression list', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processHardBounce($email, $this->campaign->id);

        $this->assertDatabaseHas('suppression_list', [
            'email' => $email,
            'reason' => 'bounce',
        ]);
    });

    test('BounceService hard bounce marks recipient as bounced', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processHardBounce($email, $this->campaign->id);

        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $this->recipient->id,
            'status' => 'bounced',
            'error_message' => 'Hard bounce — permanent delivery failure',
        ]);
    });

    test('BounceService hard bounce logs email tracking event', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processHardBounce($email, $this->campaign->id);

        $this->assertDatabaseHas('email_tracking', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'bounce',
        ]);
    });

    test('BounceService soft bounce has descriptive error message', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processSoftBounce($email, $this->campaign->id);

        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $this->recipient->id,
            'error_message' => 'Soft bounce — temporary delivery failure',
        ]);
    });

    test('BounceService complaint marks as complaint in suppression list', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processComplaint($email, $this->campaign->id);

        $this->assertDatabaseHas('suppression_list', [
            'email' => $email,
            'reason' => 'complaint',
        ]);
    });

    test('BounceService does not duplicate suppression entries', function () {
        $email = $this->recipient->email;

        app(BounceService::class)->processHardBounce($email, $this->campaign->id);
        app(BounceService::class)->processHardBounce($email, $this->campaign->id);

        $this->assertDatabaseCount('suppression_list', 1);
    });

    test('bounce webhook endpoint processes hard bounce', function () {
        $response = $this->postJson(route('webhooks.bounce'), [
            'email' => $this->recipient->email,
            'event' => 'bounce',
            'campaign_id' => $this->campaign->id,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('suppression_list', [
            'email' => $this->recipient->email,
            'reason' => 'bounce',
        ]);
    });

    test('bounce webhook endpoint processes complaint', function () {
        $response = $this->postJson(route('webhooks.bounce'), [
            'email' => $this->recipient->email,
            'event' => 'complaint',
            'campaign_id' => $this->campaign->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('suppression_list', [
            'email' => $this->recipient->email,
            'reason' => 'complaint',
        ]);
    });

    test('bounce webhook validates required fields', function () {
        $response = $this->postJson(route('webhooks.bounce'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'event']);
    });

    test('bounce webhook validates event enum', function () {
        $response = $this->postJson(route('webhooks.bounce'), [
            'email' => 'test@example.com',
            'event' => 'invalid_event',
        ]);

        $response->assertStatus(422);
    });

    test('bounce webhook is accessible without auth', function () {
        $response = $this->postJson(route('webhooks.bounce'), [
            'email' => 'test@example.com',
            'event' => 'bounce',
        ]);

        $response->assertOk();
    });
});
