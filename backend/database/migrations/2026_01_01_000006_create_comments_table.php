<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->text('content');
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending');
            $table->unsignedTinyInteger('depth')->default(0); // Nesting depth level
            $table->boolean('is_edited')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['post_id', 'status']);
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('depth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
