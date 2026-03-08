<?php

namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->input('search', '');
        $category = $request->input('category', '');
        $type     = $request->input('type', 'public'); // public|all
        $perPage  = min((int) $request->input('per_page', 18), 50);
        $page     = max((int) $request->input('page', 1), 1);
        $offset   = ($page - 1) * $perPage;

        $query = DB::table('communities as c')
            ->join('users as u', 'c.owner_id', '=', 'u.id')
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->select([
                'c.id', 'c.name', 'c.slug', 'c.about', 'c.avatar', 'c.cover_image',
                'c.type', 'c.category', 'c.members_count', 'c.posts_count', 'c.created_at',
                'u.name as owner_name',
            ]);

        if ($type !== 'all') {
            $query->where('c.type', 'public');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('c.name', 'like', "%{$search}%")
                  ->orWhere('c.about', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('c.category', $category);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('c.members_count')->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $community = DB::table('communities as c')
            ->join('users as u', 'c.owner_id', '=', 'u.id')
            ->where('c.slug', $slug)
            ->whereNull('c.deleted_at')
            ->select([
                'c.*', 'u.name as owner_name', 'u.avatar as owner_avatar',
            ])
            ->first();

        abort_if(!$community, 404, 'Community not found.');

        // Hidden communities: only visible to members
        if ($community->type === 'hidden') {
            $userId = optional($request->user())->id;
            $isMember = $userId && DB::table('community_members')
                ->where('community_id', $community->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();
            if (!$isMember) {
                abort(404, 'Community not found.');
            }
        }

        $userId      = optional($request->user())->id;
        $membership  = $userId
            ? DB::table('community_members')
                ->where('community_id', $community->id)
                ->where('user_id', $userId)
                ->first()
            : null;

        return response()->json([
            'community'  => $community,
            'membership' => $membership,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'about'       => 'nullable|string|max:2000',
            'type'        => 'required|in:public,private,hidden',
            'category'    => 'nullable|string|max:50',
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i    = 1;
        while (DB::table('communities')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $id = DB::table('communities')->insertGetId([
            'owner_id'      => $request->user()->id,
            'name'          => $validated['name'],
            'slug'          => $slug,
            'about'         => $validated['about'] ?? null,
            'type'          => $validated['type'],
            'category'      => $validated['category'] ?? null,
            'invite_token'  => Str::random(32),
            'members_count' => 1,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Auto-join owner as owner
        DB::table('community_members')->insert([
            'community_id' => $id,
            'user_id'      => $request->user()->id,
            'role'         => 'owner',
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json(DB::table('communities')->find($id), 201);
    }

    public function update(Request $request, int $community)
    {
        $row = DB::table('communities')->find($community);
        abort_if(!$row, 404);

        $this->authorizeAdmin($request, $row);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'about'       => 'nullable|string|max:2000',
            'type'        => 'sometimes|in:public,private,hidden',
            'category'    => 'nullable|string|max:50',
            'rules'       => 'nullable|array',
        ]);

        DB::table('communities')->where('id', $community)->update(array_merge(
            $validated,
            ['updated_at' => now()]
        ));

        return response()->json(DB::table('communities')->find($community));
    }

    public function destroy(Request $request, int $community)
    {
        $row = DB::table('communities')->find($community);
        abort_if(!$row, 404);

        $this->authorizeAdmin($request, $row);

        DB::table('communities')->where('id', $community)->update([
            'deleted_at' => now(),
            'is_active'  => false,
        ]);

        return response()->json(['ok' => true]);
    }

    protected function authorizeAdmin(Request $request, object $row): void
    {
        $user = $request->user();
        if ($row->owner_id !== $user->id && !$user->hasRole(['super_admin'])) {
            $isAdmin = DB::table('community_members')
                ->where('community_id', $row->id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'admin'])
                ->where('status', 'active')
                ->exists();
            abort_if(!$isAdmin, 403);
        }
    }
}
