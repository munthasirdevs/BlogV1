<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the media table for storing file uploads including images,
     * documents, and other media assets used throughout the application.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->constrained('users')->onDelete('set null');
            $table->string('filename');
            $table->string('original_filename')->nullable();
            $table->string('path');
            $table->string('disk')->default('public'); // Storage disk name
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('title')->nullable();
            $table->json('dimensions')->nullable(); // {width: int, height: int}
            $table->json('metadata')->nullable(); // Additional EXIF or custom metadata
            $table->string('collection_name')->default('default'); // For grouping media
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('uploader_id');
            $table->index('mime_type');
            $table->index('collection_name');
            $table->index('created_at');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
