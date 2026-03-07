<?php

namespace App\Providers;

use App\Core\MenuBuilder;
use App\Core\PluginManager;
use App\Core\SettingsManager;
use App\Core\ThemeManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Core singletons — available everywhere as app('settings'), app('themes'), etc.
        $this->app->singleton(SettingsManager::class);
        $this->app->alias(SettingsManager::class, 'settings');

        $this->app->singleton(ThemeManager::class);
        $this->app->alias(ThemeManager::class, 'themes');

        $this->app->singleton(MenuBuilder::class);
        $this->app->alias(MenuBuilder::class, 'menus');

        $this->app->singleton(PluginManager::class);
        $this->app->alias(PluginManager::class, 'plugins');
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Boot all enabled plugins
        $this->app->make(PluginManager::class)->boot();
    }
}
