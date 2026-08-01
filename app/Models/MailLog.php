<?php

namespace App\Models;

use Database\Factories\MailLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailLog extends Model
{
    /** @use HasFactory<MailLogFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'user_id',
        'recipient_email',
        'event',
        'smtp_source',
        'error_message',
        'attempt',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
