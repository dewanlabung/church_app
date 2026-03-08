<?php

namespace Plugins\ChurchPage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChurchSeoController extends Controller
{
    public function show(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);
        $this->authorizeAdmin($request, $row);

        $seoMeta = $row->seo_meta ? json_decode($row->seo_meta, true) : [];

        $defaults = [
            'meta_title'       => $row->name,
            'meta_description' => $row->about ? mb_substr(strip_tags($row->about), 0, 160) : '',
            'og_image'         => $row->banner ?? $row->logo ?? null,
            'canonical_url'    => config('app.url') . '/church/' . $row->slug,
            'schema_type'      => 'Church',
            'no_index'         => false,
        ];

        return response()->json(array_merge($defaults, $seoMeta));
    }

    public function update(Request $request, int $church)
    {
        $row = DB::table('church_pages')->find($church);
        abort_if(!$row, 404);
        $this->authorizeAdmin($request, $row);

        $validated = $request->validate([
            'meta_title'       => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:320',
            'og_image'         => 'nullable|string|url',
            'canonical_url'    => 'nullable|string|url',
            'schema_type'      => 'nullable|string|max:50',
            'no_index'         => 'nullable|boolean',
        ]);

        DB::table('church_pages')->where('id', $church)->update([
            'seo_meta'   => json_encode($validated),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    protected function authorizeAdmin(Request $request, object $row): void
    {
        $user = $request->user();
        if ($row->user_id !== $user->id && !$user->hasRole(['super_admin'])) {
            abort(403);
        }
    }
}
