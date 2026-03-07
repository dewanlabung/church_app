<?php

namespace Plugins\Notification;

use App\Core\PluginServiceProvider;

class NotificationServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Notification';

    protected function registerBindings(): void
    {
        // Register Notification service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Notification boot logic here
    }
}
