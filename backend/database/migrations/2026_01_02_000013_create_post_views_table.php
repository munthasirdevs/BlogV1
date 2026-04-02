<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the post_views table for tracking individual post views
     * with detailed visitor information for analytics.
     */
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('referrer')->nullable();
            $table->unsignedInteger('time_on_page')->nullable(); // Seconds spent on page
            $table->boolean('is_unique')->default(true); // First view from this session/IP
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('post_id');
            $table->index('user_id');
            $table->index('session_id');
            $table->index('viewed_at');
            $table->index(['post_id', 'viewed_at']);
            $table->index(['post_id', 'is_unique']);
            
            // Composite index for unique view tracking
            $table->index(['post_id', 'ip_address', 'session_id', 'viewed_at']);
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
