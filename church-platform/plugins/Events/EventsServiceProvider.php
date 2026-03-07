<?php

namespace Plugins\Events;

use App\Core\PluginServiceProvider;

class EventsServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Events';

    protected function registerBindings(): void
    {
        // Register Events service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Events boot logic here
    }
}
