<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['name', 'location', 'items', 'is_active'];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByLocation($query, $location) { return $query->where('location', $location); }
}
