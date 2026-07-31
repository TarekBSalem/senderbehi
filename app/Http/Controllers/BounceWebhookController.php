<?php

namespace App\Http\Controllers;

use App\Services\BounceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BounceWebhookController extends Controller
{
    public function __construct(
        private BounceService $bounceService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'event' => 'required|in:bounce,complaint,soft_bounce',
            'campaign_id' => 'sometimes|integer|exists:campaigns,id',
        ]);

        match ($validated['event']) {
            'bounce' => $this->bounceService->processHardBounce(
                $validated['email'],
                $validated['campaign_id'] ?? null,
            ),
            'soft_bounce' => $this->bounceService->processSoftBounce(
                $validated['email'],
                $validated['campaign_id'] ?? null,
            ),
            'complaint' => $this->bounceService->processComplaint(
                $validated['email'],
                $validated['campaign_id'] ?? null,
            ),
        };

        return response()->json(['status' => 'ok']);
    }
}
