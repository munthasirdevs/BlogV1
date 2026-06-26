<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Posts
            'create_posts',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'schedule_posts',
            'archive_posts',
            'feature_posts',
            'view_post_revisions',
            'restore_post_revisions',
            // Categories
            'create_categories',
            'edit_categories',
            'delete_categories',
            'restore_categories',
            // Tags
            'create_tags',
            'edit_tags',
            'delete_tags',
            'merge_tags',
            // Media
            'upload_media',
            'edit_media',
            'delete_media',
            'manage_media',
            // Comments
            'moderate_comments',
            'approve_comments',
            'delete_comments',
            // Users
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'suspend_users',
            'ban_users',
            // SEO
            'manage_meta_titles',
            'manage_meta_descriptions',
            'manage_schema',
            'manage_sitemap',
            'manage_redirects',
            'manage_canonicals',
            // AI
            'generate_ai_content',
            'generate_ai_titles',
            'generate_ai_meta',
            'generate_ai_keywords',
            'run_ai_audits',
            'manage_ai_models',
            // Analytics
            'view_dashboard_analytics',
            'view_post_analytics',
            'export_reports',
            // Settings
            'manage_general_settings',
            'manage_seo_settings',
            'manage_email_settings',
            'manage_security_settings',
            'manage_ai_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            // Posts
            'create_posts',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'schedule_posts',
            'archive_posts',
            'feature_posts',
            // Categories
            'create_categories',
            'edit_categories',
            'delete_categories',
            'restore_categories',
            // Tags
            'create_tags',
            'edit_tags',
            'delete_tags',
            'merge_tags',
            // Media
            'upload_media',
            'edit_media',
            'delete_media',
            // Comments
            'moderate_comments',
            'approve_comments',
            'delete_comments',
            // Users
            'view_users',
            'create_users',
            'edit_users',
            'suspend_users',
            // SEO
            'manage_meta_titles',
            'manage_meta_descriptions',
            'manage_schema',
            'manage_sitemap',
            'manage_redirects',
            'manage_canonicals',
            // AI
            'generate_ai_content',
            'generate_ai_titles',
            'generate_ai_meta',
            'generate_ai_keywords',
            'run_ai_audits',
            // Analytics
            'view_dashboard_analytics',
            'view_post_analytics',
            'export_reports',
            // Settings
            'manage_general_settings',
            'manage_seo_settings',
            'manage_ai_settings',
        ]);

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->givePermissionTo([
            'create_posts',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'schedule_posts',
            'archive_posts',
            'feature_posts',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'create_tags',
            'edit_tags',
            'delete_tags',
            'upload_media',
            'edit_media',
            'moderate_comments',
            'approve_comments',
            'delete_comments',
            'manage_meta_titles',
            'manage_meta_descriptions',
            'manage_schema',
            'manage_canonicals',
            'generate_ai_content',
            'generate_ai_titles',
            'generate_ai_meta',
            'generate_ai_keywords',
            'run_ai_audits',
            'view_dashboard_analytics',
            'view_post_analytics',
        ]);

        $author = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        $author->givePermissionTo([
            'create_posts',
            'edit_posts',
            'delete_posts',
            'upload_media',
            'edit_media',
            'generate_ai_content',
            'generate_ai_titles',
            'generate_ai_keywords',
            'view_post_analytics',
        ]);

        $contributor = Role::firstOrCreate(['name' => 'contributor', 'guard_name' => 'web']);
        $contributor->givePermissionTo([
            'create_posts',
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@blogv1.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        if (!$adminUser->hasRole('super-admin')) {
            $adminUser->assignRole('super-admin');
        }
    }
}
