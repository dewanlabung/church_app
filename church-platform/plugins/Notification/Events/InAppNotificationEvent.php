<?php

namespace Plugins\Notification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an in-app notification is persisted to notifications_log.
 * Real-time delivery is handled by SocketBroadcaster (Workerman), not Laravel's
 * broadcast() helper — so this event does NOT implement ShouldBroadcast.
 */
class InAppNotificationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int   $userId,
        public readonly array $notification
    ) {}
}
