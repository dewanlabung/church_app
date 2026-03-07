<?php

namespace Plugins\Analytics;

use App\Core\PluginServiceProvider;

class AnalyticsServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Analytics';

    protected function registerBindings(): void
    {
        // Register Analytics service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Analytics boot logic here
    }
}
