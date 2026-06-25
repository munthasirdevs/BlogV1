<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('page_url', 500);
            $table->string('ip_hash', 64);
            $table->string('country', 2)->nullable();
            $table->enum('device_type', ['desktop', 'tablet', 'mobile'])->nullable();
            $table->string('browser', 100)->nullable();
            $table->timestamp('visited_at');

            $table->index('visited_at');
            $table->index('page_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
