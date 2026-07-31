<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SmtpController extends Controller
{
    public function edit(): Response
    {
        $settings = Auth::user()->smtpSetting ?? new SmtpSetting([
            'host' => '',
            'port' => 587,
            'username' => '',
            'encryption' => 'tls',
            'from_email' => '',
            'from_name' => '',
            'sends_per_minute' => 60,
            'is_custom' => false,
        ]);

        return Inertia::render('settings/smtp', [
            'settings' => [
                'is_custom' => $settings->is_custom ?? false,
                'host' => $settings->host ?? '',
                'port' => $settings->port ?? 587,
                'username' => $settings->username ?? '',
                'encryption' => $settings->encryption ?? 'tls',
                'from_email' => $settings->from_email ?? '',
                'from_name' => $settings->from_name ?? '',
                'sends_per_minute' => $settings->sends_per_minute ?? 60,
                'is_active' => $settings->is_active ?? true,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_custom' => 'nullable|boolean',
            'host' => 'required_if:is_custom,true|nullable|string|max:255',
            'port' => 'required_if:is_custom,true|nullable|integer|min:1|max:65535',
            'username' => 'required_if:is_custom,true|nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|string|in:tls,ssl,none',
            'from_email' => 'required_if:is_custom,true|nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'sends_per_minute' => 'nullable|integer|min:1|max:600',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $settings = $user->smtpSetting ?? new SmtpSetting(['user_id' => $user->id]);

        $settings->fill($validated);

        if ($settings->is_custom && ! $request->filled('password')) {
            unset($settings->password);
        }

        if (! $settings->is_custom) {
            $settings->host = null;
            $settings->port = null;
            $settings->username = null;
            $settings->password = null;
            $settings->encryption = null;
            $settings->from_email = null;
            $settings->from_name = null;
        }

        $settings->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SMTP settings updated.']);

        return redirect()->route('smtp.edit');
    }

    public function test(Request $request, EmailService $emailService): JsonResponse
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        try {
            $emailService->testConnection($validated['host'], $validated['port']);

            return response()->json(['success' => true, 'message' => 'Connection successful.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
