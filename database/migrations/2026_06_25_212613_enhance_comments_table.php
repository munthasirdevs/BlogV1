<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (!Schema::hasColumn('comments', 'is_edited')) {
                $table->boolean('is_edited')->default(false)->after('status');
                $table->timestamp('edited_at')->nullable()->after('is_edited');
                $table->decimal('ai_moderation_score', 5, 2)->nullable()->after('edited_at');
                $table->string('user_agent', 500)->nullable()->after('ip_address');
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['is_edited', 'edited_at', 'ai_moderation_score', 'user_agent', 'deleted_at']);
        });
    }
};
