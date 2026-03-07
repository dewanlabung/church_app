<?php

namespace Plugins\Greeting;

use App\Models\User;

interface GreetingContentResolver
{
    /**
     * Return a greeting block for the given user.
     * Return null to skip this resolver.
     *
     * Block shape:
     * [
     *   'type'    => 'verse'|'events'|'prayer'|'message'|'custom',
     *   'content' => mixed,
     * ]
     */
    public function resolve(User $user): ?array;

    /**
     * Priority — lower runs first (0 = highest priority).
     */
    public function priority(): int;
}
