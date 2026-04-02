<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the email_trackings table for tracking email opens, clicks,
     * bounces, and complaints for newsletter subscriptions.
     */
    public function up(): void
    {
        Schema::create('email_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_campaign_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email_type'); // confirmation, digest, newsletter, new_post
            $table->string('subject')->nullable();
            $table->string('message_id')->nullable(); // Provider's message ID
            $table->timestamp('sent_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->timestamp('clicked_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_type')->nullable(); // hard, soft
            $table->string('bounce_reason')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->string('complaint_type')->nullable(); // abuse, spam
            $table->boolean('is_unsubscribed')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();

            // Indexes for efficient queries
            $table->index('subscription_id');
            $table->index('email_campaign_id');
            $table->index('email_type');
            $table->index('sent_at');
            $table->index('opened_at');
            $table->index('clicked_at');
            $table->index('bounced_at');
            $table->index('complained_at');
            
            // Composite indexes for common queries
            $table->index(['subscription_id', 'email_type']);
            $table->index(['email_type', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_trackings');
    }
};
