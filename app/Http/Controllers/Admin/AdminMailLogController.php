<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMailLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = MailLog::query()
            ->with('campaign:id,name', 'user:id,name,email')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $search = $request->string('search');

                    $query->where('recipient_email', 'like', "%{$search}%")
                        ->orWhereHas('campaign', fn ($campaign) => $campaign->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->event))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $failedCount = fn (string $event) => MailLog::where('event', $event)->count();

        return Inertia::render('admin/mail-logs', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event']),
            'stats' => [
                'failed_24h' => MailLog::where('event', 'failed')->where('created_at', '>=', now()->subDay())->count(),
                'failed_total' => $failedCount('failed'),
                'sent_24h' => MailLog::where('event', 'sent')->where('created_at', '>=', now()->subDay())->count(),
                'job_failed_total' => $failedCount('job_failed'),
            ],
        ]);
    }

    public function destroy(MailLog $mailLog): RedirectResponse
    {
        $mailLog->delete();

        return back();
    }

    public function clear(): RedirectResponse
    {
        MailLog::query()->delete();

        return back();
    }
}
