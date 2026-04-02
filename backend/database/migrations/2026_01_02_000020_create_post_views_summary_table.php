<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the post_views_summary table for storing aggregated post view statistics.
     * This table is populated by the AggregatePostViews scheduled job.
     */
    public function up(): void
    {
        Schema::create('post_views_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->date('view_date'); // The date this summary represents
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedBigInteger('unique_views')->default(0);
            $table->unsignedBigInteger('new_visitors')->default(0);
            $table->unsignedBigInteger('returning_visitors')->default(0);
            
            // Referrer breakdown (stored as JSON for flexibility)
            $table->json('referrer_breakdown')->nullable();
            // Format: {"direct": 100, "organic": 50, "social": 30, "referral": 20}
            
            // Device breakdown
            $table->json('device_breakdown')->nullable();
            // Format: {"desktop": 120, "mobile": 60, "tablet": 20}
            
            // Geographic breakdown (top countries)
            $table->json('country_breakdown')->nullable();
            // Format: {"US": 80, "UK": 40, "CA": 30}
            
            // Average time on page (in seconds)
            $table->unsignedInteger('avg_time_on_page')->default(0);
            
            // Bounce count (views with no further interaction)
            $table->unsignedBigInteger('bounce_count')->default(0);
            
            $table->timestamps();

            // Unique constraint to prevent duplicate summaries for same post/date
            $table->unique(['post_id', 'view_date']);
            
            // Indexes for efficient queries
            $table->index('view_date');
            $table->index(['post_id', 'view_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_views_summary');
    }
};
