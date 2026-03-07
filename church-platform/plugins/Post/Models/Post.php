<?php

namespace Plugins\Post\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'social_posts';

    /**
     * Post types:
     * text | photo | video | feeling | poll | link | document
     * | blessing | prayer_request | ask_question | bible_verse | event_share
     */
    protected $fillable = [
        'user_id',
        'type',           // post type
        'body',           // text content
        'privacy',        // public | community | friends | only_me
        'feeling',        // emoji/feeling for feeling-type posts
        'link_url',       // for link preview posts
        'link_meta',      // JSON: title, description, image for OG preview
        'poll_options',   // JSON array for poll type
        'poll_ends_at',   // datetime
        'bible_reference',// e.g. "John 3:16"
        'bible_text',     // verse text
        'is_anonymous',   // for prayer requests
        'is_pinned',
        'community_id',   // scoped to community (null = global feed)
        'church_id',      // scoped to church page (null = global)
        'parent_id',      // for shares (reshares)
        'views_count',
        'shares_count',
    ];

    protected $casts = [
        'link_meta'      => 'array',
        'poll_options'   => 'array',
        'poll_ends_at'   => 'datetime',
        'is_anonymous'   => 'boolean',
        'is_pinned'      => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->morphMany(\Plugins\Reaction\Models\Reaction::class, 'reactable');
    }

    public function comments()
    {
        return $this->hasMany(\Plugins\Comment\Models\Comment::class)
                    ->whereNull('parent_id')
                    ->latest();
    }

    public function saves()
    {
        return $this->hasMany(PostSave::class);
    }

    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function reshares()
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    public function scopeForFeed($query, int $userId)
    {
        // Returns posts visible to a user (their own + followed users' public posts)
        return $query->whereIn('user_id', function ($sub) use ($userId) {
            $sub->select('following_id')
                ->from('social_follows')
                ->where('follower_id', $userId);
        })->orWhere('user_id', $userId)
          ->whereIn('privacy', ['public', 'friends']);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getReactionCountsByType(): array
    {
        return $this->reactions()
                    ->selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('videos');
        $this->addMediaCollection('documents');
    }
}
