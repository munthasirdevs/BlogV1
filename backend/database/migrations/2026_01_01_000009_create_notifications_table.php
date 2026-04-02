<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index(['notifiable_id', 'notifiable_type']);
            $table->index('user_id');
            $table->index('read_at');
            $table->index('created_at');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
