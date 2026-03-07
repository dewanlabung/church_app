<?php

namespace Plugins\Reaction;

use App\Core\PluginServiceProvider;

class ReactionServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Reaction';

    protected function registerBindings(): void
    {
        // Register Reaction service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Reaction boot logic here
    }
}
