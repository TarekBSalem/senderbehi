<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;

class RecipientController extends Controller
{
    public function index(Campaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $recipients = $campaign->recipients()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'recipients' => $recipients,
            'stats' => [
                'total' => $campaign->total_recipients,
                'sent' => $campaign->sent_count,
                'failed' => $campaign->failed_count,
            ],
        ]);
    }

    public function store(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot add recipients to a sent campaign',
            ], 422);
        }

        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string|max:255',
        ]);

        $added = 0;
        $duplicates = 0;
        $invalid = 0;

        foreach ($validated['recipients'] as $recipient) {
            // Check for duplicates
            $exists = $campaign->recipients()
                ->where('email', $recipient['email'])
                ->exists();

            if ($exists) {
                $duplicates++;

                continue;
            }

            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'status' => 'pending',
            ]);

            $added++;
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'duplicates' => $duplicates,
            'invalid' => $invalid,
        ]);
    }

    public function importCsv(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot add recipients to a sent campaign',
            ], 422);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        // Find email column
        $emailIndex = array_search('email', array_map('strtolower', $header));

        if ($emailIndex === false) {
            return response()->json([
                'success' => false,
                'error' => 'CSV must have an "email" column',
            ], 422);
        }

        // Find name column (optional)
        $nameIndex = array_search('name', array_map('strtolower', $header));

        $added = 0;
        $duplicates = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $email = $row[$emailIndex] ?? null;
            $name = $nameIndex !== false ? ($row[$nameIndex] ?? null) : null;

            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $exists = $campaign->recipients()
                ->where('email', $email)
                ->exists();

            if ($exists) {
                $duplicates++;

                continue;
            }

            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'email' => $email,
                'name' => $name,
                'status' => 'pending',
            ]);

            $added++;
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'added' => $added,
            'duplicates' => $duplicates,
        ]);
    }

    public function destroy(Campaign $campaign, CampaignRecipient $recipient)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($recipient->status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot remove a recipient that has already been sent',
            ], 422);
        }

        $recipient->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
