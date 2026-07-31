<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubscriptionPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SubscriptionPlan::sorted();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $plans = $query->paginate(15)
            ->through(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'price_cents' => $plan->price_cents,
                'email_limit' => $plan->email_limit,
                'features' => $plan->features,
                'sort_order' => $plan->sort_order,
                'is_active' => $plan->is_active,
                'created_at' => $plan->created_at,
            ]);

        return Inertia::render('admin/subscription-plans/index', [
            'plans' => $plans,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/subscription-plans/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subscription_plans,code',
            'price_cents' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'email_limit' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $plan = SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscription-plans.edit', $plan)
            ->with('success', 'Subscription plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan): Response
    {
        return Inertia::render('admin/subscription-plans/edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'stripe_price_id' => $plan->stripe_price_id,
                'price_cents' => $plan->price_cents,
                'currency' => $plan->currency,
                'email_limit' => $plan->email_limit,
                'features' => $plan->features,
                'sort_order' => $plan->sort_order,
                'is_active' => $plan->is_active,
                'created_at' => $plan->created_at,
            ],
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subscription_plans,code,'.$plan->id,
            'price_cents' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'email_limit' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $plan->update($validated);

        return redirect()->route('admin.subscription-plans.edit', $plan)
            ->with('success', 'Subscription plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan deleted.');
    }
}
