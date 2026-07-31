<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doc extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'category',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeByCategory($query, ?string $category = null)
    {
        if ($category) {
            return $query->where('category', $category);
        }

        return $query;
    }
}
