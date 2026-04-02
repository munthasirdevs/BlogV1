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
        Schema::create('post_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('percentage')->default(0); // 0-100
            $table->unsignedInteger('time_spent')->default(0); // Time in seconds
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            // Unique constraint - one progress per user per post
            $table->unique(['post_id', 'user_id'], 'unique_user_post_progress');

            // Indexes
            $table->index('user_id');
            $table->index('post_id');
            $table->index('percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reading_progress');
    }
};
