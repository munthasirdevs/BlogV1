<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the analytics_daily_stats table for storing daily aggregated analytics.
     * This provides quick access to dashboard metrics without querying raw events.
     */
    public function up(): void
    {
        Schema::create('analytics_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date')->unique(); // The date this stat represents
            
            // Traffic metrics
            $table->unsignedBigInteger('total_page_views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('new_visitors')->default(0);
            $table->unsignedBigInteger('returning_visitors')->default(0);
            $table->unsignedBigInteger('total_sessions')->default(0);
            
            // Engagement metrics
            $table->unsignedInteger('avg_session_duration')->default(0); // seconds
            $table->unsignedInteger('avg_pages_per_session')->default(0);
            $table->unsignedBigInteger('bounce_count')->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0); // percentage
            
            // Event counts by type (stored as JSON for flexibility)
            $table->json('event_counts')->nullable();
            // Format: {"page_view": 1000, "post_view": 500, "search": 50}
            
            // Traffic sources
            $table->json('traffic_sources')->nullable();
            // Format: {"direct": 400, "organic": 300, "social": 200, "referral": 100}
            
            // Top referrers (top 10 domains)
            $table->json('top_referrers')->nullable();
            // Format: [{"domain": "google.com", "count": 100}, ...]
            
            // Device breakdown
            $table->json('device_breakdown')->nullable();
            // Format: {"desktop": 600, "mobile": 300, "tablet": 100}
            
            // Browser breakdown
            $table->json('browser_breakdown')->nullable();
            // Format: {"Chrome": 500, "Firefox": 200, "Safari": 300}
            
            // OS breakdown
            $table->json('os_breakdown')->nullable();
            // Format: {"Windows": 400, "macOS": 200, "Android": 200, "iOS": 200}
            
            // Geographic (top 10 countries)
            $table->json('top_countries')->nullable();
            // Format: [{"country": "US", "count": 300}, ...]
            
            // Peak concurrent users
            $table->unsignedInteger('peak_concurrent_users')->default(0);
            
            $table->timestamps();

            // Index for date range queries
            $table->index('stat_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_stats');
    }
};
