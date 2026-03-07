<?php

namespace App\Core;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Base class for all plugin service providers.
 * Each plugin extends this and gets automatic route/migration loading.
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    /** Plugin slug — must match the folder name under plugins/ */
    protected string $slug = '';

    public function register(): void
    {
        $this->registerBindings();
    }

    public function boot(): void
    {
        if (empty($this->slug)) {
            return;
        }

        $base = base_path("plugins/{$this->slug}");

        // Auto-load routes
        $apiRoutes = "{$base}/routes/api.php";
        if (File::exists($apiRoutes)) {
            $this->app['router']->middleware('api')
                ->prefix('api/v1')
                ->group($apiRoutes);
        }

        $webRoutes = "{$base}/routes/web.php";
        if (File::exists($webRoutes)) {
            $this->app['router']->middleware('web')
                ->group($webRoutes);
        }

        // Auto-load migrations
        $migrationsPath = "{$base}/database/migrations";
        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Auto-load views
        $viewsPath = "{$base}/resources/views";
        if (File::isDirectory($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->slug);
        }

        // Auto-load translations
        $langPath = "{$base}/resources/lang";
        if (File::isDirectory($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->slug);
        }

        $this->bootPlugin();
    }

    /** Override in plugin to register bindings. */
    protected function registerBindings(): void {}

    /** Override in plugin for custom boot logic. */
    protected function bootPlugin(): void {}
}
