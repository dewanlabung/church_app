<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * 5 user types — stored as a column for quick querying.
     * Spatie roles/permissions layer on top for fine-grained access control.
     */
    public const TYPES = [
        'super_admin',   // full system control
        'church_admin',  // manages church page + community
        'counsellor',    // private messaging, pastoral care
        'musician',      // events, worship content
        'general_user',  // standard social features
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'avatar',
        'bio',
        'phone',
        'custom_fields',
        'provider',
        'provider_id',
        'email_verification_token',
        // Legacy fields (kept for backward compat)
        'is_admin',
        'role_id',
        'church_id',
        'church_name',
        'social_id',
        'spiritual_background',
        'profile_completed',
    ];

    protected $hidden = ['password', 'remember_token', 'email_verification_token'];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'password'           => 'hashed',
        'is_admin'           => 'boolean',
        'custom_fields'      => 'array',
        'profile_completed'  => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function socialPosts()
    {
        return $this->hasMany(\Plugins\Post\Models\Post::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'social_follows', 'following_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'social_follows', 'follower_id', 'following_id');
    }

    public function notificationPrefs()
    {
        return $this->hasOne(\Plugins\Notification\Models\NotificationPref::class);
    }

    // Legacy relations preserved
    public function legacyRole() { return $this->belongsTo(Role::class, 'role_id'); }
    public function church() { return $this->belongsTo(Church::class); }
    public function managedChurch() { return $this->hasOne(Church::class, 'admin_user_id'); }
    public function prayerRequests() { return $this->hasMany(PrayerRequest::class); }
    public function bibleStudies() { return $this->hasMany(BibleStudy::class, 'author_id'); }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin' || $this->hasRole('super_admin');
    }

    public function isChurchAdmin(): bool
    {
        return in_array($this->user_type, ['super_admin', 'church_admin']);
    }
}
