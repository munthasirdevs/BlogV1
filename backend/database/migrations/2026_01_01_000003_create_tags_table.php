<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color code
            $table->unsignedInteger('posts_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
