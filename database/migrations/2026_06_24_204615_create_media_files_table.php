<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->string('mime_type', 100);
            $table->string('file_extension', 10);
            $table->unsignedInteger('file_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('optimization_status', 20)->default('pending');
            $table->json('ai_tags')->nullable();
            $table->string('hash_signature', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('mime_type');
            $table->index('hash_signature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
