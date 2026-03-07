<?php

namespace Plugins\Hymns;

use App\Core\PluginServiceProvider;

class HymnsServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Hymns';

    protected function registerBindings(): void
    {
        // Register Hymns service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Hymns boot logic here
    }
}
