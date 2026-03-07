<?php

namespace Plugins\Comment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $table = 'social_comments';

    protected $fillable = ['user_id', 'post_id', 'parent_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(\Plugins\Post\Models\Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('user')->latest();
    }

    public function reactions()
    {
        return $this->morphMany(\Plugins\Reaction\Models\Reaction::class, 'reactable');
    }
}
