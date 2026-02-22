<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'parent_id', 'template',
        'status', 'featured_image', 'meta_title', 'meta_description',
        'meta_keywords', 'sort_order', 'author_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            if (!$page->slug) {
                $slug = Str::slug($page->title);
                $original = $slug;
                $counter = 1;
                while (Page::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $page->slug = $slug;
            }
        });
    }

    public function parent() { return $this->belongsTo(Page::class, 'parent_id'); }
    public function children() { return $this->hasMany(Page::class, 'parent_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublished($query) { return $query->where('status', 'published'); }
    public function scopeRoots($query) { return $query->whereNull('parent_id'); }
}
