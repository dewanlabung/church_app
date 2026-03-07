<?php

namespace Plugins\Chat;

use App\Core\PluginServiceProvider;

class ChatServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Chat';

    protected function registerBindings(): void
    {
        // Register Chat service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Chat boot logic here
    }
}
