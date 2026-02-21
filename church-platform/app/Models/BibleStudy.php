<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleStudy extends Model
{
    protected $table = 'bible_studies';

    protected $fillable = [
        'title', 'slug', 'description', 'content', 'image', 'category',
        'difficulty_level', 'scripture_reference', 'video_url', 'audio_url',
        'pdf_attachment', 'duration_minutes', 'view_count', 'is_featured',
        'is_active', 'sort_order', 'meta_title', 'meta_description', 'author_id'
    ];

    protected $casts = ['is_featured' => 'boolean', 'is_active' => 'boolean'];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }
    public function scopeByDifficulty($query, $level) { return $query->where('difficulty_level', $level); }

    public function incrementView() { $this->increment('view_count'); }
}
