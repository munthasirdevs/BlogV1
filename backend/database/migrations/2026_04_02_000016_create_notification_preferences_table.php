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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // e.g., 'new_comment', 'new_like', 'mention', 'post_published'
            $table->json('channels'); // e.g., ['database', 'email', 'broadcast']
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Unique constraint per user per notification type
            $table->unique(['user_id', 'notification_type']);

            // Indexes for efficient queries
            $table->index(['user_id', 'enabled']);
            $table->index('notification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
