<?php

namespace App\Services;

use App\Mail\MarketingEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;

class CampaignService
{
    public function __construct(
        private EmailService $emailService
    ) {}

    /**
     * Send campaign to a single recipient
     */
    public function sendToRecipient(Campaign $campaign, CampaignRecipient $recipient): bool
    {
        $user = $campaign->user;
        $template = $campaign->template;

        $variables = [
            'name' => $recipient->name ?? 'Subscriber',
            'email' => $recipient->email,
            'campaign_name' => $campaign->name,
        ];

        $html = $template->render($variables);

        $mailable = new MarketingEmail(
            subject: $campaign->subject,
            htmlContent: $html
        );

        try {
            $this->emailService->send($user, $mailable, $recipient->email);

            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->updateCampaignStats($campaign, true);

            return true;
        } catch (\Exception $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->updateCampaignStats($campaign, false);

            return false;
        }
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
            $campaign->update([
                'status' => $campaign->failed_count > 0 ? 'partial' : 'sent',
                'sent_at' => now(),
            ]);
        }
    }
}
