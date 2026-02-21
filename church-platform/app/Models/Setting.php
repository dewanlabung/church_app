<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'theme_config' => 'array',
        'widget_config' => 'array',
    ];

    public static function get($key, $default = null)
    {
        $settings = static::first();
        return $settings ? ($settings->$key ?? $default) : $default;
    }
}
