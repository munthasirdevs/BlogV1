<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            if (!Schema::hasColumn('seo_meta', 'focus_keyword')) {
                $table->string('focus_keyword', 100)->nullable()->after('schema_type');
                $table->json('secondary_keywords')->nullable()->after('focus_keyword');
                $table->decimal('readability_score', 5, 2)->nullable()->after('secondary_keywords');
                $table->decimal('keyword_density', 5, 2)->nullable()->after('readability_score');
                $table->unsignedSmallInteger('internal_links_count')->default(0)->after('keyword_density');
                $table->unsignedSmallInteger('external_links_count')->default(0)->after('internal_links_count');
                $table->timestamp('last_optimized_at')->nullable()->after('external_links_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->dropColumn(['focus_keyword', 'secondary_keywords', 'readability_score', 'keyword_density', 'internal_links_count', 'external_links_count', 'last_optimized_at']);
        });
    }
};
