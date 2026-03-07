<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('church_page_id')->nullable()->constrained('church_pages')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable(); // RRULE string
            $table->string('cover_image')->nullable();
            $table->enum('privacy', ['public', 'community', 'invite_only'])->default('public');
            $table->unsignedInteger('going_count')->default(0);
            $table->unsignedInteger('interested_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
            $table->index('community_id');
        });

        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['going', 'interested', 'not_going'])->default('interested');
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('minutes_before')->default(60); // 60 or 1440
            $table->boolean('sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reminders');
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('events');
    }
};
