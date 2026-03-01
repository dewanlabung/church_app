<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin', 'role_id', 'church_id',
        'avatar', 'bio', 'phone', 'provider', 'provider_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function role() { return $this->belongsTo(Role::class); }
    public function posts() { return $this->hasMany(Post::class, 'author_id'); }
    public function prayerRequests() { return $this->hasMany(PrayerRequest::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function bibleStudies() { return $this->hasMany(BibleStudy::class, 'author_id'); }
    public function sermons() { return $this->hasMany(Sermon::class, 'author_id'); }
    public function church() { return $this->belongsTo(Church::class); }
    public function managedChurch() { return $this->hasOne(Church::class, 'admin_user_id'); }

    public function hasPermission($permission)
    {
        if ($this->is_admin) return true;
        if (!$this->role) return false;
        $perms = $this->role->permissions ?? [];
        return in_array($permission, $perms);
    }
}
