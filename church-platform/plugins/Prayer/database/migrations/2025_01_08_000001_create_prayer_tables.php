<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prayer_requests')) return;

        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_answered')->default(false);
            $table->text('answered_note')->nullable();
            $table->unsignedInteger('support_count')->default(0); // "I'm praying for you" count
            $table->timestamps();
        });

        Schema::create('prayer_request_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prayer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['prayer_request_id', 'user_id']);
        });

        Schema::create('prayer_private_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prayer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counsellor_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_private_responses');
        Schema::dropIfExists('prayer_request_supports');
        Schema::dropIfExists('prayer_requests');
    }
};
