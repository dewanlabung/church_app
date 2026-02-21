<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sermon extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'content', 'speaker', 'image',
        'video_url', 'audio_url', 'pdf_notes', 'scripture_reference',
        'series', 'category', 'sermon_date', 'duration_minutes',
        'view_count', 'is_featured', 'is_active', 'meta_title', 'meta_description', 'author_id'
    ];

    protected $casts = [
        'sermon_date' => 'date', 'is_featured' => 'boolean', 'is_active' => 'boolean'
    ];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function incrementView() { $this->increment('view_count'); }
}
