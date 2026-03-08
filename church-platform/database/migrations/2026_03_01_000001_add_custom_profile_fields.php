<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add custom_profile_fields JSON config to settings
        if (!Schema::hasColumn('settings', 'custom_profile_fields')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->json('custom_profile_fields')->nullable()->after('widget_config');
            });
        }

        // Add extra profile fields to users (only if not already present)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'church_name'))          $table->string('church_name')->nullable()->after('phone');
            if (!Schema::hasColumn('users', 'social_id'))            $table->string('social_id')->nullable()->after('church_name');
            if (!Schema::hasColumn('users', 'spiritual_background')) $table->text('spiritual_background')->nullable()->after('social_id');
            if (!Schema::hasColumn('users', 'custom_fields'))        $table->json('custom_fields')->nullable()->after('spiritual_background');
            if (!Schema::hasColumn('users', 'profile_completed'))    $table->boolean('profile_completed')->default(false)->after('custom_fields');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('custom_profile_fields');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['church_name', 'social_id', 'spiritual_background', 'custom_fields', 'profile_completed']);
        });
    }
};
