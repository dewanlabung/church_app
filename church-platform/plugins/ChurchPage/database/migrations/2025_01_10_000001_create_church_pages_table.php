<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // church admin owner
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('about')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('denomination')->nullable();
            $table->year('founded_year')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('social_links')->nullable(); // {facebook, youtube, instagram}
            $table->json('service_schedule')->nullable(); // [{day, time, name}]
            $table->json('seo_meta')->nullable(); // {title, description, og_image}
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('members_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
        });

        Schema::create('church_page_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['follow', 'member'])->default('follow');
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->timestamps();
            $table->unique(['church_page_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_page_members');
        Schema::dropIfExists('church_pages');
    }
};
