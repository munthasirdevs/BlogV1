<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft','review','seo_review','approved','scheduled','published','archived','revision_required') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft','review','seo_review','approved','scheduled','published','archived') NOT NULL DEFAULT 'draft'");
    }
};
