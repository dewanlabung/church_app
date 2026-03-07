<?php

namespace Plugins\Social;

use App\Core\PluginServiceProvider;

class SocialServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Social';

    protected function registerBindings(): void
    {
        // Register Social service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Social boot logic here
    }
}
