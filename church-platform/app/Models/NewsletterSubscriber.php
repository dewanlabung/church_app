<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'name', 'is_active', 'token'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($subscriber) {
            $subscriber->token = Str::random(32);
        });
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
