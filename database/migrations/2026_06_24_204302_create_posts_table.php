<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('content_format', ['html', 'markdown'])->default('html');
            $table->enum('status', ['draft', 'review', 'seo_review', 'approved', 'scheduled', 'published', 'archived'])->default('draft');
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('reading_time')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->decimal('seo_score', 5, 2)->default(0.00);
            $table->decimal('ai_score', 5, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index(['status', 'published_at']);
            $table->index('author_id');
            $table->index('category_id');
            $table->fullText(['title', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
