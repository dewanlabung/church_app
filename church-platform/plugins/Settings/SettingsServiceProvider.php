<?php

namespace Plugins\Settings;

use App\Core\PluginServiceProvider;

class SettingsServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Settings';

    protected function registerBindings(): void
    {
        // Register Settings service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Settings boot logic here
    }
}
