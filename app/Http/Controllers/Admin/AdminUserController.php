<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    private const array AVAILABLE_ROLES = ['Super Admin', 'Admin', 'User', 'Viewer'];

    public function index(Request $request): Response
    {
        $query = User::with('roles');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->role($role);
        }

        if ($status = $request->get('status')) {
            if ($status === 'blocked') {
                $query->whereNotNull('blocked_at');
            } elseif ($status === 'active') {
                $query->whereNull('blocked_at');
            }
        }

        $users = $query->with('subscriptionPlan')
            ->latest()
            ->paginate(15)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'email_verified_at' => $user->email_verified_at,
                'blocked_at' => $user->blocked_at,
                'campaigns_count' => $user->campaigns()->count(),
                'plan' => $user->subscriptionPlan?->only(['id', 'name', 'code', 'email_limit']),
                'monthly_email_count' => $user->monthly_email_count,
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => self::AVAILABLE_ROLES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => self::AVAILABLE_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:'.implode(',', self::AVAILABLE_ROLES),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $user->load('roles', 'subscriptionPlan');

        $campaigns = $user->campaigns()
            ->with('template')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'email_verified_at' => $user->email_verified_at,
                'blocked_at' => $user->blocked_at,
                'created_at' => $user->created_at,
                'campaigns_count' => $user->campaigns()->count(),
                'emails_sent' => $user->campaigns()->sum('sent_count'),
                'plan' => $user->subscriptionPlan?->only(['id', 'name', 'code', 'email_limit']),
                'monthly_email_count' => $user->monthly_email_count,
                'quota_remaining' => $user->quotaRemaining(),
            ],
            'campaigns' => $campaigns->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'template' => $c->template?->name,
                'sent_count' => $c->sent_count,
                'total_recipients' => $c->total_recipients,
                'created_at' => $c->created_at,
            ]),
            'roles' => self::AVAILABLE_ROLES,
            'plans' => SubscriptionPlan::sorted()->get()->map(fn ($p) => $p->only(['id', 'name', 'code', 'email_limit'])),
        ]);
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|exists:subscription_plans,id',
        ]);

        $user->update(['subscription_plan_id' => $validated['plan_id']]);

        return back()->with('success', 'User plan updated successfully.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:'.implode(',', self::AVAILABLE_ROLES),
        ]);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'User role updated successfully.');
    }

    public function block(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot block yourself.');
        }

        $user->update(['blocked_at' => now()]);

        return back()->with('success', 'User suspended successfully.');
    }

    public function unblock(User $user): RedirectResponse
    {
        $user->update(['blocked_at' => null]);

        return back()->with('success', 'User unsuspended successfully.');
    }
}
