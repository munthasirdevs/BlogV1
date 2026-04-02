<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;

/**
 * Class BookmarkSeeder
 *
 * Seeds the bookmarks table with user bookmarks.
 */
class BookmarkSeeder extends Seeder
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

        $collections = ['default', 'reading-list', 'favorites', 'to-read'];

        foreach ($users as $user) {
            // Each user bookmarks 1-3 posts
            $numBookmarks = rand(1, 3);
            $randomPosts = $posts->random(min($numBookmarks, $posts->count()));

            foreach ($randomPosts as $post) {
                $collection = $collections[array_rand($collections)];
                
                Bookmark::firstOrCreate([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'collection_name' => $collection,
                ]);
            }
        }
    }
}
