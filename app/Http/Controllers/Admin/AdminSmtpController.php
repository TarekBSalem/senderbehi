<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSmtpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSmtpController extends Controller
{
    public function index(): Response
    {
        $smtp = PlatformSmtpSetting::first();

        return Inertia::render('admin/smtp-settings', [
            'smtp' => $smtp ? [
                'id' => $smtp->id,
                'host' => $smtp->host,
                'port' => $smtp->port,
                'username' => $smtp->username,
                'encryption' => $smtp->encryption,
                'from_email' => $smtp->from_email,
                'from_name' => $smtp->from_name,
                'daily_limit' => $smtp->daily_limit,
                'is_active' => $smtp->is_active,
                'has_password' => ! empty($smtp->password),
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'required|in:tls,ssl',
            'from_email' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'daily_limit' => 'required|integer|min:1|max:100000',
        ]);

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        PlatformSmtpSetting::updateOrCreate([], $validated);

        return back()->with('success', 'SMTP settings updated successfully');
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|in:tls,ssl',
        ]);

        try {
            $connection = sprintf('%s://%s:%d', $validated['encryption'], $validated['host'], $validated['port']);
            $socket = @fsockopen($validated['host'], (int) $validated['port'], $errno, $errstr, 10);

            if (! $socket) {
                return response()->json([
                    'success' => false,
                    'error' => "Could not connect: $errstr ($errno)",
                ]);
            }

            fclose($socket);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
