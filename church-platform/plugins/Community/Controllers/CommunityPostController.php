<?php

namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityPostController extends Controller
{
    public function index(Request $request, int $community)
    {
        $c = DB::table('communities')->find($community);
        abort_if(!$c, 404);

        // For private/hidden communities, require membership
        if (in_array($c->type, ['private', 'hidden'])) {
            $userId = optional($request->user())->id;
            $isMember = $userId && DB::table('community_members')
                ->where('community_id', $c->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();
            abort_if(!$isMember, 403, 'Join the community to view posts.');
        }

        $cursor = $request->input('cursor');

        $query = DB::table('social_posts as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('p.community_id', $c->id)
            ->whereNull('p.deleted_at')
            ->select([
                'p.id', 'p.body', 'p.type', 'p.privacy', 'p.media',
                'p.reactions_count', 'p.comments_count', 'p.shares_count',
                'p.created_at',
                'u.id as author_id', 'u.name as author_name', 'u.avatar as author_avatar',
            ])
            ->orderByDesc('p.id');

        if ($cursor) {
            $query->where('p.id', '<', $cursor);
        }

        $items   = $query->limit(16)->get();
        $hasMore = $items->count() === 16;
        $items   = $items->take(15);

        // Decode media JSON
        $items = $items->map(function ($post) {
            $post->media = $post->media ? json_decode($post->media, true) : [];
            return $post;
        });

        return response()->json([
            'data'        => $items,
            'next_cursor' => $hasMore ? $items->last()?->id : null,
        ]);
    }

    public function store(Request $request, int $community)
    {
        $c = DB::table('communities')->find($community);
        abort_if(!$c, 404);

        $userId   = $request->user()->id;
        $isMember = DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();

        abort_if(!$isMember, 403, 'You must be a member to post.');

        $validated = $request->validate([
            'body'    => 'required|string|max:10000',
            'type'    => 'nullable|in:text,photo,video,link,poll',
            'media'   => 'nullable|array',
        ]);

        $id = DB::table('social_posts')->insertGetId([
            'user_id'      => $userId,
            'community_id' => $c->id,
            'body'         => $validated['body'],
            'type'         => $validated['type'] ?? 'text',
            'privacy'      => 'public',
            'media'        => isset($validated['media']) ? json_encode($validated['media']) : null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('communities')->where('id', $c->id)->increment('posts_count');

        return response()->json(DB::table('social_posts')->find($id), 201);
    }
}
