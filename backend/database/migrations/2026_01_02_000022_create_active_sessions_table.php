<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the active_sessions table for tracking real-time active users.
     * This table is cleaned up automatically and used for real-time analytics.
     */
    public function up(): void
    {
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('current_url')->nullable();
            $table->string('current_page_title')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->unsignedInteger('page_views')->default(1);
            $table->string('visitor_fingerprint', 64)->nullable(); // Hash of IP + user agent
            $table->boolean('is_new_visitor')->default(false);
            
            // Geographic data
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            
            // Device info
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            
            $table->timestamps();

            // Indexes for efficient real-time queries
            $table->index('session_id');
            $table->index('last_seen_at');
            $table->index('visitor_fingerprint');
            $table->index(['last_seen_at', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_sessions');
    }
};
