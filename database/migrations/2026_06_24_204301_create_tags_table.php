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
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->integer('usage_count')->default(0);
            $table->decimal('trending_score', 5, 2)->default(0.00);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->enum('status', ['active', 'hidden'])->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('usage_count');
            $table->index('trending_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
