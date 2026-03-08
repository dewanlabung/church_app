<?php

namespace Plugins\Gdpr\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GdprController extends Controller
{
    /**
     * Export all user data as a downloadable JSON archive.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $data = [
            'profile'            => DB::table('users')->where('id', $user->id)->first(),
            'posts'              => DB::table('social_posts')->where('user_id', $user->id)->whereNull('deleted_at')->get(),
            'comments'           => DB::table('social_comments')->where('user_id', $user->id)->whereNull('deleted_at')->get(),
            'reactions'          => DB::table('social_reactions')->where('user_id', $user->id)->get(),
            'prayer_requests'    => DB::table('prayer_requests')->where('user_id', $user->id)->get(),
            'event_attendance'   => DB::table('event_attendees')->where('user_id', $user->id)->get(),
            'community_memberships' => DB::table('community_members')->where('user_id', $user->id)->get(),
            'notifications'      => DB::table('notifications_log')->where('user_id', $user->id)->limit(200)->get(),
        ];

        // Remove sensitive fields
        unset($data['profile']->password, $data['profile']->remember_token);

        $json     = json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'my-data-' . now()->format('Y-m-d') . '.json';

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Request account deletion (queues for processing).
     */
    public function requestDeletion(Request $request)
    {
        $user = $request->user();

        $token = Str::random(40);

        DB::table('gdpr_deletion_requests')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'token'      => $token,
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'ok'      => true,
            'message' => 'Your deletion request has been queued. Your account will be permanently deleted within 30 days.',
        ]);
    }

    /**
     * Cancel a pending deletion request.
     */
    public function cancelDeletion(Request $request)
    {
        DB::table('gdpr_deletion_requests')
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->delete();

        return response()->json(['ok' => true, 'message' => 'Deletion request cancelled.']);
    }

    /**
     * Admin: list pending deletion requests.
     */
    public function pendingDeletions(Request $request)
    {
        $pending = DB::table('gdpr_deletion_requests as r')
            ->join('users as u', 'r.user_id', '=', 'u.id')
            ->where('r.status', 'pending')
            ->select('r.id', 'r.user_id', 'r.created_at as requested_at', 'u.name', 'u.email')
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json(['data' => $pending]);
    }

    /**
     * Admin: process (execute) a deletion request.
     */
    public function processDeletion(Request $request, int $id)
    {
        $req = DB::table('gdpr_deletion_requests')->find($id);
        abort_if(!$req, 404);

        $userId = $req->user_id;

        // Soft delete or anonymize the user
        DB::table('users')->where('id', $userId)->update([
            'name'             => '[Deleted User]',
            'email'            => "deleted_{$userId}@removed.local",
            'password'         => '',
            'avatar'           => null,
            'bio'              => null,
            'phone'            => null,
            'deleted_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Anonymize posts
        DB::table('social_posts')->where('user_id', $userId)->update(['deleted_at' => now()]);

        // Mark request as processed
        DB::table('gdpr_deletion_requests')->where('id', $id)->update([
            'status'       => 'processed',
            'processed_at' => now(),
            'updated_at'   => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
