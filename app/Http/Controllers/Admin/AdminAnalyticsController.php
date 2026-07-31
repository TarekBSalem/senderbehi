<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnalyticsController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', $startOfMonth)->count();

        $totalCampaigns = Campaign::count();
        $campaignsThisMonth = Campaign::where('created_at', '>=', $startOfMonth)->count();

        $emailsSentAllTime = Campaign::sum('sent_count');
        $emailsSentThisMonth = Campaign::where('created_at', '>=', $startOfMonth)->sum('sent_count');
        $emailsFailedAllTime = Campaign::sum('failed_count');

        $mrrCents = User::whereNotNull('subscription_plan_id')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'users.subscription_plan_id')
            ->where('subscription_plans.price_cents', '>', 0)
            ->sum('subscription_plans.price_cents');

        $dailyVolume = Campaign::where('created_at', '>=', $now->copy()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(sent_count) as sent'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'sent' => (int) $row->sent,
            ]);

        $dailyVolume = $this->fillMissingDates($dailyVolume, $now);

        $campaignsByStatus = Campaign::select(DB::raw('status'), DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ]);

        $recentCampaigns = Campaign::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'sent_count' => $c->sent_count,
                'total_recipients' => $c->total_recipients,
                'user_name' => $c->user?->name,
                'created_at' => $c->created_at,
            ]);

        $planDistribution = User::whereNotNull('subscription_plan_id')
            ->select('subscription_plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('subscription_plan_id')
            ->get()
            ->keyBy('subscription_plan_id')
            ->map(fn ($row) => (int) $row->count);

        $plans = SubscriptionPlan::sorted()->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'user_count' => $planDistribution->get($p->id, 0),
        ]);

        return Inertia::render('admin/analytics', [
            'stats' => [
                'total_users' => $totalUsers,
                'new_users_this_month' => $newUsersThisMonth,
                'total_campaigns' => $totalCampaigns,
                'campaigns_this_month' => $campaignsThisMonth,
                'emails_sent_all_time' => $emailsSentAllTime,
                'emails_sent_this_month' => $emailsSentThisMonth,
                'emails_failed_all_time' => $emailsFailedAllTime,
                'mrr_cents' => $mrrCents,
            ],
            'daily_volume' => $dailyVolume,
            'campaigns_by_status' => $campaignsByStatus,
            'recent_campaigns' => $recentCampaigns,
            'plans' => $plans,
        ]);
    }

    private function fillMissingDates($collection, Carbon $now)
    {
        $dateMap = $collection->keyBy('date');
        $start = $now->copy()->subDays(30);

        for ($date = $start; $date->lte($now); $date->addDay()) {
            $key = $date->format('Y-m-d');

            if (! $dateMap->has($key)) {
                $collection->push(['date' => $key, 'sent' => 0]);
            }
        }

        return $collection->sortBy('date')->values();
    }
}
