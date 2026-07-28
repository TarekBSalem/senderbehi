---
name: analytics
description: Use when building campaign analytics, charts, reporting, open/click tracking dashboards, recipient-level stats, A/B testing, geo/device breakdowns, or CSV exports.
---

# Analytics & Reporting

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/Campaign.php` | sent_count, failed_count, total_recipients |
| `app/Models/CampaignRecipient.php` | status per recipient |
| `app/Services/CampaignService.php` | updateCampaignStats |

## Future Tables

When implementing tracking, create this migration:

**email_tracking** — id, campaign_id (FK), recipient_id (FK),
type (enum: open|click|bounce|complaint), ip_address, user_agent,
url (for clicks), occurred_at, timestamps

## Analytics Data Sources

| Metric | Source |
|--------|--------|
| Total campaigns | `campaigns` table count |
| Emails sent | SUM of `sent_count` on campaigns |
| Emails failed | SUM of `failed_count` on campaigns |
| Open rate | tracking pixel hits / sent count |
| Click rate | link clicks / sent count |
| Bounce rate | bounced / total sent |
| Sent/failed per campaign | `campaign.sent_count`, `campaign.failed_count` |
| Per-recipient detail | `campaign_recipients.status` |
| Daily email volume | Tracking by date |

## UI Components (shadcn)

Use these existing shadcn components for analytics:
- `Card` — stat cards (total sends, open rate, etc.)
- `Table` — recipient-level data
- `Badge` — status indicators
- `Select` — time range filters

For charts, use `recharts` (most popular React chart library) or another library.
Charts needed:
- Line chart: opens/clicks over time
- Bar chart: daily send volume
- Pie chart: device breakdown

## Implementation Order

1. **Tracking pixel + click tracking** (prerequisite — in deliverability skill)
2. **Campaign analytics page** — open rate, click rate, bounces, timeline chart
3. **Recipient-level stats** — table with per-email open/click/bounce status
4. **Campaign list enhancements** — show open/click rates on campaign cards
5. **Export CSV** — campaign data export
6. **Dashboard analytics** — platform-wide trends
7. **Geo/device/browser** — from user agent and IP data
8. **A/B testing** — variant comparison with statistical significance
