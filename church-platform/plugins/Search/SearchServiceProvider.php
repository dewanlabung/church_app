<?php

namespace Plugins\Search;

use App\Core\PluginServiceProvider;

class SearchServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Search';

    protected function registerBindings(): void
    {
        // Register Search service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Search boot logic here
    }
}
