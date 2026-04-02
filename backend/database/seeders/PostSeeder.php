<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;

/**
 * Class PostSeeder
 *
 * Seeds the posts table with initial content.
 */
class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();
        $tags = Tag::all();

        if ($users->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $admin = $users->where('email', 'admin@blog.com')->first();

        $posts = [
            [
                'title' => 'Getting Started with Laravel 12: A Complete Guide',
                'excerpt' => 'Learn how to get started with Laravel 12, the latest version of the popular PHP framework.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tutorials',
                'author_email' => 'admin@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'is_featured' => true,
                'tags' => ['laravel', 'php', 'tutorial', 'beginner'],
            ],
            [
                'title' => 'Building RESTful APIs with Laravel: Best Practices',
                'excerpt' => 'A comprehensive guide to building RESTful APIs using Laravel\'s powerful features.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tutorials',
                'author_email' => 'john@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'is_featured' => true,
                'tags' => ['laravel', 'api', 'rest-api', 'backend'],
            ],
            [
                'title' => 'Tailwind CSS Best Practices for 2026',
                'excerpt' => 'Discover the best practices for using Tailwind CSS in your projects.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'best-practices',
                'author_email' => 'jane@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'is_featured' => true,
                'tags' => ['tailwindcss', 'frontend', 'css', 'web-development'],
            ],
            [
                'title' => 'The Future of Web Development: Trends to Watch',
                'excerpt' => 'Exploring the trends and technologies shaping the future of web development.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'opinion',
                'author_email' => 'bob@blog.com',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'is_featured' => false,
                'tags' => ['web-development', 'opinion', 'trends'],
            ],
            [
                'title' => 'Database Optimization Tips for High-Traffic Applications',
                'excerpt' => 'Essential tips for optimizing your database queries and improving performance.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tips-tricks',
                'author_email' => 'admin@blog.com',
                'status' => 'published',
                'published_at' => now(),
                'is_featured' => true,
                'tags' => ['database', 'performance', 'optimization', 'backend'],
            ],
            [
                'title' => 'Understanding Authentication in Laravel: Sanctum vs Passport',
                'excerpt' => 'Deep dive into Laravel\'s authentication options.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tutorials',
                'author_email' => 'admin@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(10),
                'is_featured' => false,
                'tags' => ['laravel', 'authentication', 'security', 'api'],
            ],
            [
                'title' => 'Introduction to Vue 3 Composition API',
                'excerpt' => 'Get started with Vue 3\'s Composition API and learn how it improves code organization.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tutorials',
                'author_email' => 'jane@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'is_featured' => false,
                'tags' => ['vuejs', 'javascript', 'frontend', 'tutorial'],
            ],
            [
                'title' => 'Docker for Developers: A Practical Guide',
                'excerpt' => 'Learn how to use Docker to streamline your development workflow.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'tutorials',
                'author_email' => 'charlie@blog.com',
                'status' => 'published',
                'published_at' => now()->subDays(14),
                'is_featured' => true,
                'tags' => ['docker', 'devops', 'containers', 'deployment'],
            ],
            [
                'title' => 'Draft: Upcoming Features Preview',
                'excerpt' => 'A preview of upcoming features in our platform.',
                'content' => $this->getSampleContent(),
                'category_slug' => 'news',
                'author_email' => 'admin@blog.com',
                'status' => 'draft',
                'published_at' => null,
                'is_featured' => false,
                'tags' => ['news', 'preview'],
            ],
        ];

        foreach ($posts as $postData) {
            $author = $users->where('email', $postData['author_email'])->first() ?? $admin;
            $category = $categories->where('slug', $postData['category_slug'])->first() ?? $categories->first();
            
            $post = Post::create([
                'user_id' => $author->id,
                'category_id' => $category->id,
                'title' => $postData['title'],
                'slug' => \Illuminate\Support\Str::slug($postData['title']),
                'excerpt' => $postData['excerpt'],
                'content' => $postData['content'],
                'status' => $postData['status'],
                'published_at' => $postData['published_at'],
                'is_featured' => $postData['is_featured'] ?? false,
                'reading_time' => 5,
            ]);

            // Attach tags
            $tagIds = collect($postData['tags'])
                ->map(fn($slug) => $tags->where('slug', $slug)->first()?->id)
                ->filter()
                ->toArray();
            
            if (!empty($tagIds)) {
                $post->tags()->attach($tagIds);
            }
        }
    }

    /**
     * Get sample content.
     */
    private function getSampleContent(): string
    {
        return <<<EOT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

<h2>Getting Started</h2>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<h3>Key Points</h3>
<ul>
    <li>Nemo enim ipsam voluptatem quia voluptas sit aspernatur</li>
    <li>Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet</li>
    <li>Ut enim ad minima veniam, quis nostrum exercitationem</li>
</ul>

<h2>Deep Dive</h2>
<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>

<blockquote>
    <p>The best way to predict the future is to invent it. - Alan Kay</p>
</blockquote>

<h2>Conclusion</h2>
<p>Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.</p>
EOT;
    }
}
