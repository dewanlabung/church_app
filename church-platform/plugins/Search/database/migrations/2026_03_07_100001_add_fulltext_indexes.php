<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add MySQL FULLTEXT indexes for site-wide search.
 * These replace the MeiliSearch Scout driver (not available on shared hosting).
 */
return new class extends Migration
{
    public function up(): void
    {
        // social_posts.body
        if (!$this->indexExists('social_posts', 'ft_posts_body')) {
            DB::statement('ALTER TABLE social_posts ADD FULLTEXT INDEX ft_posts_body (body)');
        }

        // communities — name + about
        if (!$this->indexExists('communities', 'ft_communities_search')) {
            DB::statement('ALTER TABLE communities ADD FULLTEXT INDEX ft_communities_search (name, about)');
        }

        // church_pages — name + about
        if (!$this->indexExists('church_pages', 'ft_church_pages_search')) {
            DB::statement('ALTER TABLE church_pages ADD FULLTEXT INDEX ft_church_pages_search (name, about)');
        }

        // users — name (searchable display name)
        if (!$this->indexExists('users', 'ft_users_name')) {
            DB::statement('ALTER TABLE users ADD FULLTEXT INDEX ft_users_name (name)');
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        $result = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        );
        return ($result[0]->cnt ?? 0) > 0;
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE social_posts DROP INDEX ft_posts_body');
        DB::statement('ALTER TABLE communities DROP INDEX ft_communities_search');
        DB::statement('ALTER TABLE church_pages DROP INDEX ft_church_pages_search');
        DB::statement('ALTER TABLE users DROP INDEX ft_users_name');
    }
};
