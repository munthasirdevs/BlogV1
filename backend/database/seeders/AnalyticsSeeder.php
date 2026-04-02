<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnalyticsEvent;
use App\Models\PostView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Class AnalyticsSeeder
 *
 * Seeds the analytics_events and post_views tables with sample data.
 */
class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::published()->get();
        $users = User::all();

        if ($posts->isEmpty()) {
            return;
        }

        // Generate sessions
        $sessions = [];
        for ($i = 0; $i < 10; $i++) {
            $sessions[] = Str::random(64);
        }

        // Create post views (5-15 per post)
        foreach ($posts as $post) {
            $numViews = rand(5, 15);

            for ($i = 0; $i < $numViews; $i++) {
                PostView::create([
                    'post_id' => $post->id,
                    'user_id' => rand(0, 100) < 40 ? $users->random()->id : null,
                    'session_id' => $sessions[array_rand($sessions)],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => rand(0, 100) < 50 ? 'https://www.google.com/' : null,
                    'time_on_page' => rand(30, 300),
                    'is_unique' => true,
                    'viewed_at' => now()->subMinutes(rand(0, 10000)),
                ]);
            }

            $post->update(['views_count' => $numViews]);
        }

        // Create some analytics events
        for ($i = 0; $i < 30; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'user_id' => rand(0, 100) < 50 ? $users->random()->id : null,
                'session_id' => $sessions[array_rand($sessions)],
                'url' => fake()->url(),
                'referrer' => 'https://www.google.com/',
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'occurred_at' => now()->subMinutes(rand(0, 10000)),
            ]);
        }
    }

    /**
     * Generate a realistic referrer.
     */
    private function generateReferrer(): ?string
    {
        $referrers = [
            null,
            'https://www.google.com/search?q=laravel+blog',
            'https://twitter.com/',
            'https://www.reddit.com/r/programming',
        ];
        return $referrers[array_rand($referrers)];
    }
}
