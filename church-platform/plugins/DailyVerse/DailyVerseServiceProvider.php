<?php

namespace Plugins\DailyVerse;

use App\Core\PluginServiceProvider;

class DailyVerseServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'DailyVerse';

    protected function registerBindings(): void
    {
        // Register DailyVerse service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional DailyVerse boot logic here
    }
}
