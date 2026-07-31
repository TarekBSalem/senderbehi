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
        $validated = $this->validateCampaign($request);

        if (! $request->filled('template_id') && ! $request->filled('body_html')) {
            return back()->withErrors([
                'body_html' => 'Choose a template or provide an HTML body for the campaign.',
            ])->withInput();
        }

        $campaign = Campaign::create([
            'user_id' => auth()->id(),
            ...$this->normalizeSmtpFields($validated, $request),
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

        $previewHtml = null;

        if ($campaign->template || $campaign->body_html) {
            $variables = [
                'name' => 'Sample Recipient',
                'email' => 'recipient@example.com',
                'campaign_name' => $campaign->name,
            ];

            $bodyHtml = $campaign->renderBody($variables);

            $previewHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;padding:0;font-family:system-ui,sans-serif">'.$bodyHtml.'</body></html>';
        }

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
            'previewHtml' => $previewHtml,
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

        $validated = $this->validateCampaign($request, true);

        if ($request->has('template_id') && ! $request->filled('template_id')
            && $request->has('body_html') && ! $request->filled('body_html')) {
            return back()->withErrors([
                'body_html' => 'Choose a template or provide an HTML body for the campaign.',
            ])->withInput();
        }

        $validated = $this->normalizeSmtpFields($validated, $request);

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully');
    }

    private function validateCampaign(Request $request, bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes|' : '';

        return $request->validate([
            'name' => $prefix.'required|string|max:255',
            'subject' => $prefix.'required|string|max:255',
            'template_id' => 'nullable|exists:templates,id',
            'body_html' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'sends_per_minute' => 'nullable|integer|min:1|max:600',
            'use_custom_smtp' => 'nullable|boolean',
            'smtp_host' => 'required_if:use_custom_smtp,true|nullable|string|max:255',
            'smtp_port' => 'required_if:use_custom_smtp,true|nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
            'smtp_from_email' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
        ]);
    }

    private function normalizeSmtpFields(array $validated, Request $request): array
    {
        if ($request->has('scheduled_at') && ($validated['scheduled_at'] ?? null) === null) {
            $validated['scheduled_at'] = null;
        }

        if (! $request->has('use_custom_smtp')) {
            return $validated;
        }

        if (! $validated['use_custom_smtp']) {
            foreach (['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name'] as $field) {
                $validated[$field] = null;
            }
        } elseif (! $request->filled('smtp_password')) {
            unset($validated['smtp_password']);
        }

        return $validated;
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign has already been sent or is in queue');
        }

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture() && $campaign->status !== 'scheduled') {
            $campaign->update(['status' => 'scheduled']);

            return back()->with('success', 'Campaign scheduled for '.$campaign->scheduled_at->toDayDateTimeString());
        }

        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->count();

        if ($pendingRecipients === 0) {
            return back()->with('error', 'No recipients to send to');
        }

        $user = $campaign->user;

        if (! $user->canSend($pendingRecipients)) {
            $remaining = $user->quotaRemaining();

            return back()->with('error', "Monthly email limit exceeded. You have {$remaining} emails remaining this month.");
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
