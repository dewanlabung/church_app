<?php

namespace Plugins\Notification\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected User   $recipient,
        protected string $type,
        protected string $message,
        protected array  $data = []
    ) {}

    public function handle(): void
    {
        $url      = $this->data['url'] ?? config('app.url');
        $siteName = config('app.name', 'Church Platform');

        Mail::send([], [], function (Message $mail) use ($url, $siteName) {
            $mail->to($this->recipient->email, $this->recipient->name)
                 ->subject("[{$siteName}] {$this->message}")
                 ->html($this->buildHtml($siteName, $url));
        });
    }

    protected function buildHtml(string $siteName, string $url): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;background:#f3f4f6;padding:24px;">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:32px;">
    <h2 style="color:#4F46E5;">{$siteName}</h2>
    <p style="font-size:16px;color:#374151;">{$this->message}</p>
    <a href="{$url}"
       style="display:inline-block;margin-top:16px;padding:10px 20px;background:#4F46E5;color:#fff;border-radius:6px;text-decoration:none;">
      View
    </a>
    <p style="margin-top:24px;font-size:12px;color:#9ca3af;">
      You received this because you have notifications enabled.
      <a href="{$url}/settings/notifications">Manage preferences</a>
    </p>
  </div>
</body>
</html>
HTML;
    }
}
