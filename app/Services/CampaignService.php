<?php

namespace App\Services;

use App\Mail\MarketingEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MailLog;
use App\Models\PlatformSmtpSetting;
use App\Models\SuppressionList;
use App\Models\User;

class CampaignService
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function sendToRecipient(Campaign $campaign, CampaignRecipient $recipient): bool
    {
        $user = $campaign->user;

        if ($this->isSuppressed($recipient->email)) {
            $recipient->update([
                'status' => 'bounced',
                'error_message' => 'Recipient is in suppression list',
            ]);

            $this->logSend($campaign, $recipient, $user, 'bounced', 'Recipient is in suppression list');

            $this->updateCampaignStats($campaign, false);

            return false;
        }

        $appUrl = config('app.url');
        $unsubscribeUrl = "{$appUrl}/unsubscribe/{$campaign->id}/{$recipient->id}";

        $platformSmtp = PlatformSmtpSetting::where('is_active', true)->first();

        $variables = [
            'name' => $recipient->name ?? 'Subscriber',
            'email' => $recipient->email,
            'campaign_name' => $campaign->name,
            'unsubscribe_url' => $unsubscribeUrl,
            'physical_address' => $platformSmtp?->physical_address ?? '',
        ];

        $html = $campaign->renderBody($variables);

        $trackingPixel = '<img src="'."{$appUrl}/track/open/{$campaign->id}/{$recipient->id}".'" alt="" width="1" height="1" style="display:none;" />';

        $html = $this->injectTrackingPixel($html, $trackingPixel);

        $html = $this->rewriteClickLinks($html, $appUrl, $campaign->id, $recipient->id, $unsubscribeUrl);

        $mailable = new MarketingEmail(
            subject: $campaign->subject,
            htmlContent: $html,
            unsubscribeUrl: $unsubscribeUrl,
            returnPath: config('mail.bounce_address'),
        );

        try {
            $recipient->increment('attempt_count');

            $this->emailService->send($user, $mailable, $recipient->email, $campaign);

            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $recipient->increment('success_count');

            $this->logSend($campaign, $recipient, $user, 'sent');

            $this->updateCampaignStats($campaign, true);

            return true;
        } catch (\Exception $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->logSend($campaign, $recipient, $user, 'failed', $e->getMessage());

            $this->updateCampaignStats($campaign, false);

            return false;
        }
    }

    private function logSend(Campaign $campaign, CampaignRecipient $recipient, User $user, string $event, ?string $errorMessage = null): void
    {
        MailLog::create([
            'campaign_id' => $campaign->id,
            'recipient_id' => $recipient->id,
            'user_id' => $user->id,
            'recipient_email' => $recipient->email,
            'event' => $event,
            'smtp_source' => $this->smtpSource($campaign, $user),
            'error_message' => $errorMessage,
            'attempt' => $recipient->attempt_count,
        ]);
    }

    private function smtpSource(Campaign $campaign, User $user): string
    {
        if ($campaign->use_custom_smtp && $campaign->smtp_host) {
            return 'campaign';
        }

        if ($user->smtpSetting?->is_custom) {
            return 'user';
        }

        return 'default';
    }

    public function updateCampaignStats(Campaign $campaign, bool $success): void
    {
        if ($success) {
            $campaign->increment('sent_count');
        } else {
            $campaign->increment('failed_count');
        }

        $remaining = $campaign->recipients()->where('status', 'pending')->count();

        if ($remaining === 0) {
            $hasFailedRecipients = $campaign->recipients()->where('status', 'failed')->exists();

            $campaign->update([
                'status' => $hasFailedRecipients ? 'partial' : 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    private function isSuppressed(string $email): bool
    {
        return SuppressionList::where('email', $email)->exists();
    }

    private function injectTrackingPixel(string $html, string $pixel): string
    {
        $bodyClose = strrpos($html, '</body>');

        if ($bodyClose !== false) {
            return substr_replace($html, $pixel."\n", $bodyClose, 0);
        }

        return $html."\n".$pixel;
    }

    private function rewriteClickLinks(string $html, string $appUrl, int $campaignId, int $recipientId, string $unsubscribeUrl): string
    {
        return preg_replace_callback(
            '/<a\s+[^>]*href\s*=\s*"([^"]*)"[^>]*>/i',
            function (array $matches) use ($appUrl, $campaignId, $recipientId, $unsubscribeUrl) {
                $url = $matches[1];

                if (str_starts_with($url, 'mailto:') || str_starts_with($url, '#') || $url === '') {
                    return $matches[0];
                }

                if (str_contains($url, $unsubscribeUrl)) {
                    return $matches[0];
                }

                $trackingUrl = "{$appUrl}/track/click/{$campaignId}/{$recipientId}?url=".urlencode($url);

                return str_replace('href="'.$url.'"', 'href="'.$trackingUrl.'"', $matches[0]);
            },
            $html
        );
    }
}
