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
        DB::statement('ALTER TABLE social_posts ADD FULLTEXT INDEX ft_posts_body (body)');

        // communities — name + about
        DB::statement('ALTER TABLE communities ADD FULLTEXT INDEX ft_communities_search (name, about)');

        // church_pages — name + about
        DB::statement('ALTER TABLE church_pages ADD FULLTEXT INDEX ft_church_pages_search (name, about)');

        // users — name (searchable display name)
        DB::statement('ALTER TABLE users ADD FULLTEXT INDEX ft_users_name (name)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE social_posts DROP INDEX ft_posts_body');
        DB::statement('ALTER TABLE communities DROP INDEX ft_communities_search');
        DB::statement('ALTER TABLE church_pages DROP INDEX ft_church_pages_search');
        DB::statement('ALTER TABLE users DROP INDEX ft_users_name');
    }
};
