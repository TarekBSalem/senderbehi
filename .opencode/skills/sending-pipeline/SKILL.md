---
name: sending-pipeline
description: Use when working on email sending — SendCampaignEmail job, EmailService, SMTP transport, queue dispatching, MarketingEmail mailable, per-user custom SMTP, scheduling, or campaign stats.
---

# Sending Pipeline

## Architecture

```
CampaignController::send()
  └─ chunk(100) recipients from DB
       └─ dispatch(SendCampaignEmail) x 100 → database queue
            └─ CampaignService::sendToRecipient()
                 ├─ Template::render() — variable interpolation
                 ├─ new MarketingEmail(subject, htmlContent)
                 ├─ EmailService::send()
                 │    ├─ User has custom SMTP? → sendWithCustomSmtp()
                 │    └─ No custom SMTP?       → sendWithPlatformSmtp()
                 │         └─ Symfony SmtpTransport (raw, bypasses Laravel mail config)
                 ├─ recipient status → 'sent' or 'failed'
                 └─ updateCampaignStats() — increment counters, auto-complete
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Jobs/SendCampaignEmail.php` | Queue job: 3 tries, 60s backoff |
| `app/Services/CampaignService.php` | Orchestrates send + stats |
| `app/Services/EmailService.php` | SMTP transport (platform + custom) |
| `app/Mail/MarketingEmail.php` | Mailable with subject + htmlString |
| `app/Http/Controllers/CampaignController.php` | `send()` action — dispatch logic |
| `app/Models/PlatformSmtpSetting.php` | Admin SMTP config (encrypted) |
| `app/Models/SmtpSetting.php` | Per-user custom SMTP (encrypted) |

## SMTP Architecture

### Platform SMTP
- Admin configures via `/admin/smtp` page
- Stored encrypted in `platform_smtp_settings` table
- Used as default for all users
- Password auto-decrypted by Eloquent accessor

### Custom SMTP (per-user)
- `SmtpSetting` model exists with `is_custom` toggle
- Backend logic in `EmailService` checks `$user->smtpSetting?->is_custom`
- **NO UI exists yet** — needs to be added to user settings page
- Password auto-decrypted by accessor

### Transport Creation
```php
private function createTransport(
    string $host, int $port,
    string $username, string $password,
    string $encryption
): SmtpTransport {
    return new SmtpTransport($host, $port,
        $encryption === 'ssl' ? 'ssl' : 'tls',
        ['username' => $username, 'password' => $password]
    );
}
```

## Queue Configuration
- Driver: `database` (uses `jobs` table)
- Start worker: `php artisan queue:work`
- Job retries: 3 (`public int $tries = 3`)
- Backoff: 60 seconds between retries
- Chunk size: 100 recipients per DB query

## Current Limitations

### Known Issues
1. **Raw transport bypasses Laravel mail config** — `MAIL_*` env vars unused
2. **Per-user SMTP has no UI** — model + backend logic exist, but no settings page
3. **Scheduled sending unused** — `scheduled_at` column exists, no logic
4. **No rate limiting** — jobs fire as fast as the queue processes them
5. **Missing mail events** — no `MessageSent` or `MessageSending` listeners

### Implementations Needed
- Add `SendCampaignEmail` job delays using `$this->release()` for rate limiting
- Build user custom SMTP settings page (settings → email configuration)
- Implement scheduled_at check in the job to delay sending
- Consider switching to Laravel Mail facade with dynamic mailers for better event support
