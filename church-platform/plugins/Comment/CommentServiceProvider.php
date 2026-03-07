<?php

namespace Plugins\Comment;

use App\Core\PluginServiceProvider;

class CommentServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Comment';

    protected function registerBindings(): void
    {
        // Register Comment service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Comment boot logic here
    }
}
