<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            return $this->recipientError('Cannot add recipients to a sent campaign', 422);
        }

        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string|max:255',
        ]);

        $added = 0;
        $duplicates = 0;

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

        if ($request->inertia()) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Added '.$added.' recipient'.($added === 1 ? '' : 's').($duplicates > 0 ? ' ('.$duplicates.' duplicate'.($duplicates === 1 ? '' : 's').' skipped)' : ''),
            ]);
            Inertia::flash('importResult', [
                'added' => $added,
                'duplicates' => $duplicates,
                'invalid' => 0,
            ]);

            return back();
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'duplicates' => $duplicates,
        ]);
    }

    public function importCsv(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($campaign->status !== 'draft') {
            return $this->recipientError('Cannot add recipients to a sent campaign', 422);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = $this->detectCsvDelimiter($firstLine ?: '');
        $header = array_map(
            fn (string $column) => trim($column, " \t\n\r\0\x0B\xEF\xBB\xBF"),
            fgetcsv($handle, 0, $delimiter) ?: []
        );

        // Find email column
        $emailIndex = array_search('email', array_map('strtolower', $header));

        if ($emailIndex === false) {
            fclose($handle);

            return $this->recipientError('CSV must have an "email" column', 422);
        }

        // Find name column (optional)
        $nameIndex = array_search('name', array_map('strtolower', $header));

        $added = 0;
        $duplicates = 0;
        $invalid = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $email = isset($row[$emailIndex]) ? trim($row[$emailIndex]) : null;
            $name = $nameIndex !== false && isset($row[$nameIndex]) ? trim($row[$nameIndex]) : null;

            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;

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

        if ($request->inertia()) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Imported '.$added.' recipient'.($added === 1 ? '' : 's').($duplicates > 0 ? ' ('.$duplicates.' duplicate'.($duplicates === 1 ? '' : 's').' skipped)' : ''),
            ]);
            Inertia::flash('importResult', [
                'added' => $added,
                'duplicates' => $duplicates,
                'invalid' => $invalid,
            ]);

            return back();
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'duplicates' => $duplicates,
        ]);
    }

    public function destroy(Request $request, Campaign $campaign, CampaignRecipient $recipient)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if ($recipient->status !== 'pending') {
            return $this->recipientError('Cannot remove a recipient that has already been sent', 422);
        }

        $recipient->delete();

        if ($request->inertia()) {
            return $this->recipientSuccess('Recipient removed');
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function resend(Request $request, Campaign $campaign): JsonResponse|RedirectResponse
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        $recipientIds = $request->input('recipient_ids');

        $query = $campaign->recipients()->whereIn('status', ['failed', 'sent']);

        if (is_array($recipientIds) && count($recipientIds) > 0) {
            $query->whereIn('id', $recipientIds);
        } else {
            $query->where('status', 'failed');
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            return $this->recipientError('No resendable recipients selected', 422);
        }

        $user = $campaign->user;

        if (! $user->canSend($recipients->count())) {
            $remaining = $user->quotaRemaining();

            return back()->with('error', "Monthly email limit exceeded. You have {$remaining} emails remaining this month.");
        }

        $sendsPerMinute = $campaign->resolveSendsPerMinute();

        $delayPerEmail = (int) ceil(60 / max(1, $sendsPerMinute));

        $campaign->update(['status' => 'sending']);

        $recipientIdsToResend = $recipients->pluck('id');

        $campaign->recipients()
            ->whereIn('id', $recipientIdsToResend)
            ->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

        $delayOffset = 0;

        $campaign->recipients()
            ->whereIn('id', $recipientIdsToResend)
            ->chunk(100, function ($chunk) use ($campaign, &$delayOffset, $delayPerEmail) {
                $chunk->each(function ($recipient) use ($campaign, &$delayOffset, $delayPerEmail) {
                    $job = SendCampaignEmail::dispatch($campaign, $recipient);

                    if ($delayOffset > 0) {
                        $job->delay(now()->addSeconds($delayOffset));
                    }

                    $delayOffset += $delayPerEmail;
                });
            });

        $count = $recipients->count();

        return $this->recipientSuccess("Queued {$count} recipient".($count === 1 ? '' : 's').' for resend');
    }

    private function recipientSuccess(string $message): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }

    private function detectCsvDelimiter(string $line): string
    {
        $best = ',';
        $bestCount = 0;

        foreach ([',', ';', "\t"] as $candidate) {
            $count = substr_count($line, $candidate);

            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    private function recipientError(string $message, int $status = 422): JsonResponse|RedirectResponse
    {
        if (request()->inertia()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $message,
            ]);

            return back();
        }

        return response()->json([
            'success' => false,
            'error' => $message,
        ], $status);
    }
}
