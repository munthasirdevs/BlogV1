<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the email_campaigns table for managing newsletter campaigns,
     * A/B testing, and bulk email sends.
     */
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Campaign name for internal reference
            $table->string('subject'); // Email subject line
            $table->string('subject_b')->nullable(); // A/B test subject line B
            $table->text('preview_text')->nullable(); // Preview/summary text
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('from_name')->nullable(); // Override from name
            $table->string('from_email')->nullable(); // Override from email
            $table->string('reply_to')->nullable();
            $table->string('template')->default('newsletter'); // Template name
            $table->json('content')->nullable(); // Email content (HTML/text or template variables)
            $table->string('status')->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('complained_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            $table->boolean('is_ab_test')->default(false);
            $table->integer('ab_test_split')->default(50); // Percentage for variant B (10-90)
            $table->integer('ab_test_sample_size')->default(10); // Percentage of list for A/B test
            $table->string('ab_test_winner')->nullable(); // 'a' or 'b'
            $table->timestamp('ab_test_completed_at')->nullable();
            $table->json('segment_filters')->nullable(); // {categories: [], frequency: [], engagement: ''}
            $table->json('metadata')->nullable(); // Additional campaign data
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('created_at');
            $table->index('is_ab_test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
