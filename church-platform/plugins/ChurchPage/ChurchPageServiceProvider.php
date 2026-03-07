<?php

namespace Plugins\ChurchPage;

use App\Core\PluginServiceProvider;

class ChurchPageServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'ChurchPage';

    protected function registerBindings(): void
    {
        // Register ChurchPage service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional ChurchPage boot logic here
    }
}
