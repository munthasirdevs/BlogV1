<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['posts', 'categories', 'tags', 'media_files', 'comments', 'pages', 'analytics_events'];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->foreignId('tenant_id')->nullable()->constrained('sites')->cascadeOnDelete()->after('id');
                    $t->index('tenant_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['posts', 'categories', 'tags', 'media_files', 'comments', 'pages', 'analytics_events'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tenant_id']);
                    $t->dropColumn('tenant_id');
                });
            }
        }
    }
};
