<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\NotificationPreference;
use App\Models\Post;
use App\Models\User;
use App\Notifications\DigestNotification;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewLikeNotification;
use App\Notifications\PostPublishedNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Class NotificationsTest
 *
 * Feature tests for the Notifications System (Phase 13).
 * 
 * Tests cover:
 * - API endpoints for notifications CRUD
 * - Notification preferences management
 * - Notification triggering from various sources
 * - Real-time broadcasting
 * - Scheduled jobs
 * - User authorization
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $otherUser;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['status' => 'active']);
        $this->otherUser = User::factory()->create(['status' => 'active']);
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
    }

    // ==============================
    // API Endpoint Tests
    // ==============================

    /** @test */
    public function authenticated_user_can_list_their_notifications()
    {
        // Create some notifications for the user
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'action_url',
                        'is_read',
                        'read_at',
                        'created_at',
                        'from_user',
                        'data',
                        'icon',
                        'color',
                    ]
                ],
                'meta',
                'links',
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_list_notifications()
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_see_other_users_notifications()
    {
        // Create notification for other user
        $this->otherUser->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->user
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function can_filter_notifications_by_read_status()
    {
        // Create read and unread notifications
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();
        $notification->update(['read_at' => now()]);

        // Test unread filter
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?read_status=unread');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Test read filter
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?read_status=read');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function can_paginate_notifications()
    {
        // Create multiple notifications
        for ($i = 0; $i < 25; $i++) {
            $this->user->notify(new NewCommentNotification(
                Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
                $this->otherUser
            ));
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /** @test */
    public function can_get_single_notification()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $notification->id);
    }

    /** @test */
    public function cannot_get_other_users_notification()
    {
        $this->otherUser->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->user
        ));

        $notification = $this->otherUser->notifications()->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function can_mark_notification_as_read()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function can_mark_notification_as_unread()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();
        $notification->update(['read_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/unread");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNull($notification->fresh()->read_at);
    }

    /** @test */
    public function can_delete_notification()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();
        $notificationId = $notification->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/notifications/{$notificationId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
    }

    /** @test */
    public function can_mark_all_notifications_as_read()
    {
        // Create multiple unread notifications
        for ($i = 0; $i < 5; $i++) {
            $this->user->notify(new NewCommentNotification(
                Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
                $this->otherUser
            ));
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.marked_count', 5);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    /** @test */
    public function can_get_unread_notification_count()
    {
        // Create some notifications
        for ($i = 0; $i < 3; $i++) {
            $this->user->notify(new NewCommentNotification(
                Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
                $this->otherUser
            ));
        }

        // Mark one as read
        $this->user->notifications()->first()?->update(['read_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 2);
    }

    /** @test */
    public function unread_count_endpoint_is_cached()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count');

        // Second request should be cached
        $response = $this->getJson('/api/v1/notifications/unread-count');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_notification_statistics()
    {
        // Create notifications of different types
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));
        $this->user->notify(new NewLikeNotification($this->post, $this->otherUser));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'unread',
                    'read',
                    'today',
                    'unread_today',
                    'by_type',
                ]
            ]);
    }

    /** @test */
    public function can_send_test_notification_in_development()
    {
        $this->app->detectEnvironment(fn() => 'local');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/test');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function cannot_send_test_notification_in_production()
    {
        $this->app->detectEnvironment(fn() => 'production');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/test');

        $response->assertStatus(403);
    }

    // ==============================
    // Notification Preferences Tests
    // ==============================

    /** @test */
    public function can_get_notification_preferences()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users/me/notification-preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'preferences',
                    'available_channels',
                ]
            ])
            ->assertJsonPath('data.preferences.new_comment.enabled', true);
    }

    /** @test */
    public function can_update_notification_preferences()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/users/me/notification-preferences', [
                'preferences' => [
                    'new_comment' => [
                        'enabled' => false,
                        'channels' => ['database'],
                    ],
                    'new_like_post' => [
                        'enabled' => true,
                        'channels' => ['database', 'broadcast'],
                    ],
                ]
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'new_comment',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function cannot_update_with_invalid_notification_type()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/users/me/notification-preferences', [
                'preferences' => [
                    'invalid_type' => [
                        'enabled' => true,
                        'channels' => ['database'],
                    ],
                ]
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_update_with_invalid_channel()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/users/me/notification-preferences', [
                'preferences' => [
                    'new_comment' => [
                        'enabled' => true,
                        'channels' => ['invalid_channel'],
                    ],
                ]
            ]);

        $response->assertStatus(422);
    }

    // ==============================
    // Notification Triggering Tests
    // ==============================

    /** @test */
    public function new_comment_triggers_notification_to_post_author()
    {
        Notification::fake();

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'status' => 'approved',
        ]);

        Notification::assertSentTo(
            $this->user,
            NewCommentNotification::class,
            function ($notification) {
                return $notification->comment->post_id === $this->post->id;
            }
        );
    }

    /** @test */
    public function new_reply_triggers_notification_to_parent_comment_author()
    {
        Notification::fake();

        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'parent_id' => $parentComment->id,
            'status' => 'approved',
        ]);

        Notification::assertSentTo(
            $this->user,
            NewCommentNotification::class,
            function ($notification) {
                return $notification->isReply === true;
            }
        );
    }

    /** @test */
    public function new_like_on_post_triggers_notification_to_author()
    {
        Notification::fake();

        $this->otherUser->likes()->create([
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        Notification::assertSentTo(
            $this->user,
            NewLikeNotification::class,
            function ($notification) {
                return $notification->likeable->id === $this->post->id;
            }
        );
    }

    /** @test */
    public function new_like_on_comment_triggers_notification_to_author()
    {
        Notification::fake();

        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $this->otherUser->likes()->create([
            'likeable_type' => Comment::class,
            'likeable_id' => $comment->id,
        ]);

        Notification::assertSentTo(
            $this->user,
            NewLikeNotification::class,
            function ($notification) {
                return $notification->likeable->id === $comment->id;
            }
        );
    }

    /** @test */
    public function mention_triggers_notification_to_mentioned_user()
    {
        Notification::fake();

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'content' => "Great post! What do you think @{$this->user->name}?",
            'status' => 'approved',
        ]);

        Notification::assertSentTo(
            $this->user,
            MentionNotification::class
        );
    }

    /** @test */
    public function user_does_not_get_notification_for_own_comment()
    {
        Notification::fake();

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        Notification::assertNotSentTo($this->user, NewCommentNotification::class);
    }

    /** @test */
    public function user_does_not_get_notification_for_own_like()
    {
        Notification::fake();

        $this->user->likes()->create([
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        Notification::assertNotSentTo($this->user, NewLikeNotification::class);
    }

    /** @test */
    public function post_published_triggers_notification_to_subscribers()
    {
        Notification::fake();

        // Create subscribed user
        $subscriber = User::factory()->create();
        $subscriber->subscription()->create([
            'status' => 'subscribed',
            'confirmed' => true,
        ]);

        $newPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Publish the post
        $newPost->update(['status' => 'published', 'published_at' => now()]);

        // Note: This may be queued, so we check the database
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $subscriber->id,
        ]);
    }

    // ==============================
    // Notification Preferences Enforcement Tests
    // ==============================

    /** @test */
    public function respects_user_preference_for_notification_type()
    {
        Notification::fake();

        // Disable new_comment notifications for user
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'new_comment',
            'channels' => [],
            'enabled' => false,
        ]);

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'status' => 'approved',
        ]);

        // User should not receive notification
        $this->assertEquals(0, $this->user->notifications()->count());
    }

    /** @test */
    public function respects_user_preference_for_channel()
    {
        // Create preference with only database channel
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'new_comment',
            'channels' => ['database'],
            'enabled' => true,
        ]);

        $notificationService = app(NotificationService::class);
        
        $shouldSendEmail = $notificationService->shouldSendNotification(
            $this->user,
            'new_comment',
            'email'
        );

        $this->assertFalse($shouldSendEmail);
    }

    // ==============================
    // Notification Service Tests
    // ==============================

    /** @test */
    public function notification_service_can_cleanup_old_notifications()
    {
        // Create old read notification
        $oldNotification = $this->user->notifications()->create([
            'type' => 'test',
            'data' => ['test' => 'data'],
            'read_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);

        // Create recent read notification
        $recentNotification = $this->user->notifications()->create([
            'type' => 'test',
            'data' => ['test' => 'data'],
            'read_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create unread notification (should not be deleted)
        $unreadNotification = $this->user->notifications()->create([
            'type' => 'test',
            'data' => ['test' => 'data'],
            'read_at' => null,
            'created_at' => now()->subDays(60),
        ]);

        $notificationService = app(NotificationService::class);
        $deleted = $notificationService->cleanupOldNotifications(30);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('notifications', ['id' => $oldNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unreadNotification->id]);
    }

    /** @test */
    public function notification_service_can_get_export_data()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notificationService = app(NotificationService::class);
        $exportData = $notificationService->getExportData($this->user);

        $this->assertArrayHasKey('notifications', $exportData);
        $this->assertArrayHasKey('total_count', $exportData);
        $this->assertArrayHasKey('exported_at', $exportData);
        $this->assertEquals(1, $exportData['total_count']);
    }

    /** @test */
    public function notification_service_can_delete_all_notifications()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));
        $this->user->notify(new NewLikeNotification($this->post, $this->otherUser));

        $notificationService = app(NotificationService::class);
        $deleted = $notificationService->deleteAllNotifications($this->user);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(0, $this->user->notifications()->count());
    }

    // ==============================
    // Notification Classes Tests
    // ==============================

    /** @test */
    public function new_comment_notification_has_correct_data()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Test comment content',
        ]);

        $notification = new NewCommentNotification($comment, $this->otherUser);

        $this->assertEquals('new_comment', $notification->getType());
        $this->assertStringContainsString($this->post->title, $notification->message);
        $this->assertStringContainsString($this->otherUser->name, $notification->message);
    }

    /** @test */
    public function new_like_notification_only_uses_database_channel()
    {
        $notification = new NewLikeNotification($this->post, $this->otherUser);

        $channels = $notification->via($this->user);

        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
    }

    /** @test */
    public function mention_notification_has_correct_context()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'content' => "Hey @{$this->user->name}, check this out!",
        ]);

        $notification = new MentionNotification($comment, $this->otherUser);

        $this->assertEquals('mention', $notification->getType());
        $this->assertStringContainsString($this->user->name, $notification->message);
    }

    /** @test */
    public function post_published_notification_has_correct_data()
    {
        $notification = new PostPublishedNotification($this->post, $this->user);

        $this->assertEquals('post_published', $notification->getType());
        $this->assertStringContainsString($this->post->title, $notification->message);
    }

    // ==============================
    // Broadcast Event Tests
    // ==============================

    /** @test */
    public function notification_broadcast_event_has_correct_data()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $notification = $this->user->notifications()->first();

        $event = new \App\Events\NotificationBroadcast(
            $this->user,
            $notification->data,
            'new_comment'
        );

        $broadcastData = $event->broadcastWith();

        $this->assertEquals($notification->id, $broadcastData['id']);
        $this->assertEquals('new_comment', $broadcastData['type']);
        $this->assertEquals($this->user->id, $broadcastData['user_id']);
    }

    /** @test */
    public function notification_broadcasts_on_correct_channels()
    {
        $event = new \App\Events\NotificationBroadcast(
            $this->user,
            ['test' => 'data'],
            'new_comment'
        );

        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertEquals('private', $channels[0]->name());
        $this->assertStringContainsString($this->user->id, $channels[0]->name());
    }

    // ==============================
    // Authorization Tests
    // ==============================

    /** @test */
    public function banned_user_cannot_receive_notifications()
    {
        $this->user->update(['status' => 'banned']);

        $notificationService = app(NotificationService::class);
        
        $shouldBroadcast = $notificationService->shouldSendNotification(
            $this->user,
            'new_comment',
            'broadcast'
        );

        // Banned users should not receive broadcast notifications
        $this->assertFalse($shouldBroadcast);
    }

    /** @test */
    public function notification_data_includes_action_url()
    {
        $this->user->notify(new NewCommentNotification(
            Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]),
            $this->otherUser
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        
        $notification = $response->json('data.0');
        $this->assertNotNull($notification['action_url']);
        $this->assertStringContainsString($this->post->slug, $notification['action_url']);
    }

    // ==============================
    // Edge Cases Tests
    // ==============================

    /** @test */
    public function handles_pagination_with_zero_results()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?read_status=read');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /** @test */
    public function handles_invalid_page_number()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?page=0');

        $response->assertStatus(422);
    }

    /** @test */
    public function handles_invalid_per_page_number()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications?per_page=200');

        $response->assertStatus(422);
    }

    /** @test */
    public function handles_non_uuid_notification_id()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/invalid-id');

        $response->assertStatus(404);
    }

    /** @test */
    public function notification_preferences_use_defaults_when_not_set()
    {
        $notificationService = app(NotificationService::class);

        // User has no preferences set, should use defaults
        $shouldSend = $notificationService->shouldSendNotification(
            $this->user,
            'new_comment',
            'database'
        );

        $this->assertTrue($shouldSend);
    }
}
