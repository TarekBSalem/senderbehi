# Production Email Deliverability Checklist

## Prerequisites

- [ ] A domain you control (e.g., `yourcompany.com`)
- [ ] DNS management access (Cloudflare, Namecheap, Route53, etc.)
- [ ] Google Workspace account (or other SMTP provider)
- [ ] Dedicated sending email address (not a free Gmail account)

---

## 1. DNS Authentication (Required)

### SPF Record
Authorizes your sending IP/server to send emails on behalf of your domain.

```
Type:   TXT
Name:   @ (or yourdomain.com)
Value:  v=spf1 include:_spf.google.com ~all
```

If using a separate sending subdomain:
```
Type:   TXT
Name:   mail
Value:  v=spf1 include:_spf.google.com ~all
```

### DKIM Signing
Digitally signs your emails so receivers can verify they haven't been tampered with.

1. Go to [Google Admin Console](https://admin.google.com) → Apps → Google Workspace → Gmail → Authenticate email
2. Select your domain, generate a DKIM key (2048-bit)
3. Add the TXT record to your DNS:
   ```
   Type:   TXT
   Name:   google._domainkey
   Value:  (the DKIM record from Google)
   ```
4. Click "Start Authentication" in Google Admin

### DMARC Policy
Tells receiving servers what to do when authentication fails.

```
Type:   TXT
Name:   _dmarc
Value:  v=DMARC1; p=quarantine; pct=100; rua=mailto:dmarc-reports@yourdomain.com
```

**Rollout order:** Start with `p=none` to monitor, then `p=quarantine`, finally `p=reject`.

### Verify
```bash
dig TXT yourdomain.com     # SPF
dig TXT google._domainkey.yourdomain.com  # DKIM
dig TXT _dmarc.yourdomain.com  # DMARC
```

---

## 2. IP & Domain Warmup

New sending accounts have zero reputation. Following this schedule avoids spam flags.

| Day | Volume | Notes |
|-----|--------|-------|
| 1-3 | 50/day | Send only to most engaged users |
| 4-7 | 200/day | Gradually expand to active users |
| 8-14 | 500/day | Include less engaged users |
| 15-21 | 1,000/day | Continue ramping |
| 22-30 | 2,000+/day | Now safe at full volume |

**Rules:**
- Never exceed daily volume — sudden spikes trigger spam filters
- Use a dedicated IP if sending >10k/day
- Spread sends across the day (don't blast 500 at 9:00 AM)

---

## 3. Sending Infrastructure Settings

In the app's admin SMTP configuration:

| Setting | Gmail Workspace | Standard SMTP |
|---------|----------------|---------------|
| Host | `smtp.gmail.com` | Your provider's host |
| Port | `587` (TLS) | `587` / `465` |
| Encryption | `tls` | `tls` / `ssl` |
| Daily limit | Start at 100, increase weekly | Per provider |

**Gmail daily limits:**
- Free Gmail: 500/day (DO NOT use this for production)
- Google Workspace Starter: 2,000/day
- Google Workspace Business: 2,000/day (can request more)

---

## 4. Bounce & Complaint Handling

Bounce handling is built into the app. Choose the method that fits your setup.

### Option A: IMAP Polling (for your own SMTP server)

The app polls a dedicated bounce inbox and auto-processes DSN (Delivery Status Notification) emails.

**Setup:**

1. Create a dedicated bounce inbox (e.g., `bounces@yourdomain.com`)
2. Set the envelope return path so bounces go there:
   ```
   MAIL_BOUNCE_ADDRESS=bounces@yourdomain.com
   ```
3. Configure IMAP connection in `.env`:
   ```
   BOUNCE_IMAP_HOST=imap.yourdomain.com
   BOUNCE_IMAP_PORT=993
   BOUNCE_IMAP_ENCRYPTION=ssl
   BOUNCE_IMAP_USERNAME=bounces@yourdomain.com
   BOUNCE_IMAP_PASSWORD=your-password
   BOUNCE_IMAP_MAILBOX=INBOX
   ```
4. Ensure the queue worker is running. The scheduler polls every 5 minutes:
   ```bash
   php artisan schedule:run  # or add cron: * * * * * php artisan schedule:run
   ```

**Requires:** PHP IMAP extension (`php-imap`)

### Option B: Webhook Endpoint (for SMTP relays)

If your SMTP provider can POST bounce notifications, use the built-in webhook:

```
POST https://yourdomain.com/webhooks/bounce
Content-Type: application/json

{
    "email": "bounced@example.com",
    "event": "bounce|soft_bounce|complaint",
    "campaign_id": 123
}
```

The endpoint is public (no auth) and accepts:
- `bounce` — hard bounce (permanent failure)
- `soft_bounce` — temporary failure
- `complaint` — spam report (FBL)

### How It Works

All bounce types are handled by `BounceService`:
- Adds the email to `suppression_list` (auto-skipped on future sends)
- Marks matching `campaign_recipients` as `bounced` with a descriptive error
- Logs the event in `email_tracking` for campaign analytics

### What's Already Configured

- [x] `List-Unsubscribe` + `List-Unsubscribe-Post` headers on every campaign email
- [x] `{{unsubscribe_url}}` variable in all 8 email templates
- [x] One-click unsubscribe route: `GET /unsubscribe/{campaign}/{recipient}`
- [x] Auto-suppression of hard bounces (permanent failures blocked from future sends)
- [x] Suppression check runs before every send in `CampaignService`
- [x] Physical address in email footer via `{{physical_address}}`
- [x] Return-Path set to `MAIL_BOUNCE_ADDRESS` on all campaign emails

---

## 5. Content Best Practices

- Use a real person or brand name as the `From` name (not "noreply")
- Include a plain-text version of every email (not just HTML)
- Keep text-to-HTML ratio balanced (at least 40% text)
- Avoid spam trigger words: `free`, `guaranteed`, `act now`, `limited time`, `click here`
- Avoid excessive images — 1-2 max per email
- Use a real reply-to address that accepts replies
- Include physical mailing address (legal requirement in many countries)

---

## 6. Sending Strategy

- Only send to confirmed opt-in recipients (no purchased lists)
- Segment by engagement: send to recent openers first
- Re-engage stale subscribers with a separate campaign before including them
- Remove unengaged subscribers after 90 days
- Spread campaign sends over 30-60 minutes (use `dispatch()->delay()`)
- Monitor open rates — a sudden drop signals a deliverability problem

---

## 7. Monitoring

| Metric | Target | Warning |
|--------|--------|---------|
| Open rate | > 20% | < 10% |
| Click rate | > 2% | < 0.5% |
| Bounce rate | < 2% | > 5% |
| Spam complaint rate | < 0.1% | > 0.1% (Google suspends at 0.3%) |

**Tools:**
- Google Postmaster Tools — monitor domain reputation
- MXToolbox — check DNS records
- mail-tester.com — score a test email

---

## 8. Alternative: Use a Transactional Email Service

Instead of managing SMTP yourself, consider these services. They handle reputation, warming, and deliverability for you:

| Service | Price | Notes |
|---------|-------|-------|
| Postmark | Pay per email | Best deliverability, transactional focus |
| SendGrid | Free up to 100/day | Good for campaigns + transactional |
| Amazon SES | Very cheap | Requires warming; good at scale |
| Mailgun | Free up to 5k/month | Good developer experience |

The app's `EmailService` would need a new transport adapter, but it isolates the SMTP logic in one place.
