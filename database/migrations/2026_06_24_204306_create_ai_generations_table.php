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
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('model_name');
            $table->text('prompt');
            $table->longText('generated_content')->nullable();
            $table->enum('generation_type', ['article', 'title', 'meta_description', 'keywords', 'summary', 'expansion', 'tags', 'category', 'audit']);
            $table->unsignedInteger('token_usage')->default(0);
            $table->timestamp('created_at');

            $table->index('user_id');
            $table->index('generation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
