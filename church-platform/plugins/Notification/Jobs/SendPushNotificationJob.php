<?php

namespace Plugins\Notification\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        protected User   $recipient,
        protected string $type,
        protected string $message,
        protected array  $data = []
    ) {}

    public function handle(): void
    {
        $subscriptions = DB::table('push_subscriptions')
            ->where('user_id', $this->recipient->id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapidPublic  = config('app.vapid_public_key');
        $vapidPrivate = config('app.vapid_private_key');

        if (!$vapidPublic || !$vapidPrivate) {
            Log::warning('Push notifications: VAPID keys not configured.');
            return;
        }

        $payload = json_encode([
            'title'   => config('app.name', 'Church Platform'),
            'body'    => $this->message,
            'url'     => $this->data['url'] ?? config('app.url'),
            'type'    => $this->type,
            'icon'    => config('app.url') . '/icons/icon-192x192.png',
        ]);

        foreach ($subscriptions as $sub) {
            try {
                // Use web-push standard: POST to endpoint with encrypted payload
                // In production, use minishlink/web-push package for proper VAPID signing
                // This is a simplified placeholder that logs the push intent
                Log::info("Push notification queued", [
                    'user_id'  => $this->recipient->id,
                    'endpoint' => substr($sub->endpoint, 0, 50) . '...',
                    'type'     => $this->type,
                    'message'  => $this->message,
                ]);
            } catch (\Throwable $e) {
                Log::error("Push notification failed: " . $e->getMessage(), [
                    'user_id'  => $this->recipient->id,
                    'endpoint' => $sub->endpoint,
                ]);

                // Remove invalid subscriptions (410 Gone)
                if (str_contains($e->getMessage(), '410')) {
                    DB::table('push_subscriptions')
                        ->where('id', $sub->id)
                        ->delete();
                }
            }
        }
    }
}
