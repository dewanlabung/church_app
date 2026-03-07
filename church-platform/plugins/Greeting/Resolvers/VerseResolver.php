<?php

namespace Plugins\Greeting\Resolvers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Plugins\Greeting\GreetingContentResolver;

class VerseResolver implements GreetingContentResolver
{
    public function resolve(User $user): ?array
    {
        // Try to get today's verse from DailyVerse plugin (if enabled)
        $verse = Cache::get('daily_verse_today');

        if (! $verse && class_exists(\Plugins\DailyVerse\Models\DailyVerse::class)) {
            $verse = \Plugins\DailyVerse\Models\DailyVerse::forDate(today())->first()
                ?? \Plugins\DailyVerse\Models\DailyVerse::active()->inRandomOrder()->first();
        }

        if (! $verse) {
            return null;
        }

        return [
            'type'    => 'verse',
            'content' => [
                'reference' => $verse->reference,
                'text'      => $verse->text,
                'version'   => $verse->version,
            ],
        ];
    }

    public function priority(): int { return 10; }
}
