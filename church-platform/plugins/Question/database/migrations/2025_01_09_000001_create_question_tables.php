<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('best_answer_id')->nullable(); // set after resolution
            $table->unsignedInteger('answer_count')->default(0);
            $table->timestamps();
        });

        Schema::create('question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->integer('vote_score')->default(0); // upvotes - downvotes
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('question_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('question_answers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value')->default(1); // 1 = upvote, -1 = downvote
            $table->timestamps();
            $table->unique(['answer_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_votes');
        Schema::dropIfExists('question_answers');
        Schema::dropIfExists('questions');
    }
};
