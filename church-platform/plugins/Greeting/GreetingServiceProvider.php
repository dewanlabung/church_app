<?php

namespace Plugins\Greeting;

use App\Core\PluginServiceProvider;

class GreetingServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Greeting';

    protected function registerBindings(): void
    {
        // Register Greeting service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Greeting boot logic here
    }
}
