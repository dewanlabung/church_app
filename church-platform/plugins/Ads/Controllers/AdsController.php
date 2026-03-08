<?php

namespace Plugins\Ads\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
    // Positions where ads can be placed
    const POSITIONS = ['feed_top', 'feed_between', 'sidebar', 'church_page_banner', 'footer'];

    /**
     * Public: get active ad for a position.
     */
    public function show(Request $request, string $position)
    {
        if (!in_array($position, self::POSITIONS)) {
            abort(422, 'Invalid ad position.');
        }

        // Suppress ads for premium users
        $user = $request->user();
        if ($user && $user->user_type === 'super_admin') {
            return response()->json(['ad' => null]);
        }

        $ad = DB::table('ad_placements')
            ->where('position', $position)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();

        return response()->json(['ad' => $ad]);
    }

    // Admin CRUD
    public function index()
    {
        $ads = DB::table('ad_placements')->orderByDesc('id')->get();
        return response()->json(['data' => $ads]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'position' => 'required|in:' . implode(',', self::POSITIONS),
            'name'     => 'required|string|max:100',
            'code'     => 'required|string',
            'is_active'=> 'boolean',
        ]);

        $id = DB::table('ad_placements')->insertGetId(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(DB::table('ad_placements')->find($id), 201);
    }

    public function update(Request $request, int $id)
    {
        $ad = DB::table('ad_placements')->find($id);
        abort_if(!$ad, 404);

        $validated = $request->validate([
            'position' => 'sometimes|in:' . implode(',', self::POSITIONS),
            'name'     => 'sometimes|string|max:100',
            'code'     => 'sometimes|string',
            'is_active'=> 'boolean',
        ]);

        DB::table('ad_placements')->where('id', $id)->update(array_merge($validated, ['updated_at' => now()]));

        return response()->json(DB::table('ad_placements')->find($id));
    }

    public function destroy(int $id)
    {
        DB::table('ad_placements')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
