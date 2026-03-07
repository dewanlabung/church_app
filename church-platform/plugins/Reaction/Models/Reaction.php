<?php

namespace Plugins\Reaction\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $table = 'social_reactions';

    // Reaction types
    public const TYPES = ['like', 'bless', 'amen', 'pray', 'love'];

    protected $fillable = ['user_id', 'type', 'reactable_id', 'reactable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactable()
    {
        return $this->morphTo();
    }
}
