<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('level', 20)->default('info'); // info, warn, error, critical
            $table->string('channel', 50); // app, security, ai, queue, billing
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();

            $table->index('tenant_id');
            $table->index('level');
            $table->index('channel');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
