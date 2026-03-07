<?php

namespace Plugins\Timeline;

use App\Core\PluginServiceProvider;

class TimelineServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Timeline';

    protected function registerBindings(): void
    {
        // Register Timeline service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Timeline boot logic here
    }
}
