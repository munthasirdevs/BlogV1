<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table for tracking comment edit history.
     */
    public function up(): void
    {
        Schema::create('comment_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('old_content');
            $table->text('new_content');
            $table->string('edit_reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('comment_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_edits');
    }
};
