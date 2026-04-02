<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Class CommentSeeder
 *
 * Seeds the comments table with initial data including
 * nested replies and various statuses.
 */
class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::published()->get();
        $users = User::active()->get();

        if ($posts->isEmpty() || $users->isEmpty()) {
            return;
        }

        $commentTemplates = [
            'Great article! Very helpful and informative. Thanks for sharing!',
            'This is exactly what I was looking for. Well explained!',
            'Excellent write-up. Bookmarked for future reference.',
            'Really appreciate the detailed explanation here. Learned a lot!',
            'One of the best articles I\'ve read on this topic. Keep it up!',
            'Could you elaborate more on the second point? Very interesting.',
            'Fantastic content as always! Looking forward to more.',
            'This helped me solve a problem I\'ve been stuck on for days. Thank you!',
            'Clear and concise. Great job explaining complex concepts.',
            'I\'ve been waiting for a tutorial like this. Perfect timing!',
        ];

        $replyTemplates = [
            'Glad you found it helpful!',
            'Thanks for the feedback!',
            'You\'re welcome! Happy coding!',
            'Great point! I\'ll consider covering that in a future post.',
        ];

        foreach ($posts as $post) {
            // Create 2-4 top-level comments per post
            $numComments = rand(2, 4);
            
            for ($i = 0; $i < $numComments; $i++) {
                $template = $commentTemplates[array_rand($commentTemplates)];
                
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $users->random()->id,
                    'content' => $template,
                    'status' => 'approved',
                    'parent_id' => null,
                    'depth' => 0,
                    'likes_count' => rand(0, 10),
                    'is_edited' => false,
                    'created_at' => now()->subMinutes(rand(1, 5000)),
                ]);

                // 30% chance to add one reply
                if (rand(0, 100) < 30) {
                    Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $users->random()->id,
                        'content' => $replyTemplates[array_rand($replyTemplates)],
                        'status' => 'approved',
                        'parent_id' => $comment->id,
                        'depth' => 1,
                        'likes_count' => rand(0, 5),
                        'is_edited' => false,
                        'created_at' => $comment->created_at->addMinutes(rand(5, 500)),
                    ]);
                }
            }
        }
    }
}
