<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 *
 * Main database seeder that orchestrates all module seeders.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Core data (order matters due to foreign key constraints)
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            LikeSeeder::class,
            BookmarkSeeder::class,
            SubscriptionSeeder::class,
            AnalyticsSeeder::class,
        ]);

        $this->command->info('Database seeding completed!');
        $this->command->info('Summary:');
        $this->command->info('  Users: ' . \App\Models\User::count());
        $this->command->info('  Categories: ' . \App\Models\Category::count());
        $this->command->info('  Tags: ' . \App\Models\Tag::count());
        $this->command->info('  Posts: ' . \App\Models\Post::count());
        $this->command->info('  Comments: ' . \App\Models\Comment::count());
    }
}
