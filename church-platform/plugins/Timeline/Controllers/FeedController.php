<?php

namespace Plugins\Timeline\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all'); // all|prayer|bible|events|church
        $cursor = $request->input('cursor');
        $limit = min($request->input('limit', 15), 50);

        $query = DB::table('social_feeds as f')
            ->join('social_posts as p', 'f.post_id', '=', 'p.id')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('f.user_id', $user->id)
            ->whereNull('p.deleted_at')
            ->select([
                'p.id', 'p.user_id', 'p.type', 'p.body', 'p.media',
                'p.privacy', 'p.meta', 'p.community_id', 'p.church_page_id',
                'p.parent_id', 'p.reactions_count', 'p.comments_count',
                'p.shares_count', 'p.created_at',
                'u.name', 'u.avatar', 'u.user_type',
                'f.id as feed_id',
            ])
            ->orderByDesc('f.id');

        if ($filter !== 'all') {
            $typeMap = [
                'prayer'  => ['prayer_request'],
                'bible'   => ['bible_verse', 'bible_study_session'],
                'events'  => ['event_share'],
                'church'  => ['blessing'],
            ];
            if (isset($typeMap[$filter])) {
                $query->whereIn('p.type', $typeMap[$filter]);
            }
        }

        if ($cursor) {
            $query->where('f.id', '<', $cursor);
        }

        $items = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        $items = $items->take($limit);
        $nextCursor = $hasMore ? $items->last()?->feed_id : null;

        return response()->json([
            'data'        => $items,
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ]);
    }

    public function publicFeed(Request $request)
    {
        $cursor = $request->input('cursor');
        $limit = min($request->input('limit', 15), 50);

        $query = DB::table('social_posts as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->whereNull('p.deleted_at')
            ->where('p.privacy', 'public')
            ->whereNull('p.community_id')
            ->whereNull('p.parent_id')
            ->select([
                'p.id', 'p.user_id', 'p.type', 'p.body', 'p.media',
                'p.reactions_count', 'p.comments_count', 'p.created_at',
                'u.name', 'u.avatar',
            ])
            ->orderByDesc('p.id');

        if ($cursor) {
            $query->where('p.id', '<', $cursor);
        }

        $items = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        $items = $items->take($limit);
        $nextCursor = $hasMore ? $items->last()?->id : null;

        return response()->json([
            'data'        => $items,
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ]);
    }
}
