---
name: admin-management
description: Use when building admin panels for user management, subscription plans, usage quotas, platform-wide analytics, template CRUD, category management, or in-app notifications.
---

# Admin Business Management

## Key Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/AdminSmtpController.php` | Existing admin SMTP controller (pattern to follow) |
| `app/Http/Controllers/Admin/DashboardController.php` | Existing admin dashboard (pattern to follow) |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | Admin guard middleware |
| `app/Models/User.php` | `is_admin` boolean field |
| `resources/js/pages/admin/smtp-settings.tsx` | Existing admin page (pattern to follow) |
| `resources/js/components/app-sidebar.tsx` | Sidebar navigation |

## Existing Admin Patterns

### Controller Pattern (from AdminSmtpController)
```php
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminXxxController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/some-page', [
            'data' => [...],
        ]);
    }
}
```

### Route Pattern (from routes/web.php)
```php
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('xxx', [AdminXxxController::class, 'index'])->name('xxx.index');
});
```

### Frontend Page Pattern
- Pages live in `resources/js/pages/admin/`
- Layout uses `AppLayout` (sidebar included automatically)
- Exports `PageName.layout = { breadcrumbs: [...] }`

## Admin Navigation
Sidebar admin items go in `app-sidebar.tsx` `mainNavItems` array.
Only visible to admin users (check `auth.user.is_admin` in component).

## Permissions
- `is_admin` boolean on `users` table
- `EnsureUserIsAdmin` middleware checks `auth()->user()?->is_admin`
- Future: use a proper permissions/spatie table when roles expand

## Implementation Order (Admin Features)

1. **User management** — list all users, search, view their campaigns, toggle active/suspended
2. **Subscription plans CRUD** — table with Free/Starter/Pro/Business, set price, email limit, features
3. **User subscription assignment** — assign a plan to a user manually (before Stripe)
4. **Usage quotas** — enforce email limits per plan, block overage, track usage
5. **Platform analytics** — total users, active campaigns, emails sent today/this month, MRR
6. **Template CRUD** — create/edit/delete system templates from admin panel
7. **Category management** — manage template categories
8. **Notifications** — send in-app announcements to all users

## Admin Dashboard Layout
- Use cards for stats (like `dashboard.tsx`)
- Use tables for list data (like `campaigns/index.tsx`)
- Use shadcn `Card`, `Table`, `Dialog` components
- Breadcrumbs: Admin > Feature Name
