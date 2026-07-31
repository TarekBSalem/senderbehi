<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangelogEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'content',
        'released_at',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'released_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('released_at', 'desc');
    }
}
