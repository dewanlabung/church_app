<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Follows
        Schema::create('social_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['follower_id', 'following_id']);
        });

        // Posts
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('text'); // text|photo|video|feeling|poll|link|document|blessing|prayer_request|ask_question|bible_verse|event_share
            $table->text('body')->nullable();
            $table->enum('privacy', ['public', 'community', 'friends', 'only_me'])->default('public');
            $table->string('feeling', 100)->nullable();
            $table->string('link_url')->nullable();
            $table->json('link_meta')->nullable();
            $table->json('poll_options')->nullable();
            $table->timestamp('poll_ends_at')->nullable();
            $table->string('bible_reference', 100)->nullable();
            $table->text('bible_text')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('community_id')->nullable();   // FK to communities (no constraint — avoids migration order dependency)
            $table->unsignedBigInteger('church_id')->nullable();       // FK to church_pages (no constraint — avoids migration order dependency)
            $table->foreignId('parent_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['community_id', 'created_at']);
            $table->index(['church_id', 'created_at']);
            $table->index(['privacy', 'created_at']);
        });

        // Feeds (fan-out on write — pre-computed per user)
        Schema::create('social_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // owner of this feed row
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index(['user_id', 'posted_at']);
            $table->unique(['user_id', 'post_id']);
        });

        // Reactions (polymorphic — posts and comments)
        Schema::create('social_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactable'); // reactable_type, reactable_id
            $table->string('type', 20); // like|bless|amen|pray|love
            $table->timestamps();
            $table->unique(['user_id', 'reactable_type', 'reactable_id']);
        });

        // Comments
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('social_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'parent_id', 'created_at']);
        });

        // Saves / Bookmarks
        Schema::create('social_post_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'post_id']);
        });

        // Hashtags
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 100)->unique();
            $table->unsignedBigInteger('posts_count')->default(0);
            $table->timestamps();
        });

        Schema::create('post_hashtags', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('hashtag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_hashtags');
        Schema::dropIfExists('hashtags');
        Schema::dropIfExists('social_post_saves');
        Schema::dropIfExists('social_comments');
        Schema::dropIfExists('social_reactions');
        Schema::dropIfExists('social_feeds');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_follows');
    }
};
