<?php

namespace App\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PluginManager
{
    protected string $pluginsPath;
    protected string $registryPath;
    protected array $registry = [];
    protected array $manifests = [];
    protected array $booted = [];

    public function __construct()
    {
        $this->pluginsPath  = base_path('plugins');
        $this->registryPath = storage_path('app/plugins.json');
    }

    /**
     * Boot all enabled plugins. Called once from AppServiceProvider.
     */
    public function boot(): void
    {
        $this->loadRegistry();
        $this->discoverManifests();

        foreach ($this->registry as $slug => $state) {
            if (! ($state['enabled'] ?? false)) {
                continue;
            }
            $this->bootPlugin($slug);
        }
    }

    /**
     * Boot a single plugin by slug.
     */
    protected function bootPlugin(string $slug): void
    {
        if (isset($this->booted[$slug])) {
            return;
        }

        $manifest = $this->manifests[$slug] ?? null;
        if (! $manifest) {
            return;
        }

        // Boot required dependencies first
        foreach ($manifest['requires'] ?? [] as $dep) {
            $this->bootPlugin($dep);
        }

        $providerClass = "Plugins\\{$slug}\\{$slug}ServiceProvider";
        if (class_exists($providerClass)) {
            app()->register($providerClass);
            $this->booted[$slug] = true;
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /** Return all discovered plugins with their status. */
    public function all(): array
    {
        $this->discoverManifests();
        $result = [];

        foreach ($this->manifests as $slug => $manifest) {
            $result[$slug] = array_merge($manifest, [
                'enabled' => $this->registry[$slug]['enabled'] ?? false,
                'booted'  => isset($this->booted[$slug]),
            ]);
        }

        return $result;
    }

    /** Return only enabled plugins. */
    public function enabled(): array
    {
        return array_filter($this->all(), fn ($p) => $p['enabled']);
    }

    /** Enable a plugin by slug. */
    public function enable(string $slug): bool
    {
        if (! isset($this->manifests[$slug])) {
            return false;
        }

        $this->registry[$slug] = array_merge(
            $this->registry[$slug] ?? [],
            ['enabled' => true, 'version' => $this->manifests[$slug]['version'] ?? '1.0.0']
        );

        $this->saveRegistry();
        Cache::forget('plugin_manager_registry');
        return true;
    }

    /** Disable a plugin by slug. Core plugins (can_disable: false) cannot be disabled. */
    public function disable(string $slug): bool
    {
        $manifest = $this->manifests[$slug] ?? null;
        if (! $manifest || ! ($manifest['can_disable'] ?? true)) {
            return false;
        }

        $this->registry[$slug]['enabled'] = false;
        $this->saveRegistry();
        Cache::forget('plugin_manager_registry');
        return true;
    }

    /** Remove a plugin folder (must be disabled first). */
    public function remove(string $slug): bool
    {
        $manifest = $this->manifests[$slug] ?? null;
        if (! $manifest) {
            return false;
        }
        if ($manifest['can_remove'] ?? true === false) {
            return false;
        }
        if ($this->registry[$slug]['enabled'] ?? false) {
            return false; // must disable first
        }

        $path = "{$this->pluginsPath}/{$slug}";
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }

        unset($this->registry[$slug], $this->manifests[$slug]);
        $this->saveRegistry();
        Cache::forget('plugin_manager_registry');
        return true;
    }

    /** Check if a plugin is enabled. */
    public function isEnabled(string $slug): bool
    {
        return $this->registry[$slug]['enabled'] ?? false;
    }

    /** Get manifest for a single plugin. */
    public function manifest(string $slug): ?array
    {
        return $this->manifests[$slug] ?? null;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function loadRegistry(): void
    {
        if (! File::exists($this->registryPath)) {
            $this->registry = [];
            return;
        }

        $contents = File::get($this->registryPath);
        $this->registry = json_decode($contents, true) ?? [];
    }

    protected function saveRegistry(): void
    {
        File::put($this->registryPath, json_encode($this->registry, JSON_PRETTY_PRINT));
    }

    protected function discoverManifests(): void
    {
        if (! empty($this->manifests) || ! File::isDirectory($this->pluginsPath)) {
            return;
        }

        foreach (File::directories($this->pluginsPath) as $dir) {
            $manifestPath = "{$dir}/plugin.json";
            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);
            if (! $manifest || empty($manifest['slug'])) {
                continue;
            }

            $this->manifests[$manifest['slug']] = $manifest;
        }
    }
}
