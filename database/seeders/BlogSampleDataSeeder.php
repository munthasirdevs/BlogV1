<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first();
        if (!$author) {
            $author = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $categories = [
            ['name' => 'Technology', 'slug' => 'technology', 'color' => '#3b82f6', 'sort_order' => 1, 'status' => 'published'],
            ['name' => 'Design', 'slug' => 'design', 'color' => '#ec4899', 'sort_order' => 2, 'status' => 'published'],
            ['name' => 'Business', 'slug' => 'business', 'color' => '#10b981', 'sort_order' => 3, 'status' => 'published'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'color' => '#f59e0b', 'sort_order' => 4, 'status' => 'published'],
            ['name' => 'Science', 'slug' => 'science', 'color' => '#8b5cf6', 'sort_order' => 5, 'status' => 'published'],
            ['name' => 'Health', 'slug' => 'health', 'color' => '#ef4444', 'sort_order' => 6, 'status' => 'published'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['slug']] = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'color' => $cat['color'],
                'sort_order' => $cat['sort_order'],
                'status' => $cat['status'],
                'article_count' => 0,
                'created_by' => $author->id,
            ]);
        }

        $tags = [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Vue.js', 'slug' => 'vuejs'],
            ['name' => 'Tailwind CSS', 'slug' => 'tailwind-css'],
            ['name' => 'AI', 'slug' => 'ai'],
            ['name' => 'UX Design', 'slug' => 'ux-design'],
            ['name' => 'Startup', 'slug' => 'startup'],
            ['name' => 'Productivity', 'slug' => 'productivity'],
            ['name' => 'Open Source', 'slug' => 'open-source'],
            ['name' => 'DevOps', 'slug' => 'devops'],
            ['name' => 'JavaScript', 'slug' => 'javascript'],
        ];

        $createdTags = [];
        foreach ($tags as $tag) {
            $createdTags[$tag['slug']] = Tag::create([
                'name' => $tag['name'],
                'slug' => $tag['slug'],
                'status' => 'active',
                'usage_count' => rand(5, 50),
                'created_by' => $author->id,
            ]);
        }

        $posts = [
            [
                'title' => 'Getting Started with Laravel 12: A Complete Guide',
                'category' => 'technology',
                'tags' => ['laravel', 'tailwind-css', 'open-source'],
                'is_featured' => true,
                'reading_time' => 8,
                'content' => 'Laravel 12 brings a host of new features and improvements that make web development a breeze. From the new starter kits to improved testing capabilities, this release is packed with tools that will help you build better applications faster. In this comprehensive guide, we will walk through everything you need to know to get started with Laravel 12, including installation, configuration, routing, controllers, Eloquent ORM, Blade templating, and more.',
            ],
            [
                'title' => 'The Future of AI in Content Creation',
                'category' => 'technology',
                'tags' => ['ai', 'javascript'],
                'is_featured' => true,
                'reading_time' => 6,
                'content' => 'Artificial intelligence is transforming the way we create content. From AI-powered writing assistants to automated video generation, the possibilities are endless. This article explores the latest trends in AI content creation and what they mean for writers, marketers, and businesses. We will look at tools like ChatGPT, DALL-E, and specialized content platforms that are reshaping the industry.',
            ],
            [
                'title' => 'Building Beautiful UIs with Tailwind CSS v4',
                'category' => 'design',
                'tags' => ['tailwind-css', 'ux-design'],
                'is_featured' => false,
                'reading_time' => 5,
                'content' => 'Tailwind CSS v4 introduces a new CSS-first configuration approach that makes styling even more intuitive. Gone are the days of complex configuration files - now you can define your design system directly in your CSS using @theme directives. This guide covers the major changes and shows you how to build stunning interfaces with the latest version.',
            ],
            [
                'title' => '10 Productivity Tips for Remote Developers',
                'category' => 'business',
                'tags' => ['productivity', 'startup'],
                'is_featured' => false,
                'reading_time' => 4,
                'content' => 'Working remotely comes with unique challenges. Distractions at home, difficulty separating work and personal life, and communication barriers can all impact your productivity. In this article, we share ten proven strategies that top remote developers use to stay focused, organized, and productive while working from anywhere.',
            ],
            [
                'title' => 'Understanding Vue.js 3 Composition API',
                'category' => 'technology',
                'tags' => ['vuejs', 'javascript'],
                'is_featured' => false,
                'reading_time' => 7,
                'content' => 'The Composition API in Vue.js 3 represents a fundamental shift in how we organize component logic. Rather than being forced to organize by Options (data, methods, computed), the Composition API lets you organize by feature, making your code more maintainable and reusable. This deep dive covers setup(), ref(), reactive(), computed(), watch(), and lifecycle hooks.',
            ],
            [
                'title' => 'Designing Accessible Web Applications',
                'category' => 'design',
                'tags' => ['ux-design'],
                'is_featured' => false,
                'reading_time' => 6,
                'content' => 'Accessibility is not an afterthought - it is a fundamental aspect of good web design. With WCAG 2.2 guidelines and increasing legal requirements, building accessible web applications has never been more important. This article covers practical techniques for designing and developing websites that work for everyone, including keyboard navigation, screen reader support, color contrast, and focus management.',
            ],
            [
                'title' => 'DevOps Best Practices for Laravel Applications',
                'category' => 'technology',
                'tags' => ['devops', 'laravel'],
                'is_featured' => false,
                'reading_time' => 9,
                'content' => 'Deploying Laravel applications requires careful planning and the right tooling. From setting up CI/CD pipelines to managing queues and scheduled tasks, this guide covers the essential DevOps practices every Laravel developer should know. We will explore deployment strategies, monitoring, logging, and performance optimization techniques.',
            ],
            [
                'title' => 'The Rise of No-Code and Low-Code Platforms',
                'category' => 'business',
                'tags' => ['startup', 'ai'],
                'is_featured' => false,
                'reading_time' => 5,
                'content' => 'No-code and low-code platforms are democratizing software development, enabling non-technical users to build applications without writing code. This shift is transforming how businesses approach digital transformation. We analyze the market landscape, key players, and what this means for traditional software developers.',
            ],
            [
                'title' => 'Healthy Habits for Developers',
                'category' => 'health',
                'tags' => ['productivity'],
                'is_featured' => false,
                'reading_time' => 4,
                'content' => 'Sitting at a desk for hours can take a toll on your physical and mental health. This article explores practical habits that can help developers maintain their wellbeing while staying productive. From ergonomic setup to exercise routines, eye care, and mental health practices, we cover the essential aspects of a healthy developer lifestyle.',
            ],
        ];

        foreach ($posts as $index => $postData) {
            $category = $createdCategories[$postData['category']];
            $post = Post::create([
                'author_id' => $author->id,
                'category_id' => $category->id,
                'title' => $postData['title'],
                'slug' => Str::slug($postData['title']),
                'excerpt' => Str::limit($postData['content'], 200),
                'content' => $postData['content'],
                'status' => 'published',
                'is_featured' => $postData['is_featured'],
                'published_at' => now()->subDays(count($posts) - $index),
                'reading_time' => $postData['reading_time'],
                'word_count' => str_word_count($postData['content']),
                'views_count' => rand(50, 500),
            ]);

            foreach ($postData['tags'] as $tagSlug) {
                if (isset($createdTags[$tagSlug])) {
                    $post->tags()->attach($createdTags[$tagSlug]->id, [
                        'relevance_score' => rand(1, 100) / 100,
                    ]);
                }
            }

            $category->increment('article_count');
        }
    }
}
