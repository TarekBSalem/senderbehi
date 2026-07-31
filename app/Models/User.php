<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $blocked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $subscription_plan_id
 * @property int $monthly_email_count
 * @property Carbon|null $monthly_email_reset_at
 * @property SubscriptionPlan|null $subscriptionPlan
 */
#[Fillable(['name', 'email', 'password', 'blocked_at', 'subscription_plan_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'blocked_at' => 'datetime',
            'monthly_email_count' => 'integer',
            'monthly_email_reset_at' => 'datetime',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function smtpSetting(): HasOne
    {
        return $this->hasOne(SmtpSetting::class);
    }

    public function canSend(int $count = 1): bool
    {
        if ($this->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        $this->resetMonthlyCountIfNeeded();

        $plan = $this->subscriptionPlan;

        if ($plan === null) {
            return false;
        }

        return $this->monthly_email_count + $count <= $plan->email_limit;
    }

    public function quotaRemaining(): int
    {
        $this->resetMonthlyCountIfNeeded();

        $plan = $this->subscriptionPlan;

        if ($plan === null) {
            return 0;
        }

        return max(0, $plan->email_limit - $this->monthly_email_count);
    }

    public function incrementMonthlyEmailCount(int $count = 1): void
    {
        $this->resetMonthlyCountIfNeeded();

        $this->increment('monthly_email_count', $count);
    }

    public function resetMonthlyCountIfNeeded(): void
    {
        if ($this->monthly_email_reset_at === null || $this->monthly_email_reset_at->isBefore(now()->startOfMonth())) {
            $this->monthly_email_count = 0;
            $this->monthly_email_reset_at = now();
            $this->save();
        }
    }
}
