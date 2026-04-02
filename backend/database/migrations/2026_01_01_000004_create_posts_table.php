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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('reading_time')->default(5); // Minutes
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->json('custom_fields')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['status', 'published_at']);
            $table->index('slug');
            $table->index('user_id');
            $table->index('category_id');
            $table->index('is_featured');
            $table->index('published_at');
            $table->index('created_at');
            $table->index('views_count');
            $table->index('likes_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
