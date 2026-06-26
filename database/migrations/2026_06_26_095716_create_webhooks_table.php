<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('url', 500);
            $table->json('events')->nullable(); // ['post.created', 'ai.generated', ...]
            $table->string('secret', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('timeout')->default(10);
            $table->unsignedInteger('retry_count')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
