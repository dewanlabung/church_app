<?php

namespace Plugins\Sitemap;

use App\Core\PluginServiceProvider;

class SitemapServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Sitemap';

    protected function registerBindings(): void
    {
        // Register Sitemap service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Sitemap boot logic here
    }
}
