<?php

namespace Plugins\Greeting\Resolvers;

use App\Models\User;
use Plugins\Greeting\GreetingContentResolver;

class EventsResolver implements GreetingContentResolver
{
    public function resolve(User $user): ?array
    {
        if (! class_exists(\App\Models\Event::class)) {
            return null;
        }

        $events = \App\Models\Event::where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(3)
            ->get(['id', 'title', 'start_date', 'location']);

        if ($events->isEmpty()) {
            return null;
        }

        return [
            'type'    => 'events',
            'content' => $events->toArray(),
        ];
    }

    public function priority(): int { return 20; }
}
