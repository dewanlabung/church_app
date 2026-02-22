<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'request', 'description',
        'status', 'is_public', 'is_anonymous', 'is_urgent', 'prayer_count', 'user_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_urgent' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeApproved($query) { return $query->where('status', 'approved')->where('is_public', true); }
    public function scopePublic($query) { return $query->where('is_public', true); }
}
