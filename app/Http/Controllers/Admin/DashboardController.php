<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $stats = [
            'total_campaigns' => $user->campaigns()->count(),
            'emails_sent' => $user->campaigns()->sum('sent_count'),
            'total_recipients' => $user->campaigns()->sum('total_recipients'),
        ];

        $recentCampaigns = $user->campaigns()
            ->with('template')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }
}
