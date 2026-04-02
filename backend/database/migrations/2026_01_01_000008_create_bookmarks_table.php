<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('collection_name')->default('default'); // For organizing bookmarks
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint per user per post per collection
            $table->unique(['user_id', 'post_id', 'collection_name'], 'unique_bookmark');
            
            // Indexes for efficient queries
            $table->index('user_id');
            $table->index('post_id');
            $table->index('collection_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
