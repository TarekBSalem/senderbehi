<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        $campaigns = auth()->user()->campaigns()
            ->with('template')
            ->latest()
            ->paginate(10);

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): Response
    {
        $templates = Template::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('campaigns/create', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'required|exists:templates,id',
            'subject' => 'required|string|max:255',
        ]);

        $campaign = Campaign::create([
            'user_id' => auth()->id(),
            ...$validated,
            'status' => 'draft',
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully');
    }

    public function show(Campaign $campaign): Response
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $campaign->load('template', 'recipients');

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $campaign->load('template');
        $templates = Template::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('campaigns/create', [
            'campaign' => $campaign,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully');
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($campaign->status !== 'draft') {
            return back()->with('error', 'Campaign has already been sent or is in queue');
        }

        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->count();

        if ($pendingRecipients === 0) {
            return back()->with('error', 'No recipients to send to');
        }

        // Update campaign status
        $campaign->update([
            'status' => 'queued',
            'total_recipients' => $pendingRecipients,
        ]);

        // Dispatch jobs in chunks of 100 to avoid memory issues
        $campaign->recipients()
            ->where('status', 'pending')
            ->chunk(100, fn ($recipients) => $recipients->each(
                fn ($recipient) => SendCampaignEmail::dispatch($campaign, $recipient)
            ));

        return back()->with('success', 'Campaign queued for sending');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted');
    }
}
