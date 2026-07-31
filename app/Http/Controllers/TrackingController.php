<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailTracking;
use App\Models\SuppressionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class TrackingController extends Controller
{
    public function trackOpen(int $campaign, int $recipient): Response
    {
        $campaignModel = Campaign::find($campaign);
        $recipientModel = CampaignRecipient::find($recipient);

        if ($campaignModel && $recipientModel) {
            EmailTracking::create([
                'campaign_id' => $campaign,
                'recipient_id' => $recipient,
                'type' => 'open',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
            ]);
        }

        return response(base64_decode(
            'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
        ))->withHeaders([
            'Content-Type' => 'image/gif',
            'Content-Length' => 43,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function trackClick(Request $request, int $campaign, int $recipient): RedirectResponse
    {
        $url = $request->query('url');

        if (! $url) {
            return Redirect::to('/');
        }

        $campaignModel = Campaign::find($campaign);
        $recipientModel = CampaignRecipient::find($recipient);

        if ($campaignModel && $recipientModel) {
            EmailTracking::create([
                'campaign_id' => $campaign,
                'recipient_id' => $recipient,
                'type' => 'click',
                'url' => $url,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
            ]);
        }

        return Redirect::to($url);
    }

    public function unsubscribe(int $campaign, int $recipient): Response
    {
        $recipientModel = CampaignRecipient::find($recipient);
        $campaignModel = Campaign::find($campaign);

        if ($recipientModel) {
            SuppressionList::firstOrCreate(
                ['email' => $recipientModel->email],
                [
                    'reason' => 'manual',
                    'campaign_id' => $campaign,
                    'created_at' => now(),
                ]
            );

            $recipientModel->update(['status' => 'bounced']);
        }

        return response(
            <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f4f4f5; color: #18181b; }
        .card { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        h1 { font-size: 24px; margin: 0 0 8px; }
        p { color: #71717a; margin: 0 0 24px; line-height: 1.6; }
        .btn { display: inline-block; padding: 10px 20px; background: #18181b; color: #fafafa; text-decoration: none; border-radius: 6px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Unsubscribed</h1>
        <p>You have been successfully unsubscribed from this mailing list. You will no longer receive emails from this sender.</p>
        <a class="btn" href="/">Back to Home</a>
    </div>
</body>
</html>
HTML
        )->withHeaders(['Content-Type' => 'text/html']);
    }
}
