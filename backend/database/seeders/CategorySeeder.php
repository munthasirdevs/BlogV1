<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

/**
 * Class CategorySeeder
 *
 * Seeds the categories table with initial data including
 * hierarchical parent-child relationships.
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding categories...');

        // Top-level categories
        $categories = [
            [
                'name' => 'Tutorials',
                'slug' => 'tutorials',
                'description' => 'Step-by-step guides and how-to articles for developers.',
                'color' => '#3B82F6',
                'icon' => '📚',
                'sort_order' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'News',
                'slug' => 'news',
                'description' => 'Latest news and updates from the tech world.',
                'color' => '#10B981',
                'icon' => '📰',
                'sort_order' => 2,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Opinion',
                'slug' => 'opinion',
                'description' => 'Thoughts, opinions, and perspectives on technology.',
                'color' => '#F59E0B',
                'icon' => '💭',
                'sort_order' => 3,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Reviews',
                'slug' => 'reviews',
                'description' => 'In-depth reviews of tools, frameworks, and services.',
                'color' => '#8B5CF6',
                'icon' => '⭐',
                'sort_order' => 4,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tips & Tricks',
                'slug' => 'tips-tricks',
                'description' => 'Quick tips and tricks to improve your workflow.',
                'color' => '#EC4899',
                'icon' => '💡',
                'sort_order' => 5,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Best Practices',
                'slug' => 'best-practices',
                'description' => 'Industry best practices and coding standards.',
                'color' => '#06B6D4',
                'icon' => '✅',
                'sort_order' => 6,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Career',
                'slug' => 'career',
                'description' => 'Career advice and professional development for developers.',
                'color' => '#84CC16',
                'icon' => '🚀',
                'sort_order' => 7,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $category = Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
            $createdCategories[$category->slug] = $category;
            $this->command->info("  ✓ Created category: {$category->name}");
        }

        // Child categories
        $childCategories = [
            [
                'parent_slug' => 'tutorials',
                'name' => 'Beginner Tutorials',
                'slug' => 'beginner-tutorials',
                'description' => 'Tutorials for beginners just starting out.',
                'color' => '#60A5FA',
                'icon' => '🌱',
            ],
            [
                'parent_slug' => 'tutorials',
                'name' => 'Advanced Tutorials',
                'slug' => 'advanced-tutorials',
                'description' => 'Advanced tutorials for experienced developers.',
                'color' => '#3B82F6',
                'icon' => '🔥',
            ],
            [
                'parent_slug' => 'news',
                'name' => 'Product Launches',
                'slug' => 'product-launches',
                'description' => 'New product and feature announcements.',
                'color' => '#34D399',
                'icon' => '🎉',
            ],
            [
                'parent_slug' => 'news',
                'name' => 'Industry Updates',
                'slug' => 'industry-updates',
                'description' => 'Updates and changes in the tech industry.',
                'color' => '#10B981',
                'icon' => '📊',
            ],
            [
                'parent_slug' => 'reviews',
                'name' => 'Tool Reviews',
                'slug' => 'tool-reviews',
                'description' => 'Reviews of development tools and software.',
                'color' => '#A78BFA',
                'icon' => '🔧',
            ],
            [
                'parent_slug' => 'reviews',
                'name' => 'Book Reviews',
                'slug' => 'book-reviews',
                'description' => 'Reviews of technical books and resources.',
                'color' => '#8B5CF6',
                'icon' => '📖',
            ],
            [
                'parent_slug' => 'career',
                'name' => 'Interview Prep',
                'slug' => 'interview-prep',
                'description' => 'Tips and resources for technical interviews.',
                'color' => '#A3E635',
                'icon' => '📝',
            ],
            [
                'parent_slug' => 'career',
                'name' => 'Salary & Benefits',
                'slug' => 'salary-benefits',
                'description' => 'Discussions about compensation and benefits.',
                'color' => '#84CC16',
                'icon' => '💰',
            ],
        ];

        foreach ($childCategories as $childData) {
            $parent = $createdCategories[$childData['parent_slug']] ?? null;
            if ($parent) {
                $category = Category::firstOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childData['name'],
                        'description' => $childData['description'],
                        'color' => $childData['color'],
                        'icon' => $childData['icon'],
                        'is_active' => true,
                    ]
                );
                $this->command->info("  ✓ Created child category: {$category->name} (parent: {$parent->name})");
            }
        }

        $this->command->info('Category seeding completed: ' . Category::count() . ' categories total.');
    }
}
