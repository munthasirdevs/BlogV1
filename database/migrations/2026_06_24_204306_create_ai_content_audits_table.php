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
        Schema::create('ai_content_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->decimal('readability_score', 5, 2)->default(0);
            $table->decimal('seo_score', 5, 2)->default(0);
            $table->decimal('keyword_density', 5, 2)->default(0);
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_audits');
    }
};
