<?php

namespace Plugins\Reaction\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Reaction\Models\Reaction;

class ReactionController extends Controller
{
    private array $validTypes = ['like', 'bless', 'amen', 'pray', 'love'];
    private array $validTargets = [
        'post'    => 'social_posts',
        'comment' => 'social_comments',
    ];

    public function react(Request $request)
    {
        $validated = $request->validate([
            'reactable_type' => 'required|in:post,comment',
            'reactable_id'   => 'required|integer',
            'type'           => 'required|in:like,bless,amen,pray,love',
        ]);

        $type     = $validated['reactable_type'];
        $id       = $validated['reactable_id'];
        $reaction = $validated['type'];
        $userId   = $request->user()->id;
        $table    = $this->validTargets[$type];

        // Remove previous reaction (toggle or change)
        $existing = Reaction::where('user_id', $userId)
            ->where('reactable_type', $type)
            ->where('reactable_id', $id)
            ->first();

        if ($existing) {
            if ($existing->type === $reaction) {
                // Same reaction → unreact
                $existing->delete();
                DB::table($table)->where('id', $id)->decrement('reactions_count');
                return response()->json(['action' => 'removed', 'type' => $reaction]);
            }
            // Change reaction type
            $existing->update(['type' => $reaction]);
            return response()->json(['action' => 'changed', 'type' => $reaction]);
        }

        Reaction::create([
            'user_id'        => $userId,
            'reactable_type' => $type,
            'reactable_id'   => $id,
            'type'           => $reaction,
        ]);
        DB::table($table)->where('id', $id)->increment('reactions_count');

        return response()->json(['action' => 'added', 'type' => $reaction]);
    }

    public function reactions(Request $request, string $type, int $id)
    {
        if (!isset($this->validTargets[$type])) {
            return response()->json(['message' => 'Invalid type.'], 422);
        }

        $reactions = Reaction::with('user:id,name,avatar')
            ->where('reactable_type', $type)
            ->where('reactable_id', $id)
            ->when($request->input('reaction_type'), fn($q, $t) => $q->where('type', $t))
            ->get()
            ->groupBy('type');

        $summary = [];
        foreach (Reaction::TYPES as $t) {
            $summary[$t] = [
                'count' => $reactions->get($t, collect())->count(),
                'users' => $reactions->get($t, collect())->take(3)->pluck('user'),
            ];
        }

        $myReaction = $request->user()
            ? Reaction::where('user_id', $request->user()->id)
                ->where('reactable_type', $type)
                ->where('reactable_id', $id)
                ->value('type')
            : null;

        return response()->json([
            'summary'     => $summary,
            'my_reaction' => $myReaction,
        ]);
    }
}
