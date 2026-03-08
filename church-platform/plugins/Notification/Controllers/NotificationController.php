<?php

namespace Plugins\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $limit  = min((int) $request->input('limit', 20), 50);
        $cursor = $request->input('cursor');

        $query = DB::table('notifications_log')
            ->where('user_id', $userId)
            ->orderByDesc('id');

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $items   = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        $items   = $items->take($limit);

        return response()->json([
            'data'        => $items,
            'next_cursor' => $hasMore ? $items->last()?->id : null,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = DB::table('notifications_log')
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, int $id)
    {
        DB::table('notifications_log')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        DB::table('notifications_log')
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, int $id)
    {
        DB::table('notifications_log')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint'          => 'required|string|url',
            'keys.auth'         => 'required|string',
            'keys.p256dh'       => 'required|string',
            'user_agent'        => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;

        DB::table('push_subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'endpoint' => $validated['endpoint']],
            [
                'auth_key'   => $validated['keys']['auth'],
                'p256dh_key' => $validated['keys']['p256dh'],
                'user_agent' => $validated['user_agent'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['ok' => true], 201);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate(['endpoint' => 'required|string']);

        DB::table('push_subscriptions')
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
