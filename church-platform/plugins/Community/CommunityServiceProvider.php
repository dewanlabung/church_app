<?php

namespace Plugins\Community;

use App\Core\PluginServiceProvider;

class CommunityServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Community';

    protected function registerBindings(): void
    {
        // Register Community service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Community boot logic here
    }
}
