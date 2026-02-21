<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'is_read', 'admin_reply', 'replied_at'];

    protected $casts = ['is_read' => 'boolean', 'replied_at' => 'datetime'];

    public function scopeUnread($query) { return $query->where('is_read', false); }
}
