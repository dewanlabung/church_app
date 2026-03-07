<?php

namespace Plugins\Gdpr;

use App\Core\PluginServiceProvider;

class GdprServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Gdpr';

    protected function registerBindings(): void
    {
        // Register Gdpr service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Gdpr boot logic here
    }
}
