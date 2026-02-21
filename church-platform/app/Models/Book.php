<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title', 'slug', 'author', 'description', 'cover_image', 'pdf_file',
        'category', 'isbn', 'pages', 'published_year', 'download_count',
        'view_count', 'is_featured', 'is_active', 'meta_title', 'meta_description', 'uploaded_by'
    ];

    protected $casts = ['is_featured' => 'boolean', 'is_active' => 'boolean'];

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function incrementDownload() { $this->increment('download_count'); }
    public function incrementView() { $this->increment('view_count'); }
}
