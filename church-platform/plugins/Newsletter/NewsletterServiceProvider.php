<?php

namespace Plugins\Newsletter;

use App\Core\PluginServiceProvider;

class NewsletterServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Newsletter';

    protected function registerBindings(): void
    {
        // Register Newsletter service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Newsletter boot logic here
    }
}
