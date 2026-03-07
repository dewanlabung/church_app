<?php

namespace Plugins\Question;

use App\Core\PluginServiceProvider;

class QuestionServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'Question';

    protected function registerBindings(): void
    {
        // Register Question service bindings here
    }

    protected function bootPlugin(): void
    {
        // Additional Question boot logic here
    }
}
