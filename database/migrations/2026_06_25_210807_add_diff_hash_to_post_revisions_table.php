<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_revisions', function (Blueprint $table) {
            if (!Schema::hasColumn('post_revisions', 'diff_hash')) {
                $table->string('diff_hash', 64)->nullable()->after('change_summary')->index();
            }
            if (!Schema::hasColumn('post_revisions', 'ai_prompt')) {
                $table->text('ai_prompt')->nullable()->after('ai_tool_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_revisions', function (Blueprint $table) {
            $table->dropColumn(['diff_hash', 'ai_prompt']);
        });
    }
};
