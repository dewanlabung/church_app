<?php

namespace Plugins\Newsletter\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name'  => 'nullable|string|max:100',
        ]);

        $token = Str::random(32);

        DB::table('newsletter_subscribers')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'name'            => $validated['name'] ?? null,
                'unsubscribe_token' => $token,
                'subscribed_at'   => now(),
                'unsubscribed_at' => null,
                'is_active'       => true,
                'updated_at'      => now(),
                'created_at'      => now(),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Subscribed successfully.']);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $updated = DB::table('newsletter_subscribers')
            ->where('unsubscribe_token', $token)
            ->update([
                'is_active'       => false,
                'unsubscribed_at' => now(),
                'updated_at'      => now(),
            ]);

        if (!$updated) {
            abort(404, 'Invalid unsubscribe token.');
        }

        return response()->json(['ok' => true, 'message' => 'You have been unsubscribed.']);
    }

    // Admin: list subscribers
    public function subscribers(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;
        $search  = $request->input('search', '');
        $active  = $request->input('active', '');

        $query = DB::table('newsletter_subscribers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($active !== '') {
            $query->where('is_active', (bool) $active);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('subscribed_at')->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    // Admin: send broadcast
    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $siteName = config('app.name', 'Church Platform');
        $appUrl   = config('app.url');

        $subscribers = DB::table('newsletter_subscribers')
            ->where('is_active', true)
            ->select('email', 'name', 'unsubscribe_token')
            ->get();

        $sent = 0;
        foreach ($subscribers as $sub) {
            $unsubUrl = $appUrl . '/newsletter/unsubscribe/' . $sub->unsubscribe_token;
            try {
                Mail::send([], [], function (Message $mail) use ($sub, $validated, $siteName, $appUrl, $unsubUrl) {
                    $html = <<<HTML
<!DOCTYPE html><html><body style="font-family:sans-serif;background:#f3f4f6;padding:24px;">
<div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:32px;">
  <h2 style="color:#4F46E5;">{$siteName}</h2>
  <div style="font-size:15px;color:#374151;line-height:1.6;">{$validated['body']}</div>
  <p style="margin-top:24px;font-size:11px;color:#9ca3af;">
    <a href="{$unsubUrl}">Unsubscribe</a>
  </p>
</div></body></html>
HTML;
                    $mail->to($sub->email, $sub->name ?? $sub->email)
                         ->subject($validated['subject'])
                         ->html($html);
                });
                $sent++;
            } catch (\Throwable) {
                // Continue sending to others
            }
        }

        return response()->json(['sent' => $sent, 'total' => $subscribers->count()]);
    }

    // Admin: delete subscriber
    public function destroy(Request $request, int $id)
    {
        DB::table('newsletter_subscribers')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
