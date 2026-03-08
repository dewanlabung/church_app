<?php

namespace Plugins\ChurchPage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChurchPageController extends Controller
{
    public function directory(Request $request)
    {
        $search  = $request->input('search', '');
        $country = $request->input('country', '');
        $perPage = min((int) $request->input('per_page', 18), 50);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $query = DB::table('church_pages as c')
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->whereNull('c.deleted_at')
            ->select([
                'c.id', 'c.name', 'c.slug', 'c.about', 'c.logo', 'c.banner',
                'c.denomination', 'c.city', 'c.country', 'c.followers_count',
                'c.members_count', 'c.is_verified', 'c.created_at',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('c.name', 'like', "%{$search}%")
                  ->orWhere('c.about', 'like', "%{$search}%")
                  ->orWhere('c.city', 'like', "%{$search}%");
            });
        }

        if ($country) {
            $query->where('c.country', $country);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('c.followers_count')->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $church = DB::table('church_pages as c')
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->where('c.slug', $slug)
            ->whereNull('c.deleted_at')
            ->select([
                'c.*',
                'u.name as owner_name', 'u.avatar as owner_avatar',
            ])
            ->first();

        abort_if(!$church, 404, 'Church page not found.');

        // Decode JSON fields
        $church->social_links      = $church->social_links      ? json_decode($church->social_links, true)      : [];
        $church->service_schedule  = $church->service_schedule  ? json_decode($church->service_schedule, true)  : [];
        $church->seo_meta          = $church->seo_meta          ? json_decode($church->seo_meta, true)          : [];

        $userId       = optional($request->user())->id;
        $isFollowing  = $userId && DB::table('church_page_members')
            ->where("church_page_id", $church->id)
            ->where('user_id', $userId)
            ->exists();

        return response()->json([
            'church'       => $church,
            'is_following' => $isFollowing,
        ]);
    }

    public function incrementView(Request $request, string $slug)
    {
        DB::table('church_pages')->where('slug', $slug)->increment('followers_count', 0); // no-op placeholder
        return response()->json(['ok' => true]);
    }

    public function feed(Request $request, string $slug)
    {
        $church = DB::table('church_pages')->where('slug', $slug)->whereNull('deleted_at')->first();
        abort_if(!$church, 404);

        $cursor = $request->input('cursor');

        $query = DB::table('social_posts as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('p.church_id', $church->id)
            ->whereNull('p.deleted_at')
            ->select([
                'p.id', 'p.body', 'p.type', 'p.media', 'p.reactions_count',
                'p.comments_count', 'p.shares_count', 'p.created_at',
                'u.id as author_id', 'u.name as author_name', 'u.avatar as author_avatar',
            ])
            ->orderByDesc('p.id');

        if ($cursor) {
            $query->where('p.id', '<', $cursor);
        }

        $items   = $query->limit(16)->get();
        $hasMore = $items->count() === 16;
        $items   = $items->take(15)->map(function ($p) {
            $p->media = $p->media ? json_decode($p->media, true) : [];
            return $p;
        });

        return response()->json([
            'data'        => $items,
            'next_cursor' => $hasMore ? $items->last()?->id : null,
        ]);
    }

    public function events(Request $request, string $slug)
    {
        $church = DB::table('church_pages')->where('slug', $slug)->whereNull('deleted_at')->first();
        abort_if(!$church, 404);

        $events = DB::table('events')
            ->where("church_page_id", $church->id)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        return response()->json(['data' => $events]);
    }

    // Admin: list all churches
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;
        $search  = $request->input('search', '');

        $query = DB::table('church_pages')->whereNull('deleted_at');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('id')->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:120',
            'about'        => 'nullable|string|max:5000',
            'denomination' => 'nullable|string|max:100',
            'website'      => 'nullable|url',
            'email'        => 'nullable|email',
            'phone'        => 'nullable|string|max:30',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i    = 1;
        while (DB::table('church_pages')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $id = DB::table('church_pages')->insertGetId(array_merge($validated, [
            'user_id'    => $request->user()->id,
            'slug'       => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(DB::table('church_pages')->find($id), 201);
    }

    public function update(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);
        $this->authorizeChurchAdmin($request, $row);

        $validated = $request->validate([
            'name'             => 'sometimes|string|max:120',
            'about'            => 'nullable|string|max:5000',
            'denomination'     => 'nullable|string|max:100',
            'website'          => 'nullable|url',
            'email'            => 'nullable|email',
            'phone'            => 'nullable|string|max:30',
            'address'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'country'          => 'nullable|string|max:100',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'social_links'     => 'nullable|array',
            'service_schedule' => 'nullable|array',
        ]);

        if (isset($validated['social_links'])) {
            $validated['social_links'] = json_encode($validated['social_links']);
        }
        if (isset($validated['service_schedule'])) {
            $validated['service_schedule'] = json_encode($validated['service_schedule']);
        }

        DB::table('church_pages')->where('id', $church)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        return response()->json(DB::table('church_pages')->find($church));
    }

    public function destroy(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);
        $this->authorizeChurchAdmin($request, $row);

        DB::table('church_pages')->where('id', $church)->update(['deleted_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function updateStatus(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);

        $validated = $request->validate(['status' => 'required|in:active,inactive']);
        DB::table('church_pages')->where('id', $church)->update(['is_verified' => $validated['status'] === 'active']);

        return response()->json(['ok' => true]);
    }

    public function toggleFeatured(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);
        DB::table('church_pages')->where('id', $church)->update(['is_verified' => !$row->is_verified]);
        return response()->json(['ok' => true]);
    }

    public function myChurch(Request $request)
    {
        $church = DB::table('church_pages')
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->first();

        return response()->json(['church' => $church]);
    }

    protected function authorizeChurchAdmin(Request $request, object $row): void
    {
        $user = $request->user();
        if ($row->user_id !== $user->id && !$user->hasRole(['super_admin'])) {
            abort(403);
        }
    }
}
