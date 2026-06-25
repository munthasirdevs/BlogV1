<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('full_description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $table->enum('status', ['draft', 'published', 'archived', 'hidden'])->default('published');
            $table->integer('article_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('parent_id');
            $table->index('status');
            $table->index('featured');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
