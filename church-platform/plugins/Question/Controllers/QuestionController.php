<?php

namespace Plugins\Question\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'latest'); // latest|unanswered|popular
        $query = DB::table('questions as q')
            ->join('social_posts as p', 'q.post_id', '=', 'p.id')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('p.privacy', 'public')
            ->select('q.*', 'p.body', 'p.created_at', 'u.name as author_name', 'u.avatar as author_avatar');

        if ($sort === 'unanswered') {
            $query->where('q.answer_count', 0);
        } elseif ($sort === 'popular') {
            $query->orderByDesc('q.answer_count');
        } else {
            $query->orderByDesc('q.id');
        }

        return response()->json($query->paginate(15));
    }

    public function answers(int $questionId)
    {
        $answers = DB::table('question_answers as a')
            ->join('users as u', 'a.user_id', '=', 'u.id')
            ->where('a.question_id', $questionId)
            ->whereNull('a.deleted_at')
            ->select('a.*', 'u.name as author_name', 'u.avatar as author_avatar')
            ->orderByDesc('a.vote_score')
            ->get();

        $bestAnswerId = DB::table('questions')->where('id', $questionId)->value('best_answer_id');

        return response()->json(['answers' => $answers, 'best_answer_id' => $bestAnswerId]);
    }

    public function addAnswer(Request $request, int $questionId)
    {
        $validated = $request->validate(['body' => 'required|string|max:10000']);

        $id = DB::table('question_answers')->insertGetId([
            'question_id' => $questionId,
            'user_id'     => $request->user()->id,
            'body'        => $validated['body'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('questions')->where('id', $questionId)->increment('answer_count');

        return response()->json(DB::table('question_answers')->find($id), 201);
    }

    public function vote(Request $request, int $answerId)
    {
        $value = $request->validate(['value' => 'required|in:-1,1'])['value'];
        $userId = $request->user()->id;

        $answer = DB::table('question_answers')->find($answerId);
        abort_if(!$answer, 404);

        $existing = DB::table('question_votes')
            ->where('answer_id', $answerId)->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->value === (int) $value) {
                // Cancel vote
                DB::table('question_votes')->where('id', $existing->id)->delete();
                DB::table('question_answers')->where('id', $answerId)->decrement('vote_score', $value);
                return response()->json(['voted' => null]);
            }
            // Change vote
            DB::table('question_votes')->where('id', $existing->id)->update(['value' => $value]);
            $delta = (int) $value - $existing->value;
            DB::table('question_answers')->where('id', $answerId)->increment('vote_score', $delta);
        } else {
            DB::table('question_votes')->insert([
                'answer_id' => $answerId, 'user_id' => $userId,
                'value' => $value, 'created_at' => now(),
            ]);
            DB::table('question_answers')->where('id', $answerId)->increment('vote_score', $value);
        }

        return response()->json(['voted' => $value]);
    }

    public function markBestAnswer(Request $request, int $questionId, int $answerId)
    {
        $question = DB::table('questions')->find($questionId);
        abort_if(!$question, 404);

        // Only the question author or super_admin can mark best answer
        $post = DB::table('social_posts')->find($question->post_id);
        if ($post->user_id !== $request->user()->id && !$request->user()->hasRole('super_admin')) {
            abort(403);
        }

        DB::table('questions')->where('id', $questionId)->update(['best_answer_id' => $answerId]);

        return response()->json(['best_answer_id' => $answerId]);
    }
}
