<?php

namespace Plugins\BibleReader;

use App\Core\PluginServiceProvider;

class BibleReaderServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'BibleReader';

    protected function registerBindings(): void
    {
        // Register BibleReader service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional BibleReader boot logic here
    }
}
