<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bible_studies')) return;

        Schema::create('bible_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('scripture_reference')->nullable(); // e.g. "John 3:16-17"
            $table->string('bible_version')->default('NIV');
            $table->boolean('is_open')->default(true); // open for new members
            $table->unsignedInteger('members_count')->default(0);
            $table->timestamps();
        });

        Schema::create('bible_study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // facilitator
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('scripture_reference')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedInteger('discussion_count')->default(0);
            $table->timestamps();
        });

        Schema::create('bible_study_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->timestamps();
            $table->unique(['bible_study_id', 'user_id']);
        });

        Schema::create('bible_study_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('bible_study_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_notes');
        Schema::dropIfExists('bible_study_members');
        Schema::dropIfExists('bible_study_sessions');
        Schema::dropIfExists('bible_studies');
    }
};
