<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the subscriptions table for managing newsletter subscriptions
     * and email notifications preferences.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique(); // For email verification
            $table->timestamp('subscribed_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('preferences')->nullable(); // {new_posts: bool, weekly_digest: bool, etc.}
            $table->string('frequency')->default('instant'); // instant, daily, weekly
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('email');
            $table->index('user_id');
            $table->index('token');
            $table->index('is_confirmed');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
