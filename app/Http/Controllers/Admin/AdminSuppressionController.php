<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuppressionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminSuppressionController extends Controller
{
    public function index(Request $request): Response
    {
        $suppressions = SuppressionList::query()
            ->with('campaign:id,name')
            ->when($request->filled('search'), fn ($query) => $query->where('email', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('reason'), fn ($query) => $query->where('reason', $request->reason))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/suppressions', [
            'suppressions' => $suppressions,
            'filters' => $request->only(['search', 'reason']),
            'stats' => [
                'total' => SuppressionList::count(),
                'bounce' => SuppressionList::where('reason', 'bounce')->count(),
                'complaint' => SuppressionList::where('reason', 'complaint')->count(),
                'manual' => SuppressionList::where('reason', 'manual')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('suppression_list', 'email')],
            'reason' => ['required', Rule::in(['bounce', 'complaint', 'manual'])],
        ]);

        SuppressionList::create($validated);

        return back()->with('success', 'Email added to suppression list.');
    }

    public function destroy(SuppressionList $suppression): RedirectResponse
    {
        $suppression->delete();

        return back()->with('success', 'Email removed from suppression list.');
    }

    public function clear(): RedirectResponse
    {
        SuppressionList::query()->delete();

        return back()->with('success', 'Suppression list cleared.');
    }
}
