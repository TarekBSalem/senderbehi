<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailTracking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignAnalyticsController extends Controller
{
    public function index(Campaign $campaign): Response
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $totalSent = $campaign->sent_count;
        $totalFailed = $campaign->failed_count;
        $totalBounced = $campaign->recipients()->where('status', 'bounced')->count();
        $totalPending = $campaign->recipients()->where('status', 'pending')->count();

        $tracking = EmailTracking::where('campaign_id', $campaign->id)
            ->whereIn('type', ['open', 'click'])
            ->get();

        $uniqueOpens = $tracking->where('type', 'open')->pluck('recipient_id')->unique()->count();
        $uniqueClicks = $tracking->where('type', 'click')->pluck('recipient_id')->unique()->count();
        $totalOpenEvents = $tracking->where('type', 'open')->count();
        $totalClickEvents = $tracking->where('type', 'click')->count();

        $openRate = $totalSent > 0 ? round(($uniqueOpens / $totalSent) * 100, 1) : 0;
        $clickRate = $totalSent > 0 ? round(($uniqueClicks / $totalSent) * 100, 1) : 0;
        $clickToOpenRate = $uniqueOpens > 0 ? round(($uniqueClicks / $uniqueOpens) * 100, 1) : 0;
        $bounceRate = $totalSent > 0 ? round(($totalBounced / ($totalSent + $totalBounced)) * 100, 1) : 0;

        $timeline = $this->buildTimeline($campaign, $tracking);

        $deviceBreakdown = $this->buildDeviceBreakdown($tracking);

        $browserBreakdown = $this->buildBrowserBreakdown($tracking);

        $recipients = $this->buildRecipientData($campaign);

        return Inertia::render('campaigns/analytics', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
            ],
            'stats' => [
                'total_sent' => $totalSent,
                'total_failed' => $totalFailed,
                'total_bounced' => $totalBounced,
                'total_pending' => $totalPending,
                'unique_opens' => $uniqueOpens,
                'unique_clicks' => $uniqueClicks,
                'total_open_events' => $totalOpenEvents,
                'total_click_events' => $totalClickEvents,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
                'click_to_open_rate' => $clickToOpenRate,
                'bounce_rate' => $bounceRate,
            ],
            'timeline' => $timeline,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'recipients' => $recipients,
        ]);
    }

    public function export(Campaign $campaign): JsonResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $campaign->load('recipients');

        $tracking = EmailTracking::where('campaign_id', $campaign->id)
            ->whereIn('type', ['open', 'click'])
            ->get()
            ->groupBy('recipient_id');

        $rows = $campaign->recipients->map(function ($recipient) use ($tracking) {
            $events = $tracking->get($recipient->id, collect());
            $opens = $events->where('type', 'open')->count();
            $clicks = $events->where('type', 'click')->count();
            $firstOpen = $events->where('type', 'open')->sortBy('occurred_at')->first();
            $lastClick = $events->where('type', 'click')->sortByDesc('occurred_at')->first();

            return [
                'email' => $recipient->email,
                'name' => $recipient->name ?? '',
                'status' => $recipient->status,
                'sent_at' => $recipient->sent_at?->toIso8601String() ?? '',
                'opens' => $opens,
                'clicks' => $clicks,
                'first_opened_at' => $firstOpen?->occurred_at?->toIso8601String() ?? '',
                'last_clicked_at' => $lastClick?->occurred_at?->toIso8601String() ?? '',
                'error_message' => $recipient->error_message ?? '',
            ];
        });

        $headers = ['Email', 'Name', 'Status', 'Sent At', 'Opens', 'Clicks', 'First Opened At', 'Last Clicked At', 'Error'];

        $csv = $this->arrayToCsv($rows->toArray(), $headers);

        return response()->json([
            'csv' => $csv,
            'filename' => "campaign-{$campaign->id}-analytics.csv",
        ]);
    }

    private function buildTimeline(Campaign $campaign, $tracking): array
    {
        $startDate = Carbon::parse($campaign->sent_at ?? $campaign->created_at)->startOfDay();
        $endDate = Carbon::parse(now());

        $grouped = $tracking->groupBy(fn ($t) => $t->occurred_at->format('Y-m-d'));

        $timeline = [];
        $current = $startDate;

        while ($current->lte($endDate)) {
            $key = $current->format('Y-m-d');
            $dayEvents = $grouped->get($key, collect());

            $timeline[] = [
                'date' => $key,
                'opens' => $dayEvents->where('type', 'open')->count(),
                'clicks' => $dayEvents->where('type', 'click')->count(),
            ];

            $current = $current->addDay();
        }

        return $timeline;
    }

    private function buildDeviceBreakdown($tracking): array
    {
        $openTracking = $tracking->where('type', 'open');

        $devices = $openTracking->groupBy(fn ($t) => $this->parseDevice($t->user_agent))
            ->map(fn ($group, $device) => [
                'name' => $device,
                'value' => $group->unique('recipient_id')->count(),
            ])
            ->values()
            ->toArray();

        if (empty($devices)) {
            $devices = [['name' => 'Unknown', 'value' => 0]];
        }

        return $devices;
    }

    private function buildBrowserBreakdown($tracking): array
    {
        $openTracking = $tracking->where('type', 'open');

        $browsers = $openTracking->groupBy(fn ($t) => $this->parseBrowser($t->user_agent))
            ->map(fn ($group, $browser) => [
                'name' => $browser,
                'value' => $group->unique('recipient_id')->count(),
            ])
            ->values()
            ->toArray();

        if (empty($browsers)) {
            $browsers = [['name' => 'Unknown', 'value' => 0]];
        }

        return $browsers;
    }

    private function buildRecipientData(Campaign $campaign): array
    {
        $recipients = $campaign->recipients;

        $trackingByRecipient = EmailTracking::where('campaign_id', $campaign->id)
            ->whereIn('type', ['open', 'click'])
            ->get()
            ->groupBy('recipient_id');

        return $recipients->map(function ($recipient) use ($trackingByRecipient) {
            $events = $trackingByRecipient->get($recipient->id, collect());
            $opens = $events->where('type', 'open');
            $clicks = $events->where('type', 'click');

            return [
                'id' => $recipient->id,
                'email' => $recipient->email,
                'name' => $recipient->name,
                'status' => $recipient->status,
                'sent_at' => $recipient->sent_at,
                'opened' => $opens->isNotEmpty(),
                'clicked' => $clicks->isNotEmpty(),
                'open_count' => $opens->count(),
                'click_count' => $clicks->count(),
                'bounced' => $recipient->status === 'bounced',
                'first_opened_at' => $opens->sortBy('occurred_at')->first()?->occurred_at,
                'last_clicked_at' => $clicks->sortByDesc('occurred_at')->first()?->occurred_at,
                'device' => $this->parseDevice($opens->first()?->user_agent),
                'error_message' => $recipient->error_message,
            ];
        })->toArray();
    }

    private function parseDevice(?string $userAgent): string
    {
        if ($userAgent === null) {
            return 'Unknown';
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || str_contains($ua, 'playbook') || str_contains($ua, 'silk')) {
            return 'Tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipod') || str_contains($ua, 'blackberry') || str_contains($ua, 'windows phone')) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function parseBrowser(?string $userAgent): string
    {
        if ($userAgent === null) {
            return 'Unknown';
        }

        $ua = $userAgent;

        if (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg')) {
            return 'Chrome';
        }

        if (str_contains($ua, 'Firefox') && ! str_contains($ua, 'Seamonkey')) {
            return 'Firefox';
        }

        if (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
            return 'Safari';
        }

        if (str_contains($ua, 'Edg')) {
            return 'Edge';
        }

        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) {
            return 'Internet Explorer';
        }

        if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) {
            return 'Opera';
        }

        return 'Other';
    }

    private function arrayToCsv(array $data, array $headers): string
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);

        $csv = stream_get_contents($output);

        fclose($output);

        return $csv;
    }
}
