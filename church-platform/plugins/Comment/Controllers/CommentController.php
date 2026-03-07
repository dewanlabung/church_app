<?php

namespace Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Comment\Models\Comment;

class CommentController extends Controller
{
    public function index(Request $request, int $postId)
    {
        $comments = Comment::with('author:id,name,avatar')
            ->withCount('replies')
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->latest()
            ->cursorPaginate(20);

        return response()->json($comments);
    }

    public function replies(Request $request, int $commentId)
    {
        $replies = Comment::with('author:id,name,avatar')
            ->where('parent_id', $commentId)
            ->oldest()
            ->limit(50)
            ->get();

        return response()->json($replies);
    }

    public function store(Request $request, int $postId)
    {
        $validated = $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:social_comments,id',
        ]);

        if (isset($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);
            // Only allow 2 levels deep
            if ($parent && $parent->parent_id !== null) {
                return response()->json(['message' => 'Maximum comment depth reached.'], 422);
            }
        }

        $comment = Comment::create([
            'user_id'   => $request->user()->id,
            'post_id'   => $postId,
            'body'      => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        \Illuminate\Support\Facades\DB::table('social_posts')
            ->where('id', $postId)
            ->increment('comments_count');

        return response()->json($comment->load('author:id,name,avatar'), 201);
    }

    public function update(Request $request, int $id)
    {
        $comment = Comment::findOrFail($id);
        $this->authorize('update', $comment);

        $comment->update($request->validate(['body' => 'required|string|max:5000']));

        return response()->json($comment);
    }

    public function destroy(Request $request, int $id)
    {
        $comment = Comment::findOrFail($id);
        $this->authorize('delete', $comment);

        $postId = $comment->post_id;
        $comment->delete();

        \Illuminate\Support\Facades\DB::table('social_posts')
            ->where('id', $postId)
            ->decrement('comments_count');

        return response()->json(['message' => 'Deleted.']);
    }
}
