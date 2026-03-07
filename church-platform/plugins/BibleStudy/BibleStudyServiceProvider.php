<?php

namespace Plugins\BibleStudy;

use App\Core\PluginServiceProvider;

class BibleStudyServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'BibleStudy';

    protected function registerBindings(): void
    {
        // Register BibleStudy service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional BibleStudy boot logic here
    }
}
