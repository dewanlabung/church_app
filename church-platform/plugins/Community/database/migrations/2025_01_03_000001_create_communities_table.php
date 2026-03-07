<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('church_id')->nullable(); // no DB FK — church_pages table not yet created at this point
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('about')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('type', ['public', 'private', 'hidden'])->default('public');
            $table->string('category', 50)->nullable();
            $table->json('rules')->nullable();
            $table->string('invite_token')->nullable()->unique();
            $table->unsignedBigInteger('members_count')->default(0);
            $table->unsignedBigInteger('posts_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['active', 'pending', 'banned'])->default('active');
            $table->timestamps();
            $table->unique(['community_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_members');
        Schema::dropIfExists('communities');
    }
};
