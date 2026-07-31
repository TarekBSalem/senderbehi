<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSmtpSetting extends Model
{
    protected $table = 'platform_smtp_settings';

    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_name',
        'daily_limit',
        'physical_address',
        'sends_per_minute',
        'daily_sent_count',
        'daily_sent_date',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_sent_date' => 'date',
    ];

    /**
     * Auto-decrypt password when accessed
     */
    public function getPasswordAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * Auto-encrypt password when set
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function dailyLimitReached(): bool
    {
        $this->resetDailyCountIfNewDay();

        return $this->daily_sent_count >= $this->daily_limit;
    }

    public function incrementDailySentCount(): void
    {
        $this->resetDailyCountIfNewDay();

        $this->increment('daily_sent_count');
    }

    private function resetDailyCountIfNewDay(): void
    {
        $today = now()->toDateString();

        if ($this->daily_sent_date === null) {
            $this->update(['daily_sent_date' => $today]);
            $this->daily_sent_date = $today;

            return;
        }

        $storedDate = $this->daily_sent_date instanceof Carbon
            ? $this->daily_sent_date->toDateString()
            : $this->daily_sent_date;

        if ($storedDate !== $today) {
            $this->update([
                'daily_sent_count' => 0,
                'daily_sent_date' => $today,
            ]);
            $this->daily_sent_count = 0;
            $this->daily_sent_date = $today;
        }
    }
}
