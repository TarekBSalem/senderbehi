---
name: deliverability
description: Use when implementing open/click tracking, unsubscribe links, bounce handling, suppression lists, SPF/DKIM/DMARC, send rate limiting, or email deliverability improvements.
---

# Deliverability & Compliance

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/CampaignService.php` | sendToRecipient, updateCampaignStats |
| `app/Services/EmailService.php` | sendWithPlatformSmtp, sendWithCustomSmtp |
| `app/Jobs/SendCampaignEmail.php` | Queue job per recipient |
| `app/Mail/MarketingEmail.php` | Mailable, envelope, content |
| `app/Models/CampaignRecipient.php` | status enum: pending, sent, failed, bounced |

## Campaign Recipient Status Values

Defined in migration `2026_07_13_000003`:
- `pending` — not yet sent
- `sent` — delivered successfully
- `failed` — hard failure after 3 retries
- `bounced` — SMTP bounce (already in enum, not yet used)

## Implementation Patterns

### Tracking Pixel
- Add a 1x1 transparent `<img>` tag at the bottom of the email body
- Image URL: `{{tracking_url}}/track/open?campaign_id=X&recipient_id=Y`
- Controller logs open event with IP, user agent, timestamp
- Store in `email_tracking` table (type: 'open')

### Click Tracking
- Rewrite all `<a href="...">` in the HTML body before sending
- Replace with: `{{tracking_url}}/track/click?url=ENCODED&campaign_id=X&recipient_id=Y`
- Controller logs click event and redirects to original URL

### Unsubscribe
- Add `{{unsubscribe_url}}` variable available in templates
- One-click unsubscribe link (required by Google/Yahoo 2024 mandate)
- Add `List-Unsubscribe` header to email envelope
- Store suppression in `suppression_list` table

### Bounce Handling
- Cannot catch SMTP bounces with raw transport approach
- Options: use a webhook endpoint, integrate SendGrid/AWS SES event notification, or use Laravel mail events
- Hard bounces go to `suppression_list`
- Soft bounces retry, then escalate

### Rate Limiting
- Add delay between sends in `SendCampaignEmail` job
- Use `$this->release(seconds)` to space out queue processing
- Respect SMTP provider daily limits

## Database Tables Involved

- `campaign_recipients` — `status` field updated on send/fail/bounce
- `campaigns` — `sent_count`, `failed_count` incremented per-recipient
- `email_tracking` — new table for opens, clicks, bounces, complaints
- `suppression_list` — new table for globally suppressed emails

## Testing

When writing tests for tracking:
- Use `Http::fake()` to intercept tracking pixel/click requests
- For bounce: test with invalid email addresses
- Verify `List-Unsubscribe` header is present on sent emails
