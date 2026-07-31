<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function pricing(): Response
    {
        $plans = SubscriptionPlan::query()
            ->active()
            ->sorted()
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'price_cents' => $plan->price_cents,
                'formatted_price' => $plan->formattedPrice(),
                'email_limit' => $plan->email_limit,
                'formatted_email_limit' => $plan->formattedEmailLimit(),
                'features' => $plan->features,
                'is_free' => $plan->price_cents === 0,
            ]);

        return Inertia::render('public/pricing', [
            'plans' => $plans,
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('public/contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // TODO: Send notification to admin

        return back()->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }

    public function features(): Response
    {
        $features = [
            [
                'title' => 'Drag-and-Drop Templates',
                'description' => 'Create beautiful emails with our intuitive template builder. No coding required.',
                'icon' => 'layout',
            ],
            [
                'title' => 'Smart Scheduling',
                'description' => 'Schedule campaigns to send at the perfect time for maximum engagement.',
                'icon' => 'clock',
            ],
            [
                'title' => 'Detailed Analytics',
                'description' => 'Track opens, clicks, and conversions with real-time reporting.',
                'icon' => 'bar-chart',
            ],
            [
                'title' => 'Custom SMTP',
                'description' => 'Use your own SMTP server for complete control over deliverability.',
                'icon' => 'server',
            ],
            [
                'title' => 'CSV Import',
                'description' => 'Import your subscriber lists from CSV files with ease.',
                'icon' => 'file-text',
            ],
            [
                'title' => 'A/B Testing',
                'description' => 'Test subject lines and content to optimize your campaigns.',
                'icon' => 'split',
            ],
        ];

        return Inertia::render('public/features', [
            'features' => $features,
        ]);
    }
}
