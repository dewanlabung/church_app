<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 'category',
        'tags', 'status', 'published_at', 'view_count', 'is_featured',
        'allow_comments', 'meta_title', 'meta_description', 'meta_keywords',
        'og_image', 'author_id'
    ];

    protected $casts = [
        'published_at' => 'datetime', 'is_featured' => 'boolean', 'allow_comments' => 'boolean'
    ];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublished($query) { return $query->where('status', 'published')->where('published_at', '<=', now()); }
    public function scopeFeatured($query) { return $query->published()->where('is_featured', true); }

    public function incrementView() { $this->increment('view_count'); }
}
