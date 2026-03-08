<?php

namespace Plugins\ChurchPage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChurchMemberController extends Controller
{
    public function follow(Request $request, int $church)
    {
        $row    = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);

        $userId = $request->user()->id;

        $exists = DB::table('church_page_members')
            ->where('church_page_id', $church)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            DB::table('church_page_members')->insert([
                'church_page_id' => $church,
                'user_id'        => $userId,
                'type'           => 'follow',
                'role'           => 'member',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::table('church_pages')->where('id', $church)->increment('followers_count');
        }

        return response()->json(['following' => true]);
    }

    public function unfollow(Request $request, int $church)
    {
        $userId  = $request->user()->id;
        $deleted = DB::table('church_page_members')
            ->where('church_page_id', $church)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            DB::table('church_pages')->where('id', $church)->decrement('followers_count');
        }

        return response()->json(['following' => false]);
    }

    public function followers(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);

        $perPage = min((int) $request->input('per_page', 24), 50);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $total = DB::table('church_page_members')
            ->where('church_page_id', $church)
            ->count();

        $followers = DB::table('church_page_members as cm')
            ->join('users as u', 'cm.user_id', '=', 'u.id')
            ->where('cm.church_page_id', $church)
            ->select('u.id', 'u.name', 'u.avatar', 'cm.type', 'cm.role', 'cm.created_at as followed_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data'         => $followers,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }
}
