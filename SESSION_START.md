# How to Start a Session with Claude

Paste this at the beginning of every new conversation:

---

**Before doing anything:**
1. Read `PROJECT_CONTEXT.md` (architecture, rules, build order)
2. Read `PROGRESS.md` (current build status, what's next)
3. Read the relevant source files for the task

**Rules that never change:**
- Read PROJECT_CONTEXT.md and PROGRESS.md before writing any code
- No new packages without strong justification
- Never hardcode business data — all lookup values come from the database
- Use design tokens, not raw hex values
- Run `vendor/bin/pint --format agent` on all PHP changes
- Run `npx tsc --noEmit` and fix TS errors before reporting done
- Build must be clean (`npm run build` passes) before reporting done
- Update `PROGRESS.md` at the end of every task
- When in doubt, stop and report — do not improvise

---

## What to say when starting a task

### Option A — Continue where we left off
> "Read PROJECT_CONTEXT.md and PROGRESS.md. Tell me the current status and what's next. No code yet."

### Option B — Start a specific feature
> "Read PROJECT_CONTEXT.md, PROGRESS.md, and all existing files related to [feature].
> Trace the full data flow (model → controller → service → job → frontend).
> Then propose a plan. No code until I confirm."

### Option C — Debug something
> "Something is broken: [describe symptom].
> Read the full relevant code — controller, service, model, component — and trace the data flow.
> Tell me the root cause before touching anything."

### Option D — Review a feature built previously
> "Read [Controller], [Service], [Model], and [Page].tsx fully.
> Trace the complete data flow and tell me how it works and what might be wrong."

---

## What NOT to say
- ❌ "Fix this" without context — Claude will guess at the wrong root cause
- ❌ "What's next?" without telling it to read PROGRESS.md first
- ❌ Asking Claude to remember something across sessions — always update PROGRESS.md

---

## Current State (update this when things change)

| Area | Status |
|------|--------|
| Foundation (auth, DB, stack) | ✅ Complete |
| Template System | ✅ Complete |
| Campaign Management | ✅ Complete |
| Recipient Management | ✅ Complete |
| Sending Pipeline | 🟡 In Progress (per-user SMTP UI missing) |
| Deliverability & Compliance | ⬜ Not Started (P0 — next priority) |
| Admin Business Management | ⬜ Not Started (P1) |
| Vitrine Site | ⬜ Not Started (P1) |
| Analytics & Reporting | ⬜ Not Started (P2) |
| Email Builder | ⬜ Not Started (P2) |
| Automation & Scheduling | ⬜ Not Started (P2-P3) |
| API & Integrations | ⬜ Not Started (P3) |
| Billing | ⬜ Not Started (P3) |
| Multi-Tenant & Teams | ⬜ Not Started (P3) |

### Last Completed (Jul 28)
- Project Context + Progress Tracker created
- Sidebar navigation (Campaigns + Templates)
- Chunked queue dispatching (chunk 100)
- Optimized campaign stats (no N+1)
- SMTP settings null-safe fix
- MarketingEmail subject property fix
- Production deliverability checklist

### Next Up (Priority Order)
1. Open/click tracking + unsubscribe link
2. Bounce handling + suppression list
3. User management (admin panel)
4. Subscription plans + usage quotas
5. Vitrine site (landing, pricing, docs)
