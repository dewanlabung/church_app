<?php

namespace Plugins\Analytics\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        $validated = $request->validate([
            'event'      => 'required|string|max:100',
            'page'       => 'nullable|string|max:500',
            'entity_type'=> 'nullable|string|max:50',
            'entity_id'  => 'nullable|integer',
            'meta'       => 'nullable|array',
        ]);

        DB::table('analytics_events')->insert([
            'user_id'     => optional($request->user())->id,
            'event'       => $validated['event'],
            'page'        => $validated['page'] ?? $request->header('Referer'),
            'entity_type' => $validated['entity_type'] ?? null,
            'entity_id'   => $validated['entity_id'] ?? null,
            'meta'        => isset($validated['meta']) ? json_encode($validated['meta']) : null,
            'ip'          => $request->ip(),
            'user_agent'  => mb_substr($request->userAgent() ?? '', 0, 255),
            'created_at'  => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function dashboard(Request $request)
    {
        $days  = min((int) $request->input('days', 30), 90);
        $since = now()->subDays($days);

        $pageViews = DB::table('analytics_events')
            ->where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->count();

        $uniqueVisitors = DB::table('analytics_events')
            ->where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $topPages = DB::table('analytics_events')
            ->where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->whereNotNull('page')
            ->selectRaw('page, COUNT(*) as views')
            ->groupBy('page')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $dailyViews = DB::table('analytics_events')
            ->where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $eventCounts = DB::table('analytics_events')
            ->where('created_at', '>=', $since)
            ->whereNotIn('event', ['page_view'])
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        return response()->json([
            'period_days'    => $days,
            'page_views'     => $pageViews,
            'unique_visitors'=> $uniqueVisitors,
            'top_pages'      => $topPages,
            'daily_views'    => $dailyViews,
            'event_counts'   => $eventCounts,
        ]);
    }
}
