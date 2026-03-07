<?php

namespace Plugins\Auth;

use App\Core\PluginServiceProvider;

class AuthServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Auth';

    protected function registerBindings(): void
    {
        // Register Auth service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Auth boot logic here
    }
}
