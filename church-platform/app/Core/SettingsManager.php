<?php

namespace App\Core;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsManager
{
    protected const CACHE_KEY = 'app_settings';
    protected const CACHE_TTL = 3600; // 1 hour

    protected ?array $data = null;

    /**
     * Get a setting value with optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->load()[$key] ?? $default;
    }

    /**
     * Set a setting value and flush cache.
     */
    public function set(string $key, mixed $value): void
    {
        $setting = Setting::firstOrNew([]);
        $setting->$key = $value;
        $setting->save();
        $this->flush();
    }

    /**
     * Set multiple values at once.
     */
    public function setMany(array $data): void
    {
        $setting = Setting::firstOrNew([]);
        foreach ($data as $key => $value) {
            $setting->$key = $value;
        }
        $setting->save();
        $this->flush();
    }

    /**
     * Return all settings as array.
     */
    public function all(): array
    {
        return $this->load();
    }

    /**
     * Clear the settings cache.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->data = null;
    }

    /**
     * Load settings from cache or DB.
     */
    protected function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $this->data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $setting = Setting::first();
            return $setting ? $setting->toArray() : [];
        });

        return $this->data;
    }
}
