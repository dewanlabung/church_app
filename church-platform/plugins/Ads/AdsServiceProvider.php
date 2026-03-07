<?php

namespace Plugins\Ads;

use App\Core\PluginServiceProvider;

class AdsServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Ads';

    protected function registerBindings(): void
    {
        // Register Ads service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Ads boot logic here
    }
}
