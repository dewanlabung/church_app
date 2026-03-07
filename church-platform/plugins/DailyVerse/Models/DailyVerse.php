<?php

namespace Plugins\DailyVerse\Models;

use Illuminate\Database\Eloquent\Model;

class DailyVerse extends Model
{
    protected $table = 'daily_verses';

    protected $fillable = ['date', 'reference', 'text', 'version', 'is_active'];

    protected $casts = [
        'date'      => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
