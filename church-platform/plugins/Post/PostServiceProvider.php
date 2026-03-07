<?php

namespace Plugins\Post;

use App\Core\PluginServiceProvider;

class PostServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Post';

    protected function registerBindings(): void
    {
        // Register Post service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Post boot logic here
    }
}
