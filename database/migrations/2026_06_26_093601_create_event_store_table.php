<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_store', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('event_type', 100);
            $table->json('payload');
            $table->string('source', 100)->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['tenant_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_store');
    }
};
