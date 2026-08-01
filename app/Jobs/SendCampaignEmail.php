<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MailLog;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient
    ) {}

    public function handle(CampaignService $campaignService): void
    {
        $user = $this->campaign->user;

        if (! $user->canSend()) {
            $this->fail(new \RuntimeException('Monthly email limit reached.'));

            return;
        }

        $campaignService->sendToRecipient($this->campaign, $this->recipient);

        $user->incrementMonthlyEmailCount();
    }

    public function failed(\Throwable $exception): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        MailLog::create([
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'user_id' => $this->campaign->user_id,
            'recipient_email' => $this->recipient->email,
            'event' => 'job_failed',
            'error_message' => $exception->getMessage(),
            'attempt' => $this->recipient->attempt_count,
        ]);

        $this->campaign->update([
            'status' => 'failed',
        ]);
    }
}
