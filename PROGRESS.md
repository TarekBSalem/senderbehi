# MailForge — Progress Tracker
# Last Updated: 2026-08-01 (14th update)# Update this file at the end of every task.

---

## Overall Status

| Phase | Progress | Status |
|-------|----------|--------|
| Foundation (auth, DB, stack) | 100% | ✅ Complete |
| Template System | 100% | ✅ Complete |
| Campaign Management | 100% | ✅ Complete |
| Recipient Management | 100% | ✅ Complete |
| Sending Pipeline | 100% | ✅ Complete |
| Roles & Permissions (Spatie) | 100% | ✅ Complete |
| Billing (Cashier + Plans) | 60% | 🟡 In Progress (Stripe keys needed) |
| Admin Management | 100% | ✅ Complete |
| Deliverability & Compliance | 95% | 🟡 In Progress (open/click ✅, unsubscribe ✅, bounce/suppression ✅, webhook + IMAP polling ✅, rate limiting ✅) |
| Vitrine Site | 100% | ✅ Complete |
| Analytics & Reporting | 80% | 🟡 In Progress (campaign analytics page ✅, recipient-level stats ✅, geo/device tracking ✅, CSV export ✅, A/B testing ⬜) |
| Email Builder | 0% | ⬜ Not Started |
| Automation & Scheduling | 0% | ⬜ Not Started |
| API & Integrations | 0% | ⬜ Not Started |
| Billing | 0% | ⬜ Not Started |
| Multi-Tenant & Teams | 0% | ⬜ Not Started |

---

## Current Sprint — Fixes & Improvements

### In Progress
| Task | Started | Notes |
|------|---------|-------|
| — | — | — |

### Completed This Sprint (Aug 1)
| Task | Notes |
|------|-------|
| Post-send SMTP update + resend-to-all | `campaigns/show.tsx` now shows the Sending Settings card for every campaign status except in-flight `sending`, so SMTP / send speed can be edited after creation (backend `CampaignController::update` already had no status guard). New "Resend to All" header button (visible for sent/partial/failed campaigns) posts `resend_all: true` to `recipients.resend`, which re-queues **every** recipient regardless of status (sent, failed, even pending), with rate-limit staggering and quota check. `RecipientController::resend` gained the `resend_all` branch while keeping existing selected/failed semantics. 3 new tests (sent-campaign SMTP update, resend_all re-queues all statuses, resend_all still honors explicit recipient_ids). Full suite: 314/314 passing, pint + tsc + build clean. |
| Settings section in sidebar | New "Settings" group in `app-sidebar.tsx` (visible to every logged-in user) grouping all per-account settings: Profile, Security, SMTP, Appearance. Previously only reachable via the user-menu dropdown. tsc + build clean. |
| SMTP provider dropdown | New shared `SmtpProviderSelect` component + `lib/smtp-providers.ts` presets (Custom, Gmail/Workspace, o2switch, Mailgun, SendGrid, Postmark, Amazon SES). Selecting a provider auto-fills host/port/encryption (+ username for SendGrid). Auto-detects the saved provider from the existing host. Wired into both `settings/smtp.tsx` and `admin/smtp-settings.tsx`. tsc + build clean. |
| Robust CSV recipient import | Importer now auto-detects the delimiter (comma / semicolon / tab), strips UTF-8 BOM from the first header cell, and trims whitespace around cells. Fixes failures when files are saved by Excel in French locale (`;`) or pasted tab-separated. 4 new tests. Full suite: 311/311 passing. |
| Admin suppression list management | `AdminSuppressionController` (index w/ search + reason filter + stats, store/manual add, destroy, clear) + `admin/suppressions.tsx` page (stat cards, add form, table, ConfirmDialog on every delete/clear) + sidebar nav (ShieldBan icon). All destructive actions confirmed via `ConfirmDialog`; mail-logs page also gained a per-row delete confirmation modal. 11 new tests. Full suite: 307/307 passing. |
| Admin mail send logs | New `mail_logs` table (persistent, records cumulative history across resends) + `MailLog` model/factory. `CampaignService` logs `sent`/`failed`/`bounced` (with `smtp_source`, `attempt`, `error_message`); `SendCampaignEmail::failed()` logs `job_failed`. `AdminMailLogController` (index w/ search + event filter + stats, destroy, clear) + `admin/mail-logs.tsx` page (stat cards, filters, table, pagination, Clear All via ConfirmDialog) + sidebar nav. 10 new tests. Full suite: 296/296 passing. Regenerated wayfinder with `--with-form` (auth pages rely on `.form()`), tsc + build clean. |

### Completed This Sprint (Jul 31)
| Task | Notes |
|------|-------|
| Per-campaign sending options | Migration adds `body_html`, `use_custom_smtp`, `smtp_host/port/username/password/encryption/from_email/from_name`, `sends_per_minute` to `campaigns`; `template_id` now nullable (MySQL `change()` + SQLite tmp-table rebuild path) |
| Campaign create wizard (4 steps) | `campaigns/create.tsx` — Details → Content (template OR HTML import w/ live preview) → Sending (schedule, speed, custom SMTP) → Review; supports edit mode via `campaigns.edit` |
| Sending Settings card | `campaigns/show.tsx` — edit schedule / send speed / custom SMTP on the campaign page any time before send (blank password preserves existing) |
| Campaign custom SMTP priority | `EmailService::sendWithCampaignSmtp()` — campaign SMTP → user SMTP → platform SMTP; sets mailable `from()` when `smtp_from_email` present |
| Send speed resolution | `Campaign::resolveSendsPerMinute()` (campaign override → user SMTP → platform → 60) used by `CampaignController::send()` + `SendScheduledCampaigns` command; steady-rate staggered delays |
| Campaign body rendering | `Campaign::renderBody()` prefers `body_html` over template body; `CampaignService` renders via campaign, passes campaign to `EmailService` |
| Admin template 2-step create | `admin/templates/create.tsx` — start from existing template (copy) or import HTML, then details; `AdminTemplateController::create` now passes template list |
| Laravel 13 mail API fix | `Mail::transport()` no longer exists — replaced with `Mail::mailer()->setSymfonyTransport()` (EmailService); `Envelope::returnPath` removed — set via `using` callback in `MarketingEmail` (latent bugs, now tested) |
| SQLite migration fix | Removed composite-index collision in tmp-table rebuild (`campaigns_tmp_user_id_status_index`) so `migrate:fresh` works on SQLite test DB |
| Test suite fixes | `CampaignTest` validation expectation updated (template OR HTML); `CategoryFactory` unique-slug suffix (was exhausting 6-name pool, blocking analytics tests); quota reset test month-boundary fix; SMTP priority tests rewritten to work with array mailer + real transport |
| TypeScript cleanup | Fixed `srcdoc` → `srcDoc`, `useForm.transform()` usage; fixed pre-existing `admin/smtp-settings.tsx` `success` form-prop type error — `tsc --noEmit` clean |
| SMTP test-connection dialogs | Replaced `alert()` with shared `ResultDialog` component (`resources/js/components/result-dialog.tsx`) on `settings/smtp.tsx` + `admin/smtp-settings.tsx`; admin test now sends host/port/encryption via fetch with proper success/error feedback, spinner while testing |
| Recipient import result dialog | `RecipientController` store/importCsv now flash `importResult` (added/duplicates/invalid) alongside toast; `campaigns/show.tsx` listens for the `flash` event and shows a ResultDialog breakdown; CSV invalid-row count now tracked (`$invalid`) |
| Tests | `CampaignSendingOptionsTest` (15 tests) — create/import-HTML, SMTP priority, password encryption, update semantics, send-rate resolution, scheduled command. Full suite: 274/274 passing. |
| SMTP priority rework | New priority: campaign SMTP → user SMTP → **default mailer (MAIL_* env)**. Platform SMTP removed from send path (kept for physical_address + sends_per_minute fallback + admin UI); `EmailService::sendWithPlatformSmtp()` replaced with `sendWithDefaultMailer()`; removed platform daily-limit enforcement test. Fixes "Platform SMTP not configured" crash when table empty. Full suite: 273/273 passing. |
| Admin unlimited sending | `User::canSend()` bypasses quota for `Super Admin` + `Admin` roles (returns true regardless of plan/limit); 3 new tests. `DatabaseSeeder` now assigns the **Business** plan to the seeded admin so they always have a plan on record; new `DatabaseSeederTest` (2 tests). Root cause of local sends failing: seeded admin had no `subscription_plan_id` → `canSend()` returned false → send blocked with "Monthly email limit exceeded". Full suite: 278/278 passing. |
| Resend + attempt/success tracking | New `ConfirmDialog` component replaces native `confirm()` on Send Now / scheduled Send. `attempt_count` + `success_count` columns on `campaign_recipients` (incremented in `CampaignService::sendToRecipient`). New `recipients.resend` route (`RecipientController::resend`) re-queues selected (or all failed) recipients any number of times with rate-limit staggering; campaign status returns to `sending`, then `sent` when no failed recipients remain (`updateCampaignStats` now checks current failed recipients). UI: checkboxes per recipient + select-all, "Resend Selected (N)" and "Resend Failed (N)" buttons, "Attempts (successes/tries)" column. 8 new tests. Full suite: 286/286 passing. |
| Resend Campaign + per-recipient resend | For already-sent campaigns: header "Resend Campaign" button (status `sent`) re-queues all sent/failed recipients; each sent/failed recipient row gets its own "Resend" button. Both reuse `recipients.resend` with explicit `recipient_ids`, so resends can be repeated endlessly with attempt/success counts accumulating. Frontend-only (show.tsx); tsc + build clean. |

### Completed This Sprint (Jul 29)
| Task | Notes |
|------|-------|
| Campaign analytics page | `CampaignAnalyticsController` (stats, timeline, device/browser breakdown, recipient data), `campaigns/analytics.tsx` page with stat cards, LineChart (opens/clicks over time), BarChart (device & browser breakdown), recipient table with tracking badges, CSV export, 13 tests |
| Analytics link on campaign show | "Analytics" button shown for non-draft campaigns, links to `/campaigns/{id}/analytics` |
| Vitrine site — Ocean Blue theme | Updated `app.css` CSS variables from zinc to ocean blue (light + dark), matching shadcn/ui conventions |
| 11 reusable vitrine components | `hero-section`, `page-header`, `section-header`, `feature-card`, `features-section`, `pricing-card`, `pricing-section`, `cta-section`, `stats-section`, `testimonials-section`, `gradient-bg` — all using shadcn/ui primitives |
| Landing page (welcome.tsx) | Refactored with HeroSection, StatsSection, FeaturesSection, TestimonialsSection, CTASection using Lucide icons |
| Pricing page | Refactored with PageHeader, PricingSection (live from DB plans), CTASection |
| Features page | Refactored with PageHeader, FeaturesSection (9 features with Lucide icons), CTASection |
| Docs, Blog, Changelog pages | Refactored with PageHeader, consistent styling, hover effects, blue accents |
| Contact, Privacy, Terms pages | Refactored with PageHeader, clean layouts |
| Public layout | Added Docs + Blog nav items to header, consistent blue theme |

### Completed This Sprint (Jul 28)
| Task | Notes |
|------|-------|
| Send rate limiting | Staggered dispatch with per-campaign delay from `sends_per_minute`, daily limit enforcement on `PlatformSmtpSetting`, `sending` status lifecycle fix, 12 tests |
| Subscription plans CRUD | `AdminSubscriptionPlanController` (index, create, store, edit, update, destroy), model, factory, routes, Inertia pages (index, create, edit), sidebar nav, 17 tests |
| Admin user management | `AdminUserController` (index, show, create, store, updateRole, block, unblock), middleware (`CheckUserIsActive`), migration (`blocked_at`), routes, Inertia pages (index, show, create), sidebar nav for Super Admin, 14 tests, TypeScript types |
| Admin user creation | Create form page (`admin/users/create.tsx`), validation, role assignment, 4 tests |
| Open tracking (tracking pixel) | `TrackingController@trackOpen` returns 1x1 GIF, logs open to `email_tracking`. Routes public (no auth). |
| Click tracking (link wrapping) | `CampaignService` rewrites all `<a href>` → tracking redirect. `TrackingController@trackClick` logs click + redirects. Skips unsubscribe links. |
| Unsubscribe link | `{{unsubscribe_url}}` template variable, one-click route with suppression + List-Unsubscribe + List-Unsubscribe-Post headers in MarketingEmail. |
| Bounce handling infrastructure | `bounced` status now set on unsubscribe + suppression skip. `email_tracking` table supports bounce/complaint types. |
| Suppression list | `suppression_list` table + model. `CampaignService` checks before sending, skips suppressed emails. |
| Physical address in footer | `physical_address` column on `platform_smtp_settings`. `{{physical_address}}` variable in all templates. |
| EmailService SmtpTransport fix | Updated from deprecated `new SmtpTransport(host, port, ...)` to `EsmtpTransport` for Symfony Mailer v8. |
| 8 templates updated | All template footers use `{{unsubscribe_url}}` and `{{physical_address}}` instead of hardcoded `#`. |
| 19 deliverability tests | 105/105 tests passing. |
| Bounce webhook endpoint | `POST /webhooks/bounce` accepts `{email, event, campaign_id}`. Public (no auth). Processes hard/soft bounce + complaint. |
| IMAP bounce polling command | `php artisan bounces:fetch` — polls bounce inbox, parses DSN, processes bounces. Scheduled every 5min. |
| Return-Path on outgoing emails | `MarketingEmail` sets `returnPath` from `config('mail.bounce_address')`. CampaignService passes it through. |
| BounceService | Processes all bounce types (hard, soft, complaint). Adds to suppression list, marks recipients bounced, logs tracking events. |
| 12 bounce tests | 117/117 tests passing. |
| Spatie laravel-permission installed + configured | Roles: Super Admin, Admin, User, Viewer. `is_admin` column migrated to roles via migration. `EnsureUserIsAdmin` middleware now uses `hasRole('Super Admin')`. |
| Laravel Cashier (Stripe) installed | `Billable` trait added to User, subscriptions/subscription_items tables created, customer columns added to users. |
| Subscription plans seeded | Free (500/mo), Starter (5k/mo), Pro (25k/mo), Business (100k/mo) in `subscription_plans` table, ready for Stripe price ID mapping. |
| Sanctum personal_access_tokens table | Created for future API access. |
| UserFactory `admin()` state | Factory can now create Super Admin users for tests with `User::factory()->admin()->create()`. |
| CampaignService tests rewritten | All tests updated for new `updateCampaignStats($campaign, $success)` signature. 86/86 passing. |
| Sidebar navigation (Campaigns + Templates) | `app-sidebar.tsx` updated with Send + Mail icons |
| Chunked queue dispatching | `CampaignController::send()` now uses `chunk(100)` |
| Optimized campaign stats | Removed N+1 queries, updated per-send |
| SMTP null-safe fix | `firstOrCreate([])` → `first()` + TS null handling |
| MarketingEmail subject fix | Removed redeclared parent `$subject` property |
| Production deliverability checklist | `docs/production-email-deliverability.md` |
| Project Context | `PROJECT_CONTEXT.md` created |
| Progress Tracker | `PROGRESS.md` created |
| Session start guide | `SESSION_START.md` created |
| 5 domain skills created | deliverability, admin-management, vitrine-site, sending-pipeline, analytics |

---

## Feature Status — Complete Breakdown

### ✅ COMPLETE — Foundation

| Feature | Files | Notes |
|---------|-------|-------|
| Laravel + React + Inertia setup | — | Starter kit |
| Auth (register, login, logout) | Fortify + auth pages | Included in starter kit |
| Password reset | Fortify | Email-based |
| Email verification | Fortify | Resend verification |
| 2FA (TOTP) | Fortify | QR setup, recovery codes, OTP challenge |
| Passkeys (WebAuthn) | Fortify | Register, verify, delete |
| Profile management | Settings pages | Name/email update, delete account |
| Appearance toggle | `appearance-tabs.tsx` | Light/Dark/System |
| Admin middleware | `EnsureUserIsAdmin.php` | `is_admin` guard |
| Dashboard stats | `Admin/DashboardController.php` | Total campaigns, emails sent, recipients |

### ✅ COMPLETE — Template System

| Feature | Files | Notes |
|---------|-------|-------|
| Template model + migration | `Template.php`, `2026_07_13_000001` | slug, body_html, variables, category, is_active |
| TemplateController | `TemplateController.php` | index (filter/search) + show (by slug) |
| Template gallery | `pages/templates/index.tsx` | Grid cards, category filter, search |
| Template preview | `pages/templates/show.tsx` | iframe srcdoc preview |
| 8 pre-seeded templates | `TemplateSeeder.php` | 6 categories |
| Variable interpolation | `Template::render()` | `{{name}}`, `{{email}}`, `{{campaign_name}}` |
| Tests | `TemplateTest.php` | 6 tests: filtering, search, inactive, 404 |

### ✅ COMPLETE — Campaign Management

| Feature | Files | Notes |
|---------|-------|-------|
| Campaign model + migration | `Campaign.php`, `2026_07_13_000002` | status enum, sent/failed counts |
| CampaignController CRUD | `CampaignController.php` | index, create, store, show, edit, update, destroy |
| Campaign list page | `pages/campaigns/index.tsx` | Paginated table |
| Campaign create page | `pages/campaigns/create.tsx` | 4-step wizard (details, content w/ template OR HTML, sending options, review), edit mode |
| Campaign show page | `pages/campaigns/show.tsx` | Stats, recipients, actions |
| Campaign status lifecycle | CampaignController | draft→queued→sent/partial/failed |
| Authorization | CampaignController | 403 if not owner |
| Tests | `CampaignTest.php` | 21 tests |

### ✅ COMPLETE — Recipient Management

| Feature | Files | Notes |
|---------|-------|-------|
| CampaignRecipient model + migration | `CampaignRecipient.php`, `2026_07_13_000003` | status enum, metadata |
| RecipientController | `RecipientController.php` | index, store, importCsv, destroy |
| Manual add | RecipientController | JSON array of {email, name} |
| CSV import | RecipientController | email column, optional name, max 2MB |
| Deduplication | RecipientController | By email within campaign |
| Remove pending | RecipientController | Cannot remove sent |
| Tests | `RecipientTest.php` | 17 tests |

### 🟡 IN PROGRESS — Sending Pipeline

| Feature | Status | Notes |
|---------|--------|-------|
| SendCampaignEmail job | ✅ Complete | 3 retries, 60s backoff, ShouldQueue |
| MarketingEmail mailable | ✅ Complete | Subject + raw HTML via htmlString |
| Queue dispatching | ✅ Complete | Per-recipient job dispatch |
| Chunked processing (100) | ✅ Complete | `chunk(100)` in send() |
| Campaign stats tracking | ✅ Complete | sent_count/failed_count increment, auto-complete |
| Per-user custom SMTP | ✅ Complete | SmtpController (edit/update/test), settings/smtp.tsx page (host, port, username, password, encryption, from email/name, sends_per_minute), settings sidebar nav, encrypted password, test connection endpoint, 9 tests |
| Per-campaign sending options | ✅ Complete | Custom SMTP, schedule, send speed per campaign — set at creation or via Sending Settings card before send (campaign SMTP → user SMTP → platform SMTP priority) |
| Scheduled sending | ✅ Complete | `scheduled_at` on campaigns, schedule dialog + Sending Settings card, `SendScheduledCampaigns` command dispatches due campaigns |
| Send rate limiting | ✅ Complete | Per-campaign `sends_per_minute` with steady-rate staggered queue delays, daily limit enforcement |

### ⬜ NOT STARTED — Admin Business Management

| Feature | Priority | Notes |
|---------|----------|-------|
| User management (list, search, view, suspend) | ✅ Complete | AdminUserController, pages, tests |
| Subscription plans CRUD | ✅ Complete | AdminSubscriptionPlanController, pages, tests, 4 seeded plans |
| Usage quotas + plan assignment | ✅ Complete | Monthly limit enforcement in send pipeline, admin plan selector, 12 tests |
| Platform-wide analytics | ✅ Complete | admin/analytics page with stat cards (users, campaigns, emails, MRR), BarChart daily volume, PieChart status, plan distribution table, 10 tests |
| Admin Template CRUD | ✅ Complete | AdminTemplateController (index, create, store, edit, update, destroy), routes, Inertia pages (index with search/filter, create, edit), sidebar nav, auto-variable extraction from body_html, 13 tests |
| User subscription assignment | P1 | Admin sets user plan |
| Usage quotas (email limits per plan) | ✅ Complete | Track + enforce in CampaignController & SendCampaignEmail job |
| User subscription assignment (admin) | ✅ Complete | Assign plan in admin user show page |
| Platform-wide analytics | ✅ Complete | BarChart daily volume, PieChart status breakdown, plan distribution, MRR, 10 tests |
| Template CRUD (admin) | ✅ Complete | AdminTemplateController, pages (index, create, edit), routes, auto-variable extraction, 13 tests |
| Mail send logs (admin) | ✅ Complete | `mail_logs` table, AdminMailLogController (index/destroy/clear), `admin/mail-logs.tsx` page, sent/failed/bounced/job_failed events, 10 tests |
| Suppression list (admin) | ✅ Complete | `AdminSuppressionController` (index/store/destroy/clear), `admin/suppressions.tsx` page (add, search, reason filter, stats, confirmed deletes), sidebar nav, 11 tests |
| Category management | ✅ Complete | Categories table + model, AdminCategoryController CRUD, pages (index, create, edit), category_id FK on templates, auto-migration of data, 11 tests |
| Announcements / notifications to users | P2 | In-app |

### 🟡 IN PROGRESS — Deliverability & Compliance

| Feature | Status | Notes |
|---------|--------|-------|
| Open tracking (pixel) | ✅ Complete | 1x1 GIF via `TrackingController@trackOpen`, logs to `email_tracking` |
| Click tracking (link wrapping) | ✅ Complete | HTML link rewriting in `CampaignService`, log + redirect via `TrackingController@trackClick` |
| Unsubscribe link | ✅ Complete | `{{unsubscribe_url}}` in templates, one-click route, suppresses + marks bounced |
| Bounce handling | ✅ Complete | BounceService + webhook endpoint + IMAP polling command (`bounces:fetch`) |
| Suppression list | ✅ Complete | `suppression_list` table + model, checked before send in `CampaignService` |
| Physical address in footer | ✅ Complete | `physical_address` on `platform_smtp_settings`, `{{physical_address}}` template variable |
| Send rate limiting | ✅ Complete | Staggered dispatch via `->delay()`, daily limit enforcement, configurable `sends_per_minute` per SMTP |

### ✅ COMPLETE — Vitrine Site

| Feature | Notes |
|---------|-------|
| Ocean Blue theme | Full CSS variable set (light + dark mode), oklch-based ocean blue primary |
| 11 reusable vitrine components | `hero-section`, `page-header`, `section-header`, `feature-card`, `features-section`, `pricing-card`, `pricing-section`, `cta-section`, `stats-section`, `testimonials-section`, `gradient-bg` |
| Public layout | Updated with Docs + Blog nav items, blue theme styling |
| Landing page (hero, features, CTA) | SEO-optimized, responsive, testimonials + stats sections |
| Pricing page | Live from DB plans, `PricingCard` + `PricingSection` |
| Documentation / knowledge base | Category-grouped cards, MD rendering |
| Blog | Index + show pages with MD rendering |
| Contact / sales page | Form with validation, admin notification stub |
| Changelog / status page | Timeline layout with version badges |
| Privacy policy + terms of service | Legal pages with `PageHeader` |

### 🟡 IN PROGRESS — Analytics & Reporting

| Feature | Status | Notes |
|---------|--------|-------|
| Campaign analytics page (charts) | ✅ Complete | Stat cards, LineChart (opens/clicks over time), BarChart (device/browser) |
| Recipient-level stats | ✅ Complete | Per-email table with open/click/bounce tracking, timestamps, device |
| Geo/device/browser stats | ✅ Complete | User-agent parsing → Mobile/Desktop/Tablet, Chrome/Firefox/Safari/Edge |
| Export reports (CSV) | ✅ Complete | Download recipient analytics as CSV |
| A/B testing | ⬜ Not Started | Subject line + content variants |

### ⬜ NOT STARTED — Email Builder

| Feature | Priority | Notes |
|---------|----------|-------|
| Drag-and-drop editor | P2 | Block-based (rows, text, image, button) |
| Save custom templates | P2 | User-owned templates |
| User template categories | P3 | Organization |
| Template versioning | P3 | Track changes, rollback |

### 🟡 IN PROGRESS — Automation & Scheduling

| Feature | Priority | Notes |
|---------|----------|-------|
| Campaign scheduling | ✅ Complete | Specific date/time per campaign, `SendScheduledCampaigns` command, schedule + speed + SMTP editable pre-send |
| Timezone-aware sending | P3 | Recipient timezone |
| Drip campaigns | P3 | Automated sequences |
| Triggered emails | P3 | Welcome, birthday, etc. |
| Re-engagement automation | P3 | Inactive subscriber recovery |

### ⬜ NOT STARTED — API & Integrations

| Feature | Priority | Notes |
|---------|----------|-------|
| REST API (Sanctum) | P3 | Campaigns, recipients, templates |
| API keys management | P3 | Generate/revoke |
| Webhooks | P3 | Sent, opened, clicked, bounced |
| SendGrid integration | P3 | Alternative provider |
| AWS SES integration | P3 | Alternative provider |
| Mailgun integration | P3 | Alternative provider |
| Zapier integration | P3 | No-code automation |

### ⬜ NOT STARTED — Billing

| Feature | Priority | Notes |
|---------|----------|-------|
| Stripe integration | P3 | Payment processing |
| Subscription management | P3 | Upgrade/downgrade/cancel |
| Usage metering | P3 | Per-billing-period tracking |
| Invoice generation | P3 | PDF invoices |
| Coupon / promo codes | P3 | Discount system |

### ⬜ NOT STARTED — Multi-Tenant & Teams

| Feature | Priority | Notes |
|---------|----------|-------|
| Team workspaces | P3 | Multiple users per account |
| Team roles | P3 | Owner, editor, viewer |
| Audit log | P3 | All actions tracked |
| Sending domains (DKIM/SPF UI) | P3 | Self-service domain setup |

### ⬜ NOT STARTED — Polish & Scale

| Feature | Priority | Notes |
|---------|----------|-------|
| Email validation (ZeroBounce) | P3 | Clean lists before sending |
| Multi-language (i18n) | P3 | UI translations |
| Mobile responsive polish | P2 | Campaign pages mobile optimization |
| PWA | P3 | Installable, offline-capable |
| Performance optimization | P3 | Caching, pagination everywhere |

---

## Environment

| Item | Value |
|------|-------|
| Project | MailForge |
| App URL | `https://mailforge.test` (Herd) |
| DB | SQLite (dev) → MySQL (prod) |
| Queue | Database (dev & prod) |
| Queue worker | `php artisan queue:work` |
| Node | Latest LTS |
| PHP | 8.5 |

---

## Quick Commands

```bash
# Start queue worker
php artisan queue:work

# Run tests
php artisan test --compact

# Run specific test
php artisan test --compact --filter=CampaignTest

# Format PHP
vendor/bin/pint --format agent

# Build frontend
npm run build

# TypeScript check
npx tsc --noEmit

# Fresh database
php artisan migrate:fresh --seed

# Clear queue
php artisan queue:clear
```
