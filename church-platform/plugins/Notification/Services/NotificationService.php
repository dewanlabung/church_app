<?php

namespace Plugins\Notification\Services;

use App\Core\SettingsManager;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Plugins\Notification\Jobs\SendEmailNotificationJob;
use Plugins\Notification\Jobs\SendPushNotificationJob;

class NotificationService
{
    public function __construct(protected SettingsManager $settings) {}

    /**
     * Notification type registry.
     * Add new notification types here without touching other code.
     */
    public const TYPES = [
        'post_reaction'        => ['label' => 'Post reaction',        'channels' => ['in_app', 'email', 'push']],
        'comment'              => ['label' => 'Comment on post',      'channels' => ['in_app', 'email', 'push']],
        'comment_reply'        => ['label' => 'Reply to comment',     'channels' => ['in_app', 'email', 'push']],
        'new_follower'         => ['label' => 'New follower',         'channels' => ['in_app', 'email']],
        'prayer_support'       => ['label' => 'Prayer support',       'channels' => ['in_app', 'email', 'push']],
        'question_answered'    => ['label' => 'Question answered',    'channels' => ['in_app', 'email', 'push']],
        'best_answer'          => ['label' => 'Best answer selected', 'channels' => ['in_app', 'email']],
        'event_reminder'       => ['label' => 'Event reminder',       'channels' => ['in_app', 'email', 'push']],
        'bible_study_starting' => ['label' => 'Bible study starting', 'channels' => ['in_app', 'email', 'push']],
        'community_invite'     => ['label' => 'Community invite',     'channels' => ['in_app', 'email']],
        'community_post'       => ['label' => 'Community post',       'channels' => ['in_app', 'email', 'push']],
        'church_page_post'     => ['label' => 'Church page post',     'channels' => ['in_app', 'email', 'push']],
        'daily_verse'          => ['label' => 'Daily verse',          'channels' => ['email', 'push']],
        'new_device_login'     => ['label' => 'New device login',     'channels' => ['in_app', 'email']],
        'mention'              => ['label' => 'Mention (@username)',   'channels' => ['in_app', 'email', 'push']],
        'admin_announcement'   => ['label' => 'Admin announcement',   'channels' => ['in_app', 'email', 'push']],
    ];

    /**
     * Send a notification to a user.
     *
     * @param  User   $recipient
     * @param  string $type       One of self::TYPES keys
     * @param  string $message    Human-readable message
     * @param  array  $data       Extra payload (url, actor, entity_id, etc.)
     */
    public function send(User $recipient, string $type, string $message, array $data = []): void
    {
        // Global channel kill-switch
        $globalSettings = $this->globalSettings();

        // Check throttle
        if ($this->isThrottled($recipient->id)) {
            return;
        }

        // Create in-app notification record
        if ($globalSettings['in_app_enabled'] ?? true) {
            $notif = DB::table('notifications_log')->insertGetId([
                'user_id'    => $recipient->id,
                'type'       => $type,
                'message'    => $message,
                'data'       => json_encode($data),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Broadcast via Reverb WebSocket
            broadcast(new \Plugins\Notification\Events\InAppNotificationEvent($recipient->id, [
                'id'      => $notif,
                'type'    => $type,
                'message' => $message,
                'data'    => $data,
            ]))->toOthers();
        }

        // Check user preferences
        $prefs = $this->userPrefs($recipient->id);

        // Email notification
        if (
            ($globalSettings['email_enabled'] ?? true)
            && ($prefs["{$type}_email"] ?? true)
            && in_array('email', self::TYPES[$type]['channels'] ?? [])
        ) {
            SendEmailNotificationJob::dispatch($recipient, $type, $message, $data)
                ->onQueue('notifications');
        }

        // Push notification
        if (
            ($globalSettings['push_enabled'] ?? true)
            && ($prefs["{$type}_push"] ?? true)
            && in_array('push', self::TYPES[$type]['channels'] ?? [])
        ) {
            SendPushNotificationJob::dispatch($recipient, $type, $message, $data)
                ->onQueue('notifications');
        }
    }

    /**
     * Broadcast an announcement to all users (or a filtered segment).
     */
    public function broadcast(string $message, array $data = [], array $userIds = []): void
    {
        $query = User::query();

        if (! empty($userIds)) {
            $query->whereIn('id', $userIds);
        }

        $query->chunkById(100, function ($users) use ($message, $data) {
            foreach ($users as $user) {
                $this->send($user, 'admin_announcement', $message, $data);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function globalSettings(): array
    {
        return $this->settings->get('notification_global_settings', [
            'in_app_enabled' => true,
            'email_enabled'  => true,
            'push_enabled'   => true,
            'throttle_per_hour' => 50,
        ]);
    }

    protected function userPrefs(int $userId): array
    {
        $row = DB::table('user_notification_prefs')
                 ->where('user_id', $userId)
                 ->first();

        return $row ? json_decode($row->prefs ?? '{}', true) : [];
    }

    protected function isThrottled(int $userId): bool
    {
        $limit = $this->globalSettings()['throttle_per_hour'] ?? 50;

        $count = DB::table('notifications_log')
                   ->where('user_id', $userId)
                   ->where('created_at', '>=', now()->subHour())
                   ->count();

        return $count >= $limit;
    }
}
