<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users');
            $table->unsignedInteger('revision_number');
            $table->string('title_snapshot');
            $table->text('excerpt_snapshot')->nullable();
            $table->longText('content_snapshot')->nullable();
            $table->json('seo_snapshot')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_tool_used', 100)->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->timestamp('created_at');

            $table->index(['post_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_revisions');
    }
};
