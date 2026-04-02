<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\User;

/**
 * Class SubscriptionSeeder
 *
 * Seeds the subscriptions table with newsletter subscribers.
 */
class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // Create subscriptions for 50% of existing users
        foreach ($users->take($users->count() / 2) as $user) {
            Subscription::firstOrCreate(
                ['email' => $user->email],
                [
                    'user_id' => $user->id,
                    'is_confirmed' => true,
                    'confirmed_at' => now()->subDays(rand(1, 50)),
                    'subscribed_at' => now()->subDays(rand(1, 50)),
                    'frequency' => 'weekly',
                    'preferences' => ['new_posts' => true, 'weekly_digest' => true],
                ]
            );
        }

        // Create some email-only subscribers
        $emails = [
            'subscriber1@example.com',
            'subscriber2@example.com',
            'subscriber3@example.com',
            'newsletter@example.com',
            'reader@example.com',
        ];

        foreach ($emails as $email) {
            Subscription::firstOrCreate(
                ['email' => $email],
                [
                    'user_id' => null,
                    'is_confirmed' => true,
                    'confirmed_at' => now()->subDays(rand(1, 30)),
                    'subscribed_at' => now()->subDays(rand(1, 30)),
                    'frequency' => 'weekly',
                ]
            );
        }
    }
}
