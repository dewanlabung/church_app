<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title', 'slug', 'author', 'description', 'cover_image', 'pdf_file',
        'category', 'isbn', 'publisher', 'pages', 'published_year',
        'download_count', 'view_count', 'is_featured', 'is_active', 'is_free', 'is_published',
        'tags', 'meta_title', 'meta_description', 'uploaded_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function setPublishYearAttribute($value)
    {
        $this->attributes['published_year'] = $value;
    }

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function incrementDownload() { $this->increment('download_count'); }
    public function incrementView() { $this->increment('view_count'); }
}
