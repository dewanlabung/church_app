<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'request', 'status', 'is_public', 'is_anonymous', 'prayer_count', 'user_id'];

    protected $casts = ['is_public' => 'boolean', 'is_anonymous' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeApproved($query) { return $query->where('status', '!=', 'pending')->where('is_public', true); }
    public function scopePublic($query) { return $query->where('is_public', true); }
}
