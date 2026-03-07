<?php

namespace Plugins\Prayer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrayerController extends Controller
{
    public function wall(Request $request)
    {
        $cursor = $request->input('cursor');

        $query = DB::table('prayer_requests as pr')
            ->join('social_posts as p', 'pr.post_id', '=', 'p.id')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('p.privacy', 'public')
            ->select([
                'pr.id', 'pr.is_anonymous', 'pr.is_answered', 'pr.support_count',
                'p.body', 'p.created_at', 'p.id as post_id',
                DB::raw("CASE WHEN pr.is_anonymous = 1 THEN NULL ELSE u.id END as user_id"),
                DB::raw("CASE WHEN pr.is_anonymous = 1 THEN 'Anonymous' ELSE u.name END as author_name"),
                DB::raw("CASE WHEN pr.is_anonymous = 1 THEN NULL ELSE u.avatar END as author_avatar"),
            ])
            ->orderByDesc('pr.id');

        if ($cursor) {
            $query->where('pr.id', '<', $cursor);
        }

        $items = $query->limit(16)->get();
        $hasMore = $items->count() === 16;
        $items = $items->take(15);

        return response()->json([
            'data'        => $items,
            'next_cursor' => $hasMore ? $items->last()?->id : null,
        ]);
    }

    public function support(Request $request, int $prayerId)
    {
        $userId = $request->user()->id;
        $exists = DB::table('prayer_request_supports')
            ->where('prayer_request_id', $prayerId)->where('user_id', $userId)->exists();

        if ($exists) {
            DB::table('prayer_request_supports')
                ->where('prayer_request_id', $prayerId)->where('user_id', $userId)->delete();
            DB::table('prayer_requests')->where('id', $prayerId)->decrement('support_count');
            return response()->json(['supported' => false]);
        }

        DB::table('prayer_request_supports')
            ->insert(['prayer_request_id' => $prayerId, 'user_id' => $userId, 'created_at' => now()]);
        DB::table('prayer_requests')->where('id', $prayerId)->increment('support_count');

        return response()->json(['supported' => true]);
    }

    public function markAnswered(Request $request, int $prayerId)
    {
        $prayer = DB::table('prayer_requests')->find($prayerId);
        abort_if(!$prayer, 404);

        if ($prayer->user_id !== $request->user()->id) {
            abort(403);
        }

        DB::table('prayer_requests')->where('id', $prayerId)->update([
            'is_answered'   => true,
            'answered_note' => $request->input('note'),
        ]);

        return response()->json(['is_answered' => true]);
    }

    public function privateRespond(Request $request, int $prayerId)
    {
        if (!$request->user()->hasRole(['counsellor', 'super_admin'])) {
            abort(403, 'Only counsellors can respond privately.');
        }

        $validated = $request->validate(['message' => 'required|string|max:5000']);

        $id = DB::table('prayer_private_responses')->insertGetId([
            'prayer_request_id' => $prayerId,
            'counsellor_id'     => $request->user()->id,
            'message'           => $validated['message'],
            'created_at'        => now(),
        ]);

        return response()->json(DB::table('prayer_private_responses')->find($id), 201);
    }
}
