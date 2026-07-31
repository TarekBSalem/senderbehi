<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    protected $fillable = [
        'user_id',
        'is_custom',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_name',
        'is_active',
        'sends_per_minute',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Auto-decrypt password when accessed
     */
    public function getPasswordAttribute(?string $value): ?string
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
     * Auto-encrypt password when set
     */
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value
            ? Crypt::encryptString($value)
            : null;
    }
}
