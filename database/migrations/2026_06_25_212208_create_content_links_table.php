<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_type', 50); // post, category, tag, page
            $table->unsignedBigInteger('source_id');
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->string('link_type', 30)->default('related'); // related, similar, reference
            $table->string('anchor_text', 255)->nullable();
            $table->decimal('weight_score', 5, 2)->default(0.50);
            $table->boolean('ai_generated')->default(false);
            $table->text('context_snippet')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['target_type', 'target_id']);
            $table->index('weight_score');
            $table->index('link_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_links');
    }
};
