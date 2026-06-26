<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'template')) {
                $table->string('template', 50)->default('default')->after('status');
            }
            if (!Schema::hasColumn('categories', 'lang')) {
                $table->string('lang', 10)->default(config('app.locale'))->after('template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['template', 'lang']);
        });
    }
};
