<?php

namespace Plugins\Prayer;

use App\Core\PluginServiceProvider;

class PrayerServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Prayer';

    protected function registerBindings(): void
    {
        // Register Prayer service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Prayer boot logic here
    }
}
