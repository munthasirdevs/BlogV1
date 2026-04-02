<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserSeeder
 *
 * Seeds the users table with initial data including admin, editors,
 * moderators, and regular users.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@blog.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Platform Administrator with full access to all features.',
                'avatar' => 'https://i.pravatar.cc/200?img=1',
                'website' => 'https://blog.example.com',
                'twitter' => 'admin_blog',
                'github' => 'admin-blog',
                'location' => 'San Francisco, CA',
                'timezone' => 'America/Los_Angeles',
            ]
        );

        // Create editor user
        User::firstOrCreate(
            ['email' => 'editor@blog.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('password123'),
                'role' => 'editor',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Content editor responsible for reviewing and publishing posts.',
                'avatar' => 'https://i.pravatar.cc/200?img=2',
                'twitter' => 'editor_blog',
                'location' => 'New York, NY',
                'timezone' => 'America/New_York',
            ]
        );

        // Create moderator user
        User::firstOrCreate(
            ['email' => 'moderator@blog.com'],
            [
                'name' => 'Moderator User',
                'password' => Hash::make('password123'),
                'role' => 'moderator',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Community moderator managing comments and user interactions.',
                'avatar' => 'https://i.pravatar.cc/200?img=3',
                'location' => 'London, UK',
                'timezone' => 'Europe/London',
            ]
        );

        // Create regular users (authors)
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@blog.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Tech enthusiast and blogger passionate about web development.',
                'avatar' => 'https://i.pravatar.cc/200?img=11',
                'website' => 'https://johndoe.dev',
                'twitter' => 'johndoe_dev',
                'github' => 'johndoe',
                'location' => 'Austin, TX',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@blog.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Full-stack developer specializing in Laravel and Vue.js.',
                'avatar' => 'https://i.pravatar.cc/200?img=5',
                'twitter' => 'jane_codes',
                'github' => 'janesmith',
                'linkedin' => 'jane-smith-dev',
                'location' => 'Seattle, WA',
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@blog.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'Content creator and technical writer.',
                'avatar' => 'https://i.pravatar.cc/200?img=13',
                'website' => 'https://bobwilson.io',
                'location' => 'Denver, CO',
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@blog.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'UX designer turned developer. Love creating beautiful interfaces.',
                'avatar' => 'https://i.pravatar.cc/200?img=9',
                'twitter' => 'alice_ux',
                'github' => 'alicejohnson',
                'location' => 'Portland, OR',
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@blog.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'bio' => 'DevOps engineer and cloud architecture enthusiast.',
                'avatar' => 'https://i.pravatar.cc/200?img=12',
                'github' => 'charliebrown',
                'location' => 'Chicago, IL',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }

        // Create a banned user for testing
        User::firstOrCreate(
            ['email' => 'banned@blog.com'],
            [
                'name' => 'Banned User',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'banned',
                'email_verified_at' => now(),
            ]
        );
    }
}
