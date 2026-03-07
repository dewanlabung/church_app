<?php

namespace Plugins\Library;

use App\Core\PluginServiceProvider;

class LibraryServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Library';

    protected function registerBindings(): void
    {
        // Register Library service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Library boot logic here
    }
}
