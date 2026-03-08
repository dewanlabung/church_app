<?php

namespace Plugins\Notification\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Notification\Services\NotificationService;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->user()->id;

        $stored = DB::table('user_notification_prefs')
            ->where('user_id', $userId)
            ->pluck('enabled', 'channel_type')
            ->toArray();

        // Build full preference map from registered types
        $prefs = [];
        foreach (NotificationService::TYPES as $type => $meta) {
            $prefs[$type] = [];
            foreach ($meta['channels'] as $channel) {
                $key = "{$type}_{$channel}";
                $prefs[$type][$channel] = $stored[$key] ?? true; // default enabled
            }
        }

        return response()->json(['preferences' => $prefs, 'types' => NotificationService::TYPES]);
    }

    public function update(Request $request)
    {
        $userId    = $request->user()->id;
        $prefs     = $request->input('preferences', []);
        $now       = now();
        $upserts   = [];

        foreach (NotificationService::TYPES as $type => $meta) {
            foreach ($meta['channels'] as $channel) {
                $key     = "{$type}_{$channel}";
                $enabled = (bool) ($prefs[$type][$channel] ?? true);

                $upserts[] = [
                    'user_id'      => $userId,
                    'channel_type' => $key,
                    'enabled'      => $enabled,
                    'updated_at'   => $now,
                    'created_at'   => $now,
                ];
            }
        }

        foreach ($upserts as $row) {
            DB::table('user_notification_prefs')->updateOrInsert(
                ['user_id' => $row['user_id'], 'channel_type' => $row['channel_type']],
                $row
            );
        }

        return response()->json(['ok' => true]);
    }
}
