<?php

namespace Plugins\Search\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Unified search using MySQL FULLTEXT indexes.
 * Replaces Laravel Scout + MeiliSearch (not available on shared hosting).
 *
 * Endpoint: GET /api/v1/search?q=<query>&type=all|posts|users|communities|churches&page=1
 */
class SearchController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $q    = trim($request->string('q'));
        $type = $request->string('type', 'all');
        $page = max(1, (int) $request->integer('page', 1));

        if (strlen($q) < 2) {
            return response()->json(['error' => 'Query must be at least 2 characters.'], 422);
        }

        // Sanitize for BOOLEAN MODE: strip reserved chars, wrap each word with +
        $ft = $this->buildFulltextQuery($q);

        $results = [];

        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = $this->searchPosts($ft, $q, $page);
        }

        if ($type === 'all' || $type === 'users') {
            $results['users'] = $this->searchUsers($ft, $q, $page);
        }

        if ($type === 'all' || $type === 'communities') {
            $results['communities'] = $this->searchCommunities($ft, $q, $page);
        }

        if ($type === 'all' || $type === 'churches') {
            $results['churches'] = $this->searchChurches($ft, $q, $page);
        }

        return response()->json($results);
    }

    // ── Search methods ────────────────────────────────────────────────────────

    private function searchPosts(string $ft, string $q, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::table('social_posts as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->select([
                'p.id', 'p.type', 'p.body', 'p.created_at',
                'u.id as user_id', 'u.name as user_name', 'u.avatar',
            ])
            ->selectRaw('MATCH(p.body) AGAINST(? IN BOOLEAN MODE) AS relevance', [$ft])
            ->whereRaw("MATCH(p.body) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->where('p.privacy', 'public')
            ->orderByDesc('relevance')
            ->limit(self::PER_PAGE)
            ->offset($offset)
            ->get();

        $total = DB::table('social_posts')
            ->whereRaw("MATCH(body) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->where('privacy', 'public')
            ->count();

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => self::PER_PAGE,
            'last_page'  => (int) ceil($total / self::PER_PAGE),
        ];
    }

    private function searchUsers(string $ft, string $q, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::table('users')
            ->select(['id', 'name', 'avatar', 'user_type', 'created_at'])
            ->selectRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE) AS relevance', [$ft])
            ->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->orderByDesc('relevance')
            ->limit(self::PER_PAGE)
            ->offset($offset)
            ->get();

        $total = DB::table('users')
            ->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->count();

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => self::PER_PAGE,
            'last_page' => (int) ceil($total / self::PER_PAGE),
        ];
    }

    private function searchCommunities(string $ft, string $q, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::table('communities')
            ->select(['id', 'name', 'about', 'type', 'member_count', 'created_at'])
            ->selectRaw('MATCH(name, about) AGAINST(? IN BOOLEAN MODE) AS relevance', [$ft])
            ->whereRaw("MATCH(name, about) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->where('type', '!=', 'hidden')
            ->orderByDesc('relevance')
            ->limit(self::PER_PAGE)
            ->offset($offset)
            ->get();

        $total = DB::table('communities')
            ->whereRaw("MATCH(name, about) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->where('type', '!=', 'hidden')
            ->count();

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => self::PER_PAGE,
            'last_page' => (int) ceil($total / self::PER_PAGE),
        ];
    }

    private function searchChurches(string $ft, string $q, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::table('church_pages')
            ->select(['id', 'name', 'about', 'slug', 'logo', 'denomination', 'created_at'])
            ->selectRaw('MATCH(name, about) AGAINST(? IN BOOLEAN MODE) AS relevance', [$ft])
            ->whereRaw("MATCH(name, about) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->orderByDesc('relevance')
            ->limit(self::PER_PAGE)
            ->offset($offset)
            ->get();

        $total = DB::table('church_pages')
            ->whereRaw("MATCH(name, about) AGAINST(? IN BOOLEAN MODE)", [$ft])
            ->count();

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => self::PER_PAGE,
            'last_page' => (int) ceil($total / self::PER_PAGE),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a safe MySQL FULLTEXT BOOLEAN MODE query string.
     * Wraps each word in double-quotes for phrase safety.
     */
    private function buildFulltextQuery(string $q): string
    {
        // Remove MySQL FULLTEXT operators that could cause syntax errors
        $cleaned = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $q);
        $words   = array_filter(explode(' ', $cleaned), fn($w) => strlen($w) >= 2);

        if (empty($words)) {
            return '"' . addslashes($q) . '"';
        }

        return implode(' ', array_map(fn($w) => '+' . addslashes($w) . '*', $words));
    }
}
