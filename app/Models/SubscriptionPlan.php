<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'stripe_price_id',
        'price_cents',
        'currency',
        'email_limit',
        'features',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'email_limit' => 'integer',
        'features' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    public function formattedPrice(): string
    {
        return '$'.number_format($this->price_cents / 100, 2);
    }

    public function formattedEmailLimit(): string
    {
        if ($this->email_limit >= 1000) {
            return number_format($this->email_limit / 1000, 0).',000';
        }

        return (string) $this->email_limit;
    }
}
