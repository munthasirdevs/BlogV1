<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;

/**
 * Class LikeSeeder
 *
 * Seeds the likes table with likes on posts and comments.
 */
class LikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::published()->get();
        $comments = Comment::approved()->get();
        $users = User::active()->get();

        if ($posts->isEmpty() || $users->isEmpty()) {
            return;
        }

        // Create likes for posts (3-8 likes per post)
        foreach ($posts as $post) {
            $numLikes = rand(3, min(8, $users->count()));
            $randomUsers = $users->random($numLikes);

            foreach ($randomUsers as $user) {
                Like::firstOrCreate([
                    'user_id' => $user->id,
                    'likeable_type' => Post::class,
                    'likeable_id' => $post->id,
                ]);
            }
        }

        // Create likes for some comments
        foreach ($comments->take(20) as $comment) {
            $numLikes = rand(0, 3);
            if ($numLikes > 0) {
                $randomUsers = $users->random(min($numLikes, $users->count()));
                foreach ($randomUsers as $user) {
                    Like::firstOrCreate([
                        'user_id' => $user->id,
                        'likeable_type' => Comment::class,
                        'likeable_id' => $comment->id,
                    ]);
                }
            }
        }
    }
}
