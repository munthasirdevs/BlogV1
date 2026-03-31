<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@blog.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'bio' => 'Platform Administrator',
        ]);

        // Create regular users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@blog.com",
                'password' => Hash::make('password123'),
                'role' => 'user',
                'email_verified_at' => now(),
                'bio' => "This is user {$i}'s bio",
            ]);
        }

        // Create categories
        $categories = [
            ['name' => 'Tutorials', 'description' => 'Step-by-step guides and how-tos'],
            ['name' => 'News', 'description' => 'Latest news and updates'],
            ['name' => 'Opinion', 'description' => 'Thoughts and opinions'],
            ['name' => 'Reviews', 'description' => 'Product and service reviews'],
            ['name' => 'Tips', 'description' => 'Quick tips and tricks'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[] = Category::create($cat);
        }

        // Create tags
        $tags = [
            'Laravel', 'PHP', 'JavaScript', 'Tailwind', 'Vue', 'React',
            'Tutorial', 'Beginner', 'Advanced', 'Web Development', 'Backend',
            'Frontend', 'Database', 'API', 'Security', 'Performance'
        ];

        $tagModels = [];
        foreach ($tags as $tagName) {
            $tagModels[] = Tag::create(['name' => $tagName]);
        }

        // Create posts
        $posts = [
            [
                'title' => 'Getting Started with Laravel 12',
                'content' => $this->generateLoremIpsum(),
                'excerpt' => 'Learn how to get started with Laravel 12, the latest version of the popular PHP framework.',
                'category_id' => $categoryModels[0]->id,
                'user_id' => $users[0]->id,
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Building RESTful APIs with Laravel',
                'content' => $this->generateLoremIpsum(),
                'excerpt' => 'A comprehensive guide to building RESTful APIs using Laravel\'s powerful features.',
                'category_id' => $categoryModels[0]->id,
                'user_id' => $users[1]->id,
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Tailwind CSS Best Practices',
                'content' => $this->generateLoremIpsum(),
                'excerpt' => 'Discover the best practices for using Tailwind CSS in your projects.',
                'category_id' => $categoryModels[0]->id,
                'user_id' => $users[2]->id,
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'The Future of Web Development',
                'content' => $this->generateLoremIpsum(),
                'excerpt' => 'Exploring the trends and technologies shaping the future of web development.',
                'category_id' => $categoryModels[2]->id,
                'user_id' => $users[3]->id,
                'status' => 'published',
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Database Optimization Tips',
                'content' => $this->generateLoremIpsum(),
                'excerpt' => 'Essential tips for optimizing your database queries and improving performance.',
                'category_id' => $categoryModels[4]->id,
                'user_id' => $users[4]->id,
                'status' => 'published',
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $postData) {
            $post = Post::create($postData);
            
            // Attach random tags
            $randomTags = array_rand($tagModels, rand(2, 4));
            foreach ((array) $randomTags as $tagIndex) {
                $post->tags()->attach($tagModels[$tagIndex]->id);
            }
        }

        // Create comments
        $allPosts = Post::all();
        foreach ($allPosts as $post) {
            $commentCount = rand(2, 5);
            for ($i = 0; $i < $commentCount; $i++) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $users[array_rand($users)]->id,
                    'content' => 'Great article! Very helpful and informative.',
                    'status' => 'approved',
                    'created_at' => now()->subMinutes(rand(1, 1000)),
                ]);
            }
        }
    }

    private function generateLoremIpsum(): string
    {
        return <<<EOT
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

<h2>Getting Started</h2>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<h3>Key Points</h3>
<ul>
    <li>Sed ut perspiciatis unde omnis iste natus error</li>
    <li>Nemo enim ipsam voluptatem quia voluptas sit aspernatur</li>
    <li>Neque porro quisquam est, qui dolorem ipsum quia dolor</li>
</ul>

<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>

<h2>Conclusion</h2>
<p>Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus.</p>
EOT;
    }
}
