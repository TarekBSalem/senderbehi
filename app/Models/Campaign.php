<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'subject',
        'body_html',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
        'use_custom_smtp',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_email',
        'smtp_from_name',
        'sends_per_minute',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'use_custom_smtp' => 'boolean',
    ];

    protected $hidden = [
        'smtp_password',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Auto-decrypt SMTP password when accessed
     */
    public function getSmtpPasswordAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * Auto-encrypt SMTP password when set
     */
    public function setSmtpPasswordAttribute(?string $value): void
    {
        $this->attributes['smtp_password'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Resolve the effective sending rate (emails per minute) for this campaign:
     * campaign override → user SMTP → platform SMTP → default.
     */
    public function resolveSendsPerMinute(): int
    {
        if ($this->sends_per_minute) {
            return $this->sends_per_minute;
        }

        $userSmtp = $this->user?->smtpSetting;

        if ($userSmtp?->is_custom && $userSmtp->sends_per_minute) {
            return $userSmtp->sends_per_minute;
        }

        $platformSmtp = PlatformSmtpSetting::where('is_active', true)->first();

        return $platformSmtp?->sends_per_minute ?? 60;
    }

    /**
     * Render the email body, preferring the campaign's own HTML when set.
     */
    public function renderBody(array $variables = []): string
    {
        $html = $this->body_html ?? $this->template?->body_html ?? '';

        foreach ($variables as $key => $value) {
            $html = str_replace("{{{$key}}}", $value, $html);
        }

        return $html;
    }
}
