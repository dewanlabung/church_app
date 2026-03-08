<?php

namespace Plugins\Chat\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SocketBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = DB::table('chat_messages as m')
            ->join('users as u', function ($join) use ($userId) {
                $join->on('u.id', '=', DB::raw(
                    "CASE WHEN m.sender_id = {$userId} THEN m.receiver_id ELSE m.sender_id END"
                ));
            })
            ->where(function ($q) use ($userId) {
                $q->where('m.sender_id', $userId)->orWhere('m.receiver_id', $userId);
            })
            ->whereNull('m.deleted_at')
            ->select([
                'u.id as peer_id', 'u.name as peer_name', 'u.avatar as peer_avatar',
                DB::raw('MAX(m.id) as last_message_id'),
                DB::raw('MAX(m.created_at) as last_at'),
            ])
            ->groupBy('u.id', 'u.name', 'u.avatar')
            ->orderByDesc('last_at')
            ->limit(30)
            ->get();

        // Attach last message body
        foreach ($conversations as $conv) {
            $last = DB::table('chat_messages')->find($conv->last_message_id);
            $conv->last_message = $last?->body ?? '';
            $conv->unread_count = DB::table('chat_messages')
                ->where('sender_id', $conv->peer_id)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->whereNull('deleted_at')
                ->count();
        }

        return response()->json(['data' => $conversations]);
    }

    public function messages(Request $request, int $peerId)
    {
        $userId = $request->user()->id;
        $cursor = $request->input('cursor');

        $query = DB::table('chat_messages')
            ->where(function ($q) use ($userId, $peerId) {
                $q->where(fn($q2) => $q2->where('sender_id', $userId)->where('receiver_id', $peerId))
                  ->orWhere(fn($q2) => $q2->where('sender_id', $peerId)->where('receiver_id', $userId));
            })
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $items   = $query->limit(31)->get();
        $hasMore = $items->count() > 30;
        $items   = $items->take(30);

        // Mark received messages as read
        DB::table('chat_messages')
            ->where('sender_id', $peerId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'data'        => $items->values(),
            'next_cursor' => $hasMore ? $items->last()?->id : null,
        ]);
    }

    public function send(Request $request, int $peerId)
    {
        $validated = $request->validate([
            'body'  => 'required|string|max:5000',
            'media' => 'nullable|string',
        ]);

        $peer = DB::table('users')->find($peerId);
        abort_if(!$peer, 404, 'User not found.');

        $userId = $request->user()->id;

        $id = DB::table('chat_messages')->insertGetId([
            'sender_id'   => $userId,
            'receiver_id' => $peerId,
            'body'        => $validated['body'],
            'media'       => $validated['media'] ?? null,
            'is_read'     => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $message = DB::table('chat_messages')->find($id);

        // Push to peer via socket
        SocketBroadcaster::send($peerId, 'chat_message', [
            'message'     => $message,
            'sender_name' => $request->user()->name,
        ]);

        return response()->json($message, 201);
    }

    public function delete(Request $request, int $id)
    {
        $msg = DB::table('chat_messages')->find($id);
        abort_if(!$msg, 404);
        abort_if($msg->sender_id !== $request->user()->id, 403);

        DB::table('chat_messages')->where('id', $id)->update(['deleted_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
