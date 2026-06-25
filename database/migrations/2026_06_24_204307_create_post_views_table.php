<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->string('country', 2)->nullable();
            $table->enum('device_type', ['desktop', 'tablet', 'mobile'])->nullable();
            $table->timestamp('visited_at');

            $table->index('post_id');
            $table->index('visited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
