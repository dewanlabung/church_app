<?php

namespace App\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ThemeManager
{
    protected const CACHE_KEY = 'active_theme_css';
    protected const CACHE_TTL = 3600;

    protected SettingsManager $settings;

    public function __construct(SettingsManager $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Return the active theme slug.
     */
    public function activeSlug(): string
    {
        return $this->settings->get('active_theme', 'default');
    }

    /**
     * Return the active theme manifest (theme.json).
     */
    public function activeManifest(): array
    {
        $slug = $this->activeSlug();
        $path = base_path("themes/{$slug}/theme.json");

        if (! File::exists($path)) {
            $path = base_path('themes/default/theme.json');
        }

        return File::exists($path)
            ? (json_decode(File::get($path), true) ?? [])
            : [];
    }

    /**
     * Return all installed themes.
     */
    public function all(): array
    {
        $themes    = [];
        $themesDir = base_path('themes');

        if (! File::isDirectory($themesDir)) {
            return $themes;
        }

        foreach (File::directories($themesDir) as $dir) {
            $manifestPath = "{$dir}/theme.json";
            if (! File::exists($manifestPath)) {
                continue;
            }
            $manifest = json_decode(File::get($manifestPath), true) ?? [];
            $manifest['active']     = ($manifest['slug'] ?? basename($dir)) === $this->activeSlug();
            $manifest['screenshot'] = asset("themes/{$manifest['slug']}/screenshot.png");
            $themes[]               = $manifest;
        }

        return $themes;
    }

    /**
     * Activate a theme by slug.
     */
    public function activate(string $slug): bool
    {
        $path = base_path("themes/{$slug}/theme.json");
        if (! File::exists($path)) {
            return false;
        }

        $this->settings->set('active_theme', $slug);
        $this->flush();
        return true;
    }

    /**
     * Install a theme from uploaded zip.
     */
    public function install(string $zipPath): bool
    {
        $themesDir = base_path('themes');
        $zip       = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $zip->extractTo($themesDir);
        $zip->close();
        $this->flush();
        return true;
    }

    /**
     * Remove a theme (cannot remove active theme).
     */
    public function remove(string $slug): bool
    {
        if ($slug === $this->activeSlug() || $slug === 'default') {
            return false;
        }

        $path = base_path("themes/{$slug}");
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
            $this->flush();
            return true;
        }

        return false;
    }

    /**
     * Generate CSS custom properties string, merging theme defaults
     * with admin customizer overrides stored in settings.
     */
    public function css(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->buildCss();
        });
    }

    /**
     * Build the CSS string (not cached — call css() for cached version).
     */
    public function buildCss(): string
    {
        $manifest  = $this->activeManifest();
        $overrides = $this->settings->get('theme_overrides', []);

        // Merge theme defaults with admin overrides
        $colors = array_merge(
            $manifest['colors'] ?? [],
            is_array($overrides['colors'] ?? null) ? $overrides['colors'] : []
        );
        $fonts = array_merge(
            $manifest['fonts'] ?? [],
            is_array($overrides['fonts'] ?? null) ? $overrides['fonts'] : []
        );

        $vars = [];

        foreach ($colors as $name => $value) {
            $vars[] = "  --color-{$name}: {$value};";
        }
        foreach ($fonts as $name => $value) {
            $vars[] = "  --font-{$name}: '{$value}', sans-serif;";
        }

        // Custom CSS from settings
        $customCss = $this->settings->get('custom_css', '');

        // Load dark.css file if it exists
        $slug    = $this->activeSlug();
        $darkCss = '';
        $darkPath = base_path("themes/{$slug}/css/dark.css");
        if (File::exists($darkPath)) {
            $darkCss = File::get($darkPath);
        }

        $lines = implode("\n", $vars);

        return ":root {\n{$lines}\n}\n\n{$darkCss}\n\n{$customCss}";
    }

    /**
     * Flush the theme CSS cache.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
