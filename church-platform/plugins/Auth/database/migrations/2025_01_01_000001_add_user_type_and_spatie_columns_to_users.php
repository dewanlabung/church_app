<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', [
                    'super_admin',
                    'church_admin',
                    'counsellor',
                    'musician',
                    'general_user',
                ])->default('general_user')->after('email');
            }

            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('user_type');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('bio');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('avatar');
            }
            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider', 30)->nullable()->after('custom_fields');
            }
            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }
            if (! Schema::hasColumn('users', 'email_verification_token')) {
                $table->string('email_verification_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type', 'bio', 'phone', 'avatar',
                'custom_fields', 'provider', 'provider_id',
                'email_verification_token',
            ]);
        });
    }
};
