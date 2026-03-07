<?php

namespace Plugins\BibleStudy\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BibleStudyController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('bible_studies as bs')
            ->join('users as u', 'bs.user_id', '=', 'u.id')
            ->select('bs.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->orderByDesc('bs.id');

        if ($communityId = $request->input('community_id')) {
            $query->where('bs.community_id', $communityId);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'scripture_reference'=> 'nullable|string|max:100',
            'bible_version'      => 'nullable|string|max:10',
            'community_id'       => 'nullable|exists:communities,id',
        ]);

        $id = DB::table('bible_studies')->insertGetId([
            ...$validated,
            'user_id'    => $request->user()->id,
            'is_open'    => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-join creator as owner
        DB::table('bible_study_members')->insert([
            'bible_study_id' => $id,
            'user_id'        => $request->user()->id,
            'role'           => 'owner',
            'created_at'     => now(),
        ]);

        DB::table('bible_studies')->where('id', $id)->update(['members_count' => 1]);

        return response()->json(DB::table('bible_studies')->find($id), 201);
    }

    public function show(int $id)
    {
        $study = DB::table('bible_studies as bs')
            ->join('users as u', 'bs.user_id', '=', 'u.id')
            ->where('bs.id', $id)
            ->select('bs.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->first();

        abort_if(!$study, 404);

        $sessions = DB::table('bible_study_sessions')
            ->where('bible_study_id', $id)
            ->orderByDesc('scheduled_at')
            ->get();

        return response()->json(['study' => $study, 'sessions' => $sessions]);
    }

    public function join(Request $request, int $id)
    {
        $study = DB::table('bible_studies')->find($id);
        abort_if(!$study || !$study->is_open, 404);

        $userId = $request->user()->id;
        $exists = DB::table('bible_study_members')
            ->where('bible_study_id', $id)->where('user_id', $userId)->exists();

        if (!$exists) {
            DB::table('bible_study_members')->insert([
                'bible_study_id' => $id, 'user_id' => $userId,
                'role' => 'member', 'created_at' => now(),
            ]);
            DB::table('bible_studies')->where('id', $id)->increment('members_count');
        }

        return response()->json(['joined' => true]);
    }

    public function addSession(Request $request, int $studyId)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'notes'               => 'nullable|string',
            'scripture_reference' => 'nullable|string|max:100',
            'scheduled_at'        => 'nullable|date',
        ]);

        $id = DB::table('bible_study_sessions')->insertGetId([
            ...$validated,
            'bible_study_id' => $studyId,
            'user_id'        => $request->user()->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(DB::table('bible_study_sessions')->find($id), 201);
    }

    public function saveNote(Request $request, int $sessionId)
    {
        $validated = $request->validate([
            'note'       => 'required|string|max:10000',
            'is_private' => 'boolean',
        ]);

        DB::table('bible_study_notes')->updateOrInsert(
            ['session_id' => $sessionId, 'user_id' => $request->user()->id],
            [...$validated, 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['saved' => true]);
    }
}
