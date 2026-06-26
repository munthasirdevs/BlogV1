<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction_type', 30); // like, love, laugh, insightful, support
            $table->timestamps();

            $table->unique(['comment_id', 'user_id', 'reaction_type']);
            $table->index('reaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
