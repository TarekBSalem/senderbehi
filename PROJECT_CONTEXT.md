# MailForge — Project Context
# Version 1.0 — July 2026
# This file is the single source of truth for every AI agent working on this project.
# Read it fully before touching anything.
# → For task progress, see PROGRESS.md (update it at the end of every task)

---

## 1. What We Are Building

A SaaS marketing email platform called **MailForge** — a simple, powerful tool for
small businesses and freelancers to create, send, and track email campaigns.

The platform replaces scattered email tools and spreadsheets with one system
covering template management, campaign creation, recipient management,
queue-based sending, and analytics.

MailForge does: campaign creation, template-based email sending, SMTP configuration
(platform + per-user), CSV import, queue dispatching, campaign analytics.

---

## 2. The Business Model

| Tier | Price | Includes |
|------|-------|----------|
| **Free** | $0 | 500 emails/month, 5 templates, basic analytics |
| **Starter** | $9/mo | 5,000 emails/month, all templates, CSV import |
| **Pro** | $29/mo | 25,000 emails/month, open/click tracking, priority support |
| **Business** | $79/mo | 100,000 emails/month, API access, multi-user teams |

### Add-ons (Phase 2+)
| Add-on | Price |
|--------|-------|
| Extra emails | $1 per 1,000 |
| Custom branding | $19/mo |
| Dedicated IP | $49/mo |

---

## 3. Tech Stack — Locked, Do Not Change

| Layer | Technology | Notes |
|---|---|---|
| Backend | Laravel 13, PHP 8.5 | Latest Laravel |
| Frontend | React 19 + Inertia v3 | SPA, TypeScript |
| UI Components | shadcn/ui (Radix UI) | Pre-built accessible components |
| Styling | Tailwind CSS v4 | Utility-first CSS |
| Database | MySQL (production) | SQLite for local dev |
| Queue | Database driver | `php artisan queue:work` |
| Email Transport | Symfony SmtpTransport (raw) | Bypasses Laravel mail config |
| Build Tool | Vite | Dev + production bundling |
| Auth | Laravel Fortify | Login, register, 2FA, passkeys |
| Routes | Laravel Wayfinder | Typed route functions for frontend |
| Code Style | Laravel Pint | PSR-12 + Laravel conventions |
| Tests | Pest PHP | Feature + unit tests |
| Roles/Permissions | Spatie laravel-permission ^8.3 | `$user->hasRole()`, `$user->assignRole()`, `@can()` |
| Billing | Laravel Cashier (Stripe) ^16.6 | `$user->newSubscription()->create()`, `$user->subscribed()` |

**Important notes:**
- Tailwind v4 class names differ from v3 (use `shrink-0` not `flex-shrink-0`, etc.)
- No new packages without strong justification
- All DB writes go through Eloquent models — no raw queries
- RLS not applicable (Laravel, not Supabase)
- Every sensitive action is logged

---

## 4. The 4 User Roles

### Super Admin (you / the business owner)
- Full access to everything in the system
- Manages: all users, subscription plans, platform SMTP, templates (CRUD)
- Views: all campaigns from all users, platform-wide analytics
- Configures: global settings, sending limits, email providers
- Receives notifications on critical events

### Admin (operations / support)
- ~80% of Super Admin capabilities
- Cannot: change global platform config, delete users, manage billing
- Can: view all campaigns, manage templates, help users with issues

### User (customer — default role)
- Manages own campaigns, recipients, templates
- Configures own custom SMTP (optional)
- Views own analytics and campaign stats
- Cannot see other users' data

### Viewer (read-only)
- Can view dashboards and reports
- No create/edit/delete permissions
- Useful for clients or stakeholders

---

## 5. Core Business Rules — Never Violate These

### Rule 1 — Data Isolation
Users can ONLY see their own campaigns, recipients, and analytics.
Super Admin and Admin can see ALL data.
Enforced at controller level with `auth()->id()` checks and 403s.

### Rule 2 — Campaign Status State Machine
```
draft → queued → sending → sent
                       ↘ partial
                            ↘ failed
```
- Only `draft` campaigns can be sent
- Once `sent` or `failed`, a campaign cannot be re-sent
- Status transitions are one-way — never go backward
- `partial` means some emails succeeded, some failed

### Rule 3 — Recipient Deduplication
When adding recipients (manual, JSON, or CSV), duplicate emails within the same campaign are silently skipped. This applies per-campaign, not globally.

### Rule 4 — Sending is Queue-Only
Emails are NEVER sent synchronously during an HTTP request.
Every send goes through `SendCampaignEmail` job → database queue → worker.

### Rule 5 — SMTP Credentials are Encrypted
Both `PlatformSmtpSetting` and `SmtpSetting` passwords are encrypted at rest using Laravel's `Crypt::encryptString()` / auto-decrypted on read via accessors. Never stored in plain text.

### Rule 6 — Chunked Processing
All recipient processing uses `chunk(100)` to avoid memory exhaustion on large campaigns. Never use `->each()` or `->get()` on recipient queries without chunking.

### Rule 7 — 3 Retry Policy
Every send job retries up to 3 times with 60-second backoff between attempts. After all retries fail, the recipient is marked `failed` with the error message captured.

### Rule 8 — Admin Guard (Spatie Roles)
Admin routes are protected by the `admin` middleware which checks `auth()->user()->hasRole('Super Admin')`.
Roles are managed via `spatie/laravel-permission`. Four roles exist: Super Admin, Admin, User, Viewer.
The old `is_admin` boolean column has been migrated to Spatie roles.
Use `$user->assignRole('Super Admin')`, `$user->hasRole('Admin')`, etc.
For granular permissions, use `$user->givePermissionTo()`, `$user->can()`. No permissions are seeded yet — add them in the relevant feature phase.

---

## 6. Current Feature Inventory (Implemented)

| Area | Features | Status |
|------|----------|--------|
| **Auth** | Register, Login, Logout, Password Reset, Email Verification | ✅ |
| **2FA** | TOTP enable/disable, QR code setup, recovery codes, OTP challenge | ✅ |
| **Passkeys** | WebAuthn register/verify/delete, passwordless login | ✅ |
| **Profile** | Update name/email, delete account, change password | ✅ |
| **Appearance** | Light/Dark/System toggle (cookie) | ✅ |
| **Dashboard** | Stats cards (total campaigns, emails sent, total recipients), recent campaigns | ✅ |
| **Templates** | 8 pre-seeded HTML templates, 6 categories, filtering, search, iframe preview | ✅ |
| **Campaigns** | CRUD, status lifecycle (draft→queued→sent/partial/failed) | ✅ |
| **Recipients** | Manual add, bulk JSON, CSV import (email column), dedup, remove pending | ✅ |
| **Sending** | Queue jobs (3 retries, 60s backoff), chunk(100) processing | ✅ |
| **Campaign Stats** | sent_count, failed_count, auto-complete sent/partial | ✅ |
| **Platform SMTP** | Admin-configurable SMTP, encrypted password, test connection | ✅ |
| **User SMTP** | Per-user custom SMTP, encrypted password | (half — model exists, no UI) |
| **Admin Guard** | `is_admin` flag, middleware, admin-only routes | ✅ |
| **Seeders** | TemplateSeeder with 8 email templates | ✅ |
| **Tests** | CampaignTest (21), RecipientTest (17), TemplateTest (6), CampaignServiceTest (5), DashboardTest (2) | ✅ |

---

## 7. Features to Implement — Complete Roadmap

Priority levels: P0 (must have before launch), P1 (launch-critical), P2 (growth), P3 (scale)

### PHASE 1 — Deliverability & Compliance (P0)
*Without these, emails go to spam and the platform is illegal in most countries*

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 1 | **Open tracking** (tracking pixel) | ☐ | 1x1 transparent pixel in email body |
| 2 | **Click tracking** (link wrapping) | ☐ | Rewrite all `href` → redirect URL |
| 3 | **Unsubscribe link** + `List-Unsubscribe` header | ☐ | Required by CAN-SPAM, GDPR, Google/Yahoo mandate |
| 4 | **Bounce handling** (hard vs soft, auto-suppress) | ☐ | Parse SMTP bounce codes |
| 5 | **Suppression list** (global + per-campaign) | ☐ | Block known bad addresses |
| 6 | **Physical address in email footer** | ☐ | CAN-SPAM requirement |
| 7 | **Send rate limiting** (per-campaign throttle) | ☐ | Spread sends over time, respect SMTP limits |

### PHASE 2 — Admin Business Management (P1)
*You cannot run the business without these*

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 8 | **User management** (list, search, view campaigns, suspend/ban) | ☐ | Admin panel to see all users |
| 9 | **Subscription plans** (CRUD plans, map to permissions) | ☐ | Free/Starter/Pro/Business tiers |
| 10 | **User subscription assignment** (admin sets user plan) | ☐ | Manual or Stripe-based |
| 11 | **Usage quotas** (enforce monthly email limits per plan) | ☐ | Track + block overages |
| 12 | **Platform-wide analytics** (total users, campaigns, emails, MRR) | ☐ | Super Admin dashboard |
| 13 | **Template management** (admin CRUD: create, edit, delete templates) | ☐ | Full HTML editor for templates |
| 14 | **Category management** (admin manages template categories) | ☐ | Add/edit/remove categories |
| 15 | **Announcements / notifications to users** | ☐ | In-app notification system |

### PHASE 3 — Vitrine Site & Growth (P1-P2)
*The public face of MailForge*

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 16 | **Landing page** (hero, features, pricing, CTA) | ☐ | SEO-optimized, responsive |
| 17 | **Pricing page** (plan comparison table) | ☐ | Live pricing linked to DB plans |
| 18 | **Documentation / knowledge base** | ☐ | Guides, FAQs, API docs |
| 19 | **Blog** (marketing content) | ☐ | SEO blog for organic traffic |
| 20 | **Contact / sales page** | ☐ | Contact form → admin notification |
| 21 | **Changelog / status page** | ☐ | Public status + release notes |
| 22 | **Onboarding flow** (guided first campaign) | ☐ | Tutorial wizard for new users |
| 23 | **Referral program** (invite friends, get credits) | ☐ | Growth loop |

### PHASE 4 — Analytics & Reporting (P2)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 24 | **Campaign analytics page** (opens, clicks, bounces over time) | ☐ | Charts + metrics |
| 25 | **Recipient-level stats** (who opened, clicked, bounced) | ☐ | Per-email detail |
| 26 | **Geo/device/browser stats** | ☐ | Tracking pixel captures UA + IP |
| 27 | **Export reports** (CSV/PDF) | ☐ | Download campaign data |
| 28 | **A/B testing** (subject line, content variants) | ☐ | Split test + winner selection |

### PHASE 5 — Email Builder & Templates (P2)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 29 | **Drag-and-drop email builder** | ☐ | Block-based editor (rows, text, image, button, divider) |
| 30 | **Save custom templates** (user-owned templates) | ☐ | Separate from system templates |
| 31 | **Template categories** (user-managed) | ☐ | Organize custom templates |
| 32 | **Template versioning** | ☐ | Track changes, rollback |

### PHASE 6 — Automation & Scheduling (P2-P3)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 33 | **Campaign scheduling** (specific date/time) | ☐ | `scheduled_at` column already exists |
| 34 | **Timezone-aware sending** | ☐ | Send in recipient's timezone |
| 35 | **Drip campaigns** (automated sequences) | ☐ | Trigger-based email series |
| 36 | **Triggered emails** (welcome, birthday, etc.) | ☐ | Event → action |
| 37 | **Re-engagement automation** | ☐ | Auto-send to inactive subscribers |

### PHASE 7 — API & Integrations (P3)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 38 | **REST API** (Laravel Sanctum) | ☐ | Campaigns, recipients, templates |
| 39 | **API keys management** (generate/revoke per user) | ☐ | Developer portal |
| 40 | **Webhooks** (sent, opened, clicked, bounced, complained) | ☐ | POST to user's URL |
| 41 | **SendGrid integration** | ☐ | Alternative to raw SMTP |
| 42 | **AWS SES integration** | ☐ | Scalable email service |
| 43 | **Mailgun integration** | ☐ | Alternative email service |
| 44 | **Zapier integration** | ☐ | No-code automation |
| 45 | **Import from Mailchimp / other platforms** | ☐ | Migration tool |

### PHASE 8 — Billing (P3)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 46 | **Stripe integration** | ☐ | Payment processing |
| 47 | **Subscription management** (upgrade/downgrade/cancel) | ☐ | Self-service |
| 48 | **Usage metering** (track emails per billing period) | ☐ | Against plan limits |
| 49 | **Invoice generation** | ☐ | PDF invoices |
| 50 | **Coupon / promo code system** | ☐ | Discount codes |

### PHASE 9 — Multi-Tenant & Teams (P3)

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 51 | **Team workspaces** (multiple users per account) | ☐ | Shared campaigns + recipients |
| 52 | **Team roles** (owner, editor, viewer) | ☐ | Granular permissions |
| 53 | **Audit log** (all actions tracked) | ☐ | Who did what, when |
| 54 | **Sending domains** (verify DKIM/SPF from UI) | ☐ | Users add own domain |

### PHASE 10 — Polish & Scale

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 55 | **Email verification service** (ZeroBounce/NeverBounce) | ☐ | Clean lists before sending |
| 56 | **Multi-language support** | ☐ | i18n for UI |
| 57 | **Dark mode** (already partial via shadcn) | ☐ | Complete coverage |
| 58 | **Mobile responsive** (campaign pages) | ☐ | Polish mobile views |
| 59 | **PWA** (progressive web app) | ☐ | Installable, offline-capable |
| 60 | **Performance optimization** (caching, pagination everywhere) | ☐ | Scale to 100k+ emails |

---

## 8. Database — Quick Reference

### Core Tables

**users** — id, name, email, password, is_admin, email_verified_at, two_factor_secret,
two_factor_recovery_codes, two_factor_confirmed_at, remember_token, timestamps

**templates** — id, name, slug (unique), subject, body_html (text), category,
thumbnail (nullable), variables (json, nullable), is_active (boolean), timestamps

**campaigns** — id, user_id (FK), template_id (FK), name, subject,
status (enum: draft|queued|sending|sent|partial|failed, default: draft),
total_recipients (int, default: 0), sent_count (int, default: 0),
failed_count (int, default: 0), scheduled_at (nullable), sent_at (nullable), timestamps

**campaign_recipients** — id, campaign_id (FK), email, name (nullable),
status (enum: pending|sent|failed|bounced, default: pending),
sent_at (nullable), error_message (text, nullable), metadata (json, nullable), timestamps

### SMTP Tables

**platform_smtp_settings** — id, host, port, username, password (encrypted),
encryption (default: tls), from_email, from_name, daily_limit (default: 1000),
is_active (boolean, default: true), timestamps

**smtp_settings** — id, user_id (FK, unique), is_custom (boolean, default: false),
host (nullable), port (nullable), username (nullable), password (nullable, encrypted),
encryption (nullable), from_email (nullable), from_name (nullable),
is_active (boolean, default: true), timestamps

### New Tables (Installed)

**subscription_plans** — id, name, code (unique), stripe_price_id (nullable),
price_cents, currency, email_limit, features (json), sort_order, is_active, timestamps

**subscriptions** (Cashier) — id, user_id, type, stripe_id (unique), stripe_status,
stripe_price, quantity, trial_ends_at, ends_at, timestamps

**subscription_items** (Cashier) — id, subscription_id, stripe_id, stripe_product,
stripe_price, quantity

**roles** (Spatie) — id, name, guard_name, timestamps

**permissions** (Spatie) — id, name, guard_name, timestamps

**model_has_roles** (Spatie pivot) — role_id, model_type, model_id

**model_has_permissions** (Spatie pivot) — permission_id, model_type, model_id

**personal_access_tokens** (Sanctum) — id, tokenable_id, tokenable_type, name,
token (unique, 64 chars), abilities, last_used_at, expires_at, timestamps

**email_tracking** — id, campaign_id (FK), recipient_id (FK), type (enum: open|click|bounce|complaint),
ip_address, user_agent, url (for clicks), occurred_at, timestamps

**suppression_list** — id, email, reason (enum: bounce|complaint|manual),
campaign_id (nullable), created_at

**audit_logs** — id, user_id (FK), action, entity_type, entity_id,
old_values (json), new_values (json), ip_address, user_agent, created_at

---

## 9. Vitrine Site Structure

A separate public-facing site (can be same Laravel app at `/` or a subdomain).

```
mailforge.com
├── /                    Landing page (hero, features, CTA)
├── /features            Detailed features page
├── /pricing             Pricing comparison table (live from DB)
├── /docs                Documentation / knowledge base
│   ├── /getting-started
│   ├── /campaigns
│   ├── /templates
│   ├── /api
│   └── /faq
├── /blog                Marketing blog
│   ├── /archive
│   └── /{slug}
├── /changelog           Public release notes
├── /status              System status page
├── /contact             Contact form
├── /privacy             Privacy policy
├── /terms               Terms of service
└── /login               → app subdomain (app.mailforge.com)
```

**No auth required** for vitrine pages. SEO-optimized. Static or server-rendered.

---

## 10. Build Order — Phased Implementation

### NOW — Fix existing code and add current features
```
→ Deliverability & Compliance (open/click tracking, unsubscribe, bounce)
→ Admin business management (users, plans, quotas)
```

### NEXT — Launch-ready
```
→ Vitrine site (landing, pricing, docs, blog)
→ Onboarding flow
→ Full admin dashboard
→ Template CRUD in admin
```

### AFTER — Growth
```
→ Analytics (charts, per-recipient stats, export)
→ A/B testing
→ Email builder (drag-and-drop)
→ Scheduling + timezone
```

### AFTER — Scale
```
→ API + webhooks
→ SendGrid/AWS SES/Mailgun integrations
→ Stripe billing + subscriptions
```

### LAST — Enterprise
```
→ Multi-user teams + roles
→ Audit log
→ Sending domain management
→ Email validation service
→ PWA
→ i18n
```

---

## 11. Design System

Brand: Clean, professional, trustworthy. Blue-based accent.

```
CSS variables (defined in resources/css/app.css):
--background:         #FFFFFF   page background
--foreground:         #0A0A0A   primary text
--card:               #FFFFFF   card background
--card-foreground:    #0A0A0A   card text
--primary:            #18181B   primary buttons/nav (zinc-900)
--primary-foreground: #FAFAFA   primary button text
--accent:             #F4F4F5   hover states (zinc-100)
--muted:              #71717A   secondary text (zinc-500)
--destructive:        #EF4444   errors, delete actions (red-500)
--border:             #E4E4E7   borders (zinc-200)
--ring:               #18181B   focus rings
--radius:             0.5rem
```

**Rules:**
- Use shadcn/ui components — never build custom UI primitives
- Use the `cn()` utility for conditional class merging
- No inline styles — Tailwind classes only
- Use design system CSS variables, not raw hex values
- Responsive by default — test on mobile before reporting done
- All roles share the same color system

---

## 12. Agent Instructions — Rules That Never Change

1. Read PROJECT_CONTEXT.md before writing any code
2. No new packages without strong justification
3. Every DB write goes through Eloquent models — never raw queries
4. Controller authorization is always enforced (403 for unauthorized access)
5. Never hardcode business data — all lookup values come from database
6. Use design tokens, not raw hex values
7. Build must be clean (`npm run build` passes) before reporting done
8. Run `vendor/bin/pint --format agent` on all PHP changes
9. Run `npx tsc --noEmit` and fix TS errors before reporting done
10. Every new feature needs a Pest test
11. When in doubt, stop and report — do not improvise
12. Update PROGRESS.md at the end of every task (move completed items, update status)

---

## 13. Known Technical Debt

### Bypassed Mail Configuration
The app uses raw Symfony `SmtpTransport` directly, bypassing Laravel's `config/mail.php`.
This means `MAIL_*` env vars are unused for campaign sends. Fix by refactoring `EmailService`
to use Laravel's Mail facade properly when not using custom transport.

### Per-User SMTP UI Missing
The `SmtpSetting` model and relationship exist, along with the backend `EmailService` logic
to check for custom SMTP. But there is NO UI page for users to configure their custom SMTP.
The settings page needs a new section.

### Scheduled Sending Column Exists, Unused
The `campaigns.scheduled_at` column exists in the migration and model, but no scheduling
logic is implemented anywhere. Campaigns always send immediately when queued.

### Missing Bounce/Open/Click Tracking
No tracking infrastructure exists. The `campaign_recipients` table has a `bounced` status
in the enum but no code handles it. No tracking pixel or link wrapper exists.

### Test Coverage for SMTP
No tests exist for `EmailService`, `AdminSmtpController`, or SMTP-related functionality.

### `$loop->success` TypeScript Error
`smtp-settings.tsx:33` — `success` does not exist on `InertiaFormProps`. Likely a stale
Inertia type or the `useForm` options callback needs the `onSuccess` pattern instead.

---

## 14. Progress Tracking

All progress tracking has moved to **PROGRESS.md** in the project root.
Update PROGRESS.md at the end of every task.
