<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('job_type', 50); // publish, update, unpublish, ai-optimize
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending, queued, completed, failed
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('queue_name', 100)->nullable();
            $table->timestamps();

            $table->index('scheduled_at');
            $table->index('status');
            $table->index(['post_id', 'job_type']);
        });

        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'publish_timezone')) {
                $table->string('publish_timezone', 50)->nullable()->after('scheduled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_jobs');
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('publish_timezone');
        });
    }
};
