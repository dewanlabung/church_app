<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_verses', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->nullable(); // null = pool (no specific date)
            $table->string('reference', 100);           // e.g. "John 3:16"
            $table->text('text');
            $table->string('version', 20)->default('KJV');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_verses');
    }
};
