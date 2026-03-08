<?php

namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunityMemberController extends Controller
{
    protected function getCommunity(int $id): object
    {
        $community = DB::table('communities')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(!$community, 404, 'Community not found.');
        return $community;
    }

    public function index(Request $request, int $community)
    {
        $c       = $this->getCommunity($community);
        $perPage = min((int) $request->input('per_page', 24), 50);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $total   = DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('status', 'active')
            ->count();

        $members = DB::table('community_members as cm')
            ->join('users as u', 'cm.user_id', '=', 'u.id')
            ->where('cm.community_id', $c->id)
            ->where('cm.status', 'active')
            ->select('cm.id', 'cm.role', 'cm.created_at as joined_at', 'u.id as user_id', 'u.name', 'u.avatar')
            ->orderByRaw("FIELD(cm.role, 'owner', 'admin', 'moderator', 'member')")
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data'         => $members,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function join(Request $request, int $community)
    {
        $c      = $this->getCommunity($community);
        $userId = $request->user()->id;

        $existing = DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->status === 'active') {
                return response()->json(['status' => 'already_member']);
            }
            if ($existing->status === 'banned') {
                abort(403, 'You are banned from this community.');
            }
        }

        $status = $c->type === 'private' ? 'pending' : 'active';

        if ($existing) {
            DB::table('community_members')
                ->where('id', $existing->id)
                ->update(['status' => $status, 'updated_at' => now()]);
        } else {
            DB::table('community_members')->insert([
                'community_id' => $c->id,
                'user_id'      => $userId,
                'role'         => 'member',
                'status'       => $status,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        if ($status === 'active') {
            DB::table('communities')->where('id', $c->id)->increment('members_count');
        }

        return response()->json(['status' => $status]);
    }

    public function leave(Request $request, int $community)
    {
        $c      = $this->getCommunity($community);
        $userId = $request->user()->id;

        abort_if($c->owner_id === $userId, 403, 'Owner cannot leave the community.');

        $deleted = DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->delete();

        if ($deleted) {
            DB::table('communities')->where('id', $c->id)->decrement('members_count');
        }

        return response()->json(['ok' => true]);
    }

    public function invite(Request $request, int $community)
    {
        $c = $this->getCommunity($community);

        // Regenerate invite token
        $token = Str::random(32);
        DB::table('communities')->where('id', $c->id)->update(['invite_token' => $token]);

        return response()->json(['invite_url' => config('app.url') . "/c/join/{$token}"]);
    }

    public function updateRole(Request $request, int $community, int $user)
    {
        $c = $this->getCommunity($community);
        $this->requireAdminRole($request, $c);

        $validated = $request->validate(['role' => 'required|in:admin,moderator,member']);

        DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $user)
            ->update(['role' => $validated['role'], 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function remove(Request $request, int $community, int $user)
    {
        $c = $this->getCommunity($community);
        $this->requireAdminRole($request, $c);

        abort_if($c->owner_id === $user, 403, 'Cannot remove the owner.');

        DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $user)
            ->update(['status' => 'banned', 'updated_at' => now()]);

        DB::table('communities')->where('id', $c->id)->decrement('members_count');

        return response()->json(['ok' => true]);
    }

    public function requests(Request $request, int $community)
    {
        $c = $this->getCommunity($community);
        $this->requireAdminRole($request, $c);

        $pending = DB::table('community_members as cm')
            ->join('users as u', 'cm.user_id', '=', 'u.id')
            ->where('cm.community_id', $c->id)
            ->where('cm.status', 'pending')
            ->select('cm.id', 'cm.user_id', 'cm.created_at as requested_at', 'u.name', 'u.avatar', 'u.email')
            ->get();

        return response()->json(['data' => $pending]);
    }

    public function approve(Request $request, int $community, int $user)
    {
        $c = $this->getCommunity($community);
        $this->requireAdminRole($request, $c);

        $updated = DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $user)
            ->where('status', 'pending')
            ->update(['status' => 'active', 'updated_at' => now()]);

        if ($updated) {
            DB::table('communities')->where('id', $c->id)->increment('members_count');
        }

        return response()->json(['ok' => true]);
    }

    public function deny(Request $request, int $community, int $user)
    {
        $c = $this->getCommunity($community);
        $this->requireAdminRole($request, $c);

        DB::table('community_members')
            ->where('community_id', $c->id)
            ->where('user_id', $user)
            ->where('status', 'pending')
            ->delete();

        return response()->json(['ok' => true]);
    }

    protected function requireAdminRole(Request $request, object $community): void
    {
        $user = $request->user();
        if ($community->owner_id === $user->id || $user->hasRole(['super_admin'])) {
            return;
        }
        $isAdmin = DB::table('community_members')
            ->where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->where('status', 'active')
            ->exists();
        abort_if(!$isAdmin, 403, 'Insufficient permissions.');
    }
}
