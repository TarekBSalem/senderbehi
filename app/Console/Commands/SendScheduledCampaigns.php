<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:send-scheduled';

    protected $description = 'Dispatch campaigns whose scheduled_at time has arrived';

    public function handle(): void
    {
        $campaigns = Campaign::with('user.smtpSetting')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            return;
        }

        $this->info("Found {$campaigns->count()} scheduled campaign(s) to dispatch.");

        foreach ($campaigns as $campaign) {
            $this->dispatchCampaign($campaign);
        }
    }

    private function dispatchCampaign(Campaign $campaign): void
    {
        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->count();

        if ($pendingRecipients === 0) {
            $campaign->update(['status' => 'failed']);
            $this->warn("Campaign {$campaign->id} has no pending recipients, marking as failed.");

            return;
        }

        $user = $campaign->user;

        if (! $user->canSend($pendingRecipients)) {
            $this->warn("User {$user->id} over quota for campaign {$campaign->id}, rescheduling.");
            $campaign->update(['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

            return;
        }

        $sendsPerMinute = $campaign->resolveSendsPerMinute();
        $delayPerEmail = (int) ceil(60 / max(1, $sendsPerMinute));

        $campaign->update([
            'status' => 'sending',
            'total_recipients' => $pendingRecipients,
        ]);

        $delayOffset = 0;

        $campaign->recipients()
            ->where('status', 'pending')
            ->chunk(100, function ($recipients) use ($campaign, &$delayOffset, $delayPerEmail) {
                $recipients->each(function ($recipient) use ($campaign, &$delayOffset, $delayPerEmail) {
                    $job = SendCampaignEmail::dispatch($campaign, $recipient);

                    if ($delayOffset > 0) {
                        $job->delay(now()->addSeconds($delayOffset));
                    }

                    $delayOffset += $delayPerEmail;
                });
            });

        $this->info("Dispatched campaign {$campaign->id} ({$campaign->name}) with {$pendingRecipients} recipients.");
    }
}
