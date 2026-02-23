<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterTemplate extends Model
{
    protected $fillable = [
        'name', 'subject', 'body', 'type', 'is_active',
        'sent_at', 'recipients_count', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
