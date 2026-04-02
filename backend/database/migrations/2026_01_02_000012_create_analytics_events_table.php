<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the analytics_events table for tracking user interactions
     * and application events for analytics purposes.
     */
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // e.g., 'page_view', 'post_view', 'click', 'search'
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 64)->nullable();
            $table->json('metadata')->nullable(); // Flexible event-specific data
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for efficient analytics queries
            $table->index('event_type');
            $table->index('user_id');
            $table->index('post_id');
            $table->index('session_id');
            $table->index('occurred_at');
            $table->index(['event_type', 'occurred_at']);
            $table->index(['post_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
