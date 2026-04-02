<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class RoleSeeder
 *
 * Seeds the database with default roles and permissions for the blog platform.
 * This seeder is idempotent and can be run multiple times safely.
 *
 * Roles Hierarchy (highest to lowest):
 * - super-admin: Full system access, can manage other admins
 * - admin: Full content and user management
 * - editor: Can publish and feature posts, manage categories/tags
 * - moderator: Can manage comments and ban users
 * - author: Can create and manage own posts
 * - subscriber: Basic user, can comment and like
 *
 * @package Database\Seeders
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'sanctum';

        // =========================================================================
        // DEFINE PERMISSIONS
        // =========================================================================
        
        $permissions = [
            // Post permissions
            'create-post',
            'edit-post',
            'edit-any-post',
            'delete-post',
            'delete-any-post',
            'publish-post',
            'feature-post',
            
            // Comment permissions
            'create-comment',
            'edit-comment',
            'edit-any-comment',
            'delete-comment',
            'delete-any-comment',
            'approve-comment',
            'moderate-comments',
            
            // User permissions
            'view-users',
            'manage-users',
            'manage-roles',
            'ban-users',
            
            // Settings permissions
            'manage-settings',
            'manage-categories',
            'manage-tags',
            
            // System permissions
            'access-admin-panel',
            'view-analytics',
            'manage-media',
            'upload-media',
            'delete-media',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        $this->command->info('Created ' . count($permissions) . ' permissions.');

        // =========================================================================
        // CREATE ROLES WITH PERMISSIONS
        // =========================================================================

        // -------------------------------------------------------------------------
        // SUPER-ADMIN ROLE
        // Has all permissions, can manage other administrators
        // -------------------------------------------------------------------------
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guardName,
        ]);
        $superAdmin->syncPermissions($permissions);

        // -------------------------------------------------------------------------
        // ADMIN ROLE
        // Full content and user management, but cannot manage super-admins
        // -------------------------------------------------------------------------
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guardName,
        ]);
        $admin->syncPermissions([
            // Posts
            'create-post',
            'edit-post',
            'edit-any-post',
            'delete-post',
            'delete-any-post',
            'publish-post',
            'feature-post',
            
            // Comments
            'create-comment',
            'edit-comment',
            'edit-any-comment',
            'delete-comment',
            'delete-any-comment',
            'approve-comment',
            'moderate-comments',
            
            // Users
            'view-users',
            'manage-users',
            'manage-roles',
            'ban-users',
            
            // Settings
            'manage-settings',
            'manage-categories',
            'manage-tags',
            
            // System
            'access-admin-panel',
            'view-analytics',
            'manage-media',
            'upload-media',
            'delete-media',
        ]);

        // -------------------------------------------------------------------------
        // EDITOR ROLE
        // Can publish and feature posts, manage categories and tags
        // Cannot manage users or system settings
        // -------------------------------------------------------------------------
        $editor = Role::firstOrCreate([
            'name' => 'editor',
            'guard_name' => $guardName,
        ]);
        $editor->syncPermissions([
            // Posts
            'create-post',
            'edit-post',
            'edit-any-post',
            'delete-post',
            'publish-post',
            'feature-post',
            
            // Comments
            'create-comment',
            'edit-comment',
            'edit-any-comment',
            'delete-comment',
            'approve-comment',
            
            // Settings
            'manage-categories',
            'manage-tags',
            
            // System
            'access-admin-panel',
            'view-analytics',
            'upload-media',
        ]);

        // -------------------------------------------------------------------------
        // MODERATOR ROLE
        // Can manage comments and ban users
        // Cannot manage posts or system settings
        // -------------------------------------------------------------------------
        $moderator = Role::firstOrCreate([
            'name' => 'moderator',
            'guard_name' => $guardName,
        ]);
        $moderator->syncPermissions([
            // Comments
            'create-comment',
            'edit-comment',
            'edit-any-comment',
            'delete-comment',
            'delete-any-comment',
            'approve-comment',
            'moderate-comments',
            
            // Users
            'view-users',
            'ban-users',
            
            // System
            'access-admin-panel',
        ]);

        // -------------------------------------------------------------------------
        // AUTHOR ROLE
        // Can create and manage own posts only
        // Cannot publish (requires editor approval)
        // -------------------------------------------------------------------------
        $author = Role::firstOrCreate([
            'name' => 'author',
            'guard_name' => $guardName,
        ]);
        $author->syncPermissions([
            // Posts
            'create-post',
            'edit-post',
            'delete-post',
            
            // Comments
            'create-comment',
            'edit-comment',
            'delete-comment',
            
            // System
            'upload-media',
        ]);

        // -------------------------------------------------------------------------
        // SUBSCRIBER ROLE
        // Basic user - can read, comment, and like
        // -------------------------------------------------------------------------
        $subscriber = Role::firstOrCreate([
            'name' => 'subscriber',
            'guard_name' => $guardName,
        ]);
        $subscriber->syncPermissions([
            // Comments
            'create-comment',
            'edit-comment',
            'delete-comment',
            
            // System
            'view-analytics',
        ]);

        $this->command->info('Created 6 roles with permissions.');

        // =========================================================================
        // DISPLAY SUMMARY
        // =========================================================================
        
        $this->command->info('');
        $this->command->info('=== Roles and Permissions Summary ===');
        $this->command->info('');
        
        foreach (['super-admin', 'admin', 'editor', 'moderator', 'author', 'subscriber'] as $roleName) {
            $role = Role::findByName($roleName, $guardName);
            $permissionCount = $role->permissions->count();
            $this->command->info("  {$roleName}: {$permissionCount} permissions");
        }
        
        $this->command->info('');
        $this->command->info('Role seeding completed successfully!');
    }
}
