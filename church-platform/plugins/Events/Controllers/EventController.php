<?php

namespace Plugins\Events\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'upcoming'); // upcoming|past|mine
        $query = DB::table('events as e')
            ->join('users as u', 'e.user_id', '=', 'u.id')
            ->select('e.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->orderBy('e.starts_at');

        if ($filter === 'upcoming') {
            $query->where('e.starts_at', '>=', now());
        } elseif ($filter === 'past') {
            $query->where('e.starts_at', '<', now());
        } elseif ($filter === 'mine') {
            $query->where('e.user_id', $request->user()->id);
        }

        if ($communityId = $request->input('community_id')) {
            $query->where('e.community_id', $communityId);
        }
        if ($churchPageId = $request->input('church_page_id')) {
            $query->where('e.church_page_id', $churchPageId);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'location'       => 'nullable|string|max:500',
            'online_url'     => 'nullable|url',
            'starts_at'      => 'required|date|after:now',
            'ends_at'        => 'nullable|date|after:starts_at',
            'is_recurring'   => 'boolean',
            'recurrence_rule'=> 'nullable|string',
            'cover_image'    => 'nullable|string',
            'privacy'        => 'nullable|in:public,community,invite_only',
            'community_id'   => 'nullable|exists:communities,id',
            'church_page_id' => 'nullable|exists:church_pages,id',
        ]);

        $id = DB::table('events')->insertGetId([
            ...$validated,
            'user_id'    => $request->user()->id,
            'privacy'    => $validated['privacy'] ?? 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('events')->find($id), 201);
    }

    public function show(int $id)
    {
        $event = DB::table('events as e')
            ->join('users as u', 'e.user_id', '=', 'u.id')
            ->where('e.id', $id)
            ->select('e.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->first();

        abort_if(!$event, 404);
        return response()->json($event);
    }

    public function update(Request $request, int $id)
    {
        $event = DB::table('events')->find($id);
        abort_if(!$event, 404);

        if ($event->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:500',
            'online_url'  => 'nullable|url',
            'starts_at'   => 'sometimes|date',
            'ends_at'     => 'nullable|date',
            'privacy'     => 'nullable|in:public,community,invite_only',
        ]);

        DB::table('events')->where('id', $id)->update([...$validated, 'updated_at' => now()]);

        return response()->json(DB::table('events')->find($id));
    }

    public function destroy(Request $request, int $id)
    {
        $event = DB::table('events')->find($id);
        abort_if(!$event, 404);

        if ($event->user_id !== $request->user()->id && !$request->user()->hasRole('super_admin')) {
            abort(403);
        }

        DB::table('event_attendees')->where('event_id', $id)->delete();
        DB::table('event_reminders')->where('event_id', $id)->delete();
        DB::table('events')->where('id', $id)->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function rsvp(Request $request, int $id)
    {
        $status = $request->validate(['status' => 'required|in:going,interested,not_going'])['status'];
        $userId = $request->user()->id;

        $existing = DB::table('event_attendees')
            ->where('event_id', $id)->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->status === $status) {
                // Cancel RSVP
                DB::table('event_attendees')->where('id', $existing->id)->delete();
                $this->updateCounts($id);
                return response()->json(['rsvp' => null]);
            }
            DB::table('event_attendees')->where('id', $existing->id)->update([
                'status' => $status, 'updated_at' => now(),
            ]);
        } else {
            DB::table('event_attendees')->insert([
                'event_id' => $id, 'user_id' => $userId,
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->updateCounts($id);
        return response()->json(['rsvp' => $status]);
    }

    public function attendees(int $id)
    {
        $attendees = DB::table('event_attendees as a')
            ->join('users as u', 'a.user_id', '=', 'u.id')
            ->where('a.event_id', $id)
            ->select('u.id', 'u.name', 'u.avatar', 'a.status')
            ->get()
            ->groupBy('status');

        return response()->json($attendees);
    }

    private function updateCounts(int $eventId): void
    {
        $going = DB::table('event_attendees')
            ->where('event_id', $eventId)->where('status', 'going')->count();
        $interested = DB::table('event_attendees')
            ->where('event_id', $eventId)->where('status', 'interested')->count();

        DB::table('events')->where('id', $eventId)->update([
            'going_count' => $going, 'interested_count' => $interested,
        ]);
    }
}
