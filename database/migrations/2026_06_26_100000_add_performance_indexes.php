<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'tags', 'comments', 'media_files'] as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('tenant_id')->nullable()->after('id');
                    $t->index('tenant_id');
                });
            }
        }

        if (!Schema::hasIndex('posts', 'idx_posts_tenant_status_published')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['tenant_id', 'status', 'published_at'], 'idx_posts_tenant_status_published');
            });
        }

        if (!Schema::hasIndex('posts', 'idx_posts_tenant_cat_published')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['tenant_id', 'category_id', 'published_at'], 'idx_posts_tenant_cat_published');
            });
        }

        if (!Schema::hasColumn('categories', 'posts_count')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedInteger('posts_count')->default(0)->after('slug');
            });
        }

        if (!Schema::hasIndex('categories', 'idx_categories_tenant_slug')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index(['tenant_id', 'slug'], 'idx_categories_tenant_slug');
            });
        }

        if (!Schema::hasColumn('posts', 'comments_count')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->unsignedInteger('comments_count')->default(0)->after('excerpt');
            });
        }

        if (!Schema::hasIndex('posts', 'idx_posts_tenant_slug')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['tenant_id', 'slug'], 'idx_posts_tenant_slug');
            });
        }

        if (!Schema::hasIndex('tags', 'idx_tags_tenant_slug')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->index(['tenant_id', 'slug'], 'idx_tags_tenant_slug');
            });
        }

        if (!Schema::hasIndex('comments', 'idx_comments_tenant_post_status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->index(['tenant_id', 'post_id', 'status'], 'idx_comments_tenant_post_status');
            });
        }

        if (!Schema::hasIndex('media_files', 'idx_media_tenant_model')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->index(['tenant_id', 'user_id'], 'idx_media_tenant_model');
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_tenant_status_published');
            $table->dropIndex('idx_posts_tenant_cat_published');
            $table->dropIndex('idx_posts_tenant_slug');
            $table->dropColumn('comments_count');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_tenant_slug');
            $table->dropColumn('posts_count');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('idx_tags_tenant_slug');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_tenant_post_status');
        });

        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_media_tenant_model');
        });
    }
};
