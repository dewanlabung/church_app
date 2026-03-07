<?php

namespace Plugins\Post\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Plugins\Post\Models\Post;
use Plugins\Post\Jobs\FanOutPostJob;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $posts = Post::with('author:id,name,avatar,user_type')
            ->forUser($user)
            ->latest()
            ->cursorPaginate(15);

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'           => 'required|in:text,photo,video,feeling,poll,link,document,blessing,prayer_request,ask_question,bible_verse,event_share',
            'body'           => 'nullable|string|max:10000',
            'media'          => 'nullable|array',
            'privacy'        => 'nullable|in:public,community,followers,only_me',
            'meta'           => 'nullable|array',
            'community_id'   => 'nullable|exists:communities,id',
            'church_page_id' => 'nullable|exists:church_pages,id',
            'parent_id'      => 'nullable|exists:social_posts,id',
        ]);

        if (empty($validated['body']) && empty($validated['media'])) {
            return response()->json(['message' => 'Post must have body or media.'], 422);
        }

        $post = Post::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'privacy' => $validated['privacy'] ?? 'public',
        ]);

        // Fan out to followers' feeds (async)
        FanOutPostJob::dispatch($post)->onQueue('feeds');

        return response()->json($post->load('author:id,name,avatar'), 201);
    }

    public function show(Request $request, int $id)
    {
        $post = Post::with([
            'author:id,name,avatar,user_type',
            'parent.author:id,name,avatar',
        ])->findOrFail($id);

        if ($post->privacy === 'only_me' && $post->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($post);
    }

    public function update(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('update', $post);

        $validated = $request->validate([
            'body'    => 'nullable|string|max:10000',
            'privacy' => 'nullable|in:public,community,followers,only_me',
            'meta'    => 'nullable|array',
        ]);

        $post->update($validated);

        return response()->json($post);
    }

    public function destroy(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);

        $post->delete();
        // Remove from feeds
        \Illuminate\Support\Facades\DB::table('social_feeds')->where('post_id', $id)->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function togglePrivacy(Request $request, int $id)
    {
        $post = Post::findOrFail($id);
        $this->authorize('update', $post);

        $post->update(['privacy' => $request->input('privacy', 'public')]);

        return response()->json(['privacy' => $post->privacy]);
    }

    public function saves(Request $request)
    {
        $user = $request->user();
        $saved = Post::with('author:id,name,avatar')
            ->join('social_post_saves as s', 's.post_id', '=', 'social_posts.id')
            ->where('s.user_id', $user->id)
            ->orderByDesc('s.created_at')
            ->cursorPaginate(15);

        return response()->json($saved);
    }

    public function toggleSave(Request $request, int $id)
    {
        $user = $request->user();
        $exists = \Illuminate\Support\Facades\DB::table('social_post_saves')
            ->where('user_id', $user->id)->where('post_id', $id)->exists();

        if ($exists) {
            \Illuminate\Support\Facades\DB::table('social_post_saves')
                ->where('user_id', $user->id)->where('post_id', $id)->delete();
            return response()->json(['saved' => false]);
        }

        \Illuminate\Support\Facades\DB::table('social_post_saves')
            ->insert(['user_id' => $user->id, 'post_id' => $id, 'created_at' => now()]);

        return response()->json(['saved' => true]);
    }

    public function share(Request $request, int $id)
    {
        $original = Post::findOrFail($id);

        $share = Post::create([
            'user_id'   => $request->user()->id,
            'type'      => $original->type,
            'body'      => $request->input('body', ''),
            'privacy'   => $request->input('privacy', 'public'),
            'parent_id' => $original->id,
        ]);

        Post::where('id', $id)->increment('shares_count');
        FanOutPostJob::dispatch($share)->onQueue('feeds');

        return response()->json($share->load('author:id,name,avatar', 'parent.author:id,name,avatar'), 201);
    }

    public function userPosts(Request $request, int $userId)
    {
        $viewer = $request->user();
        $posts = Post::with('author:id,name,avatar')
            ->where('user_id', $userId)
            ->where(function ($q) use ($viewer, $userId) {
                $q->where('privacy', 'public');
                if ($viewer && $viewer->id === $userId) {
                    $q->orWhereIn('privacy', ['followers', 'only_me', 'community']);
                }
            })
            ->whereNull('community_id')
            ->latest()
            ->cursorPaginate(15);

        return response()->json($posts);
    }
}
