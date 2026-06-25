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
        Schema::create('content_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->unique('post_id');
            $table->unsignedInteger('daily_views')->default(0);
            $table->unsignedInteger('weekly_views')->default(0);
            $table->unsignedInteger('monthly_views')->default(0);
            $table->decimal('engagement_score', 5, 2)->default(0);
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_metrics');
    }
};
