<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
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
        $campaignService->sendToRecipient($this->campaign, $this->recipient);
    }

    public function failed(\Throwable $exception): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $this->campaign->update([
            'status' => 'failed',
        ]);
    }
}
