<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 200);
            $table->string('version', 50);
            $table->string('author', 200)->nullable();
            $table->text('description')->nullable();
            $table->json('permissions_required')->nullable();
            $table->json('event_subscriptions')->nullable();
            $table->string('provider_class', 500)->nullable();
            $table->string('status', 20)->default('installed'); // installed, enabled, disabled
            $table->boolean('is_tenant_aware')->default(false);
            $table->string('min_core_version', 20)->default('1.0.0');
            $table->timestamps();
        });

        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins')->cascadeOnDelete();
            $table->string('version', 50);
            $table->text('changelog')->nullable();
            $table->json('compatibility')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('plugin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('sites')->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['plugin_id', 'tenant_id', 'key']);
        });

        Schema::create('tenant_plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('plugin_id')->constrained('plugins')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'plugin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plugins');
        Schema::dropIfExists('plugin_settings');
        Schema::dropIfExists('plugin_versions');
        Schema::dropIfExists('plugins');
    }
};
