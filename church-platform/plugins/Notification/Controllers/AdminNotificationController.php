<?php

namespace Plugins\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Notification\Services\NotificationService;

class AdminNotificationController extends Controller
{
    public function __construct(protected NotificationService $notif) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $offset  = ($page - 1) * $perPage;

        $total = DB::table('notifications_log')->count();
        $items = DB::table('notifications_log as n')
            ->leftJoin('users as u', 'n.user_id', '=', 'u.id')
            ->select('n.*', 'u.name as recipient_name', 'u.email as recipient_email')
            ->orderByDesc('n.id')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'message'  => 'required|string|max:500',
            'url'      => 'nullable|string|url',
            'roles'    => 'nullable|array',
        ]);

        $query = User::query();
        if (!empty($validated['roles'])) {
            $query->whereIn('user_type', $validated['roles']);
        }

        $sent = 0;
        $query->chunk(200, function ($users) use ($validated, &$sent) {
            foreach ($users as $user) {
                $this->notif->send($user, 'admin_announcement', $validated['message'], [
                    'url' => $validated['url'] ?? config('app.url'),
                ]);
                $sent++;
            }
        });

        return response()->json(['sent' => $sent]);
    }

    public function settings(Request $request)
    {
        $settings = DB::table('settings')
            ->whereIn('key', ['notif_email_enabled', 'notif_push_enabled', 'notif_inapp_enabled', 'notif_throttle_per_hour'])
            ->pluck('value', 'key');

        return response()->json([
            'email_enabled'       => (bool) ($settings['notif_email_enabled'] ?? true),
            'push_enabled'        => (bool) ($settings['notif_push_enabled'] ?? true),
            'inapp_enabled'       => (bool) ($settings['notif_inapp_enabled'] ?? true),
            'throttle_per_hour'   => (int)  ($settings['notif_throttle_per_hour'] ?? 50),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'email_enabled'     => 'boolean',
            'push_enabled'      => 'boolean',
            'inapp_enabled'     => 'boolean',
            'throttle_per_hour' => 'integer|min:1|max:500',
        ]);

        $map = [
            'email_enabled'     => 'notif_email_enabled',
            'push_enabled'      => 'notif_push_enabled',
            'inapp_enabled'     => 'notif_inapp_enabled',
            'throttle_per_hour' => 'notif_throttle_per_hour',
        ];

        foreach ($validated as $field => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $map[$field]],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['ok' => true]);
    }
}
