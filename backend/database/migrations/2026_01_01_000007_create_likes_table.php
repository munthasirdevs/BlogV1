<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('likeable'); // Creates likeable_id and likeable_type
            $table->timestamps();
            
            // Unique constraint to prevent duplicate likes
            $table->unique(['user_id', 'likeable_id', 'likeable_type'], 'unique_like');
            
            // Indexes for efficient queries
            $table->index('user_id');
            $table->index(['likeable_id', 'likeable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
