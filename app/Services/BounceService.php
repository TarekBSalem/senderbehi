<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\EmailTracking;
use App\Models\SuppressionList;

class BounceService
{
    public function processBounce(string $email, string $reason = 'bounce', ?int $campaignId = null): void
    {
        SuppressionList::firstOrCreate(
            ['email' => $email],
            [
                'reason' => $reason === 'complaint' ? 'complaint' : 'bounce',
                'campaign_id' => $campaignId,
                'created_at' => now(),
            ]
        );

        $recipients = CampaignRecipient::where('email', $email);

        if ($campaignId) {
            $recipients->where('campaign_id', $campaignId);
        }

        $recipients->whereIn('status', ['pending', 'sent'])
            ->chunk(100, function ($chunk) use ($reason) {
                foreach ($chunk as $recipient) {
                    $recipient->update([
                        'status' => 'bounced',
                        'error_message' => match ($reason) {
                            'complaint' => 'Recipient reported as spam',
                            'soft' => 'Soft bounce — temporary delivery failure',
                            default => 'Hard bounce — permanent delivery failure',
                        },
                    ]);

                    EmailTracking::create([
                        'campaign_id' => $recipient->campaign_id,
                        'recipient_id' => $recipient->id,
                        'type' => $reason === 'complaint' ? 'complaint' : 'bounce',
                        'occurred_at' => now(),
                    ]);
                }
            });
    }

    public function processHardBounce(string $email, ?int $campaignId = null): void
    {
        $this->processBounce($email, 'bounce', $campaignId);
    }

    public function processSoftBounce(string $email, ?int $campaignId = null): void
    {
        $this->processBounce($email, 'soft', $campaignId);
    }

    public function processComplaint(string $email, ?int $campaignId = null): void
    {
        $this->processBounce($email, 'complaint', $campaignId);
    }
}
