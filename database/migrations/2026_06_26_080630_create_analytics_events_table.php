<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_type', 50); // page_view, post_view, search, click, share, comment
            $table->string('entity_type', 50)->nullable(); // post, category, tag, page
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, tablet, mobile
            $table->string('url', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('created_at')->index();

            $table->index('event_type');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
