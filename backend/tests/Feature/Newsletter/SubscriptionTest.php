<?php

namespace Tests\Feature\Newsletter;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Models\EmailCampaign;
use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Class SubscriptionTest
 *
 * Feature tests for newsletter subscription flow.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can subscribe to newsletter.
     */
    public function test_user_can_subscribe(): void
    {
        Queue::fake();
        Mail::fake();

        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription created! Please check your email to confirm.',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'email' => 'subscriber@example.com',
            'is_confirmed' => false,
        ]);
    }

    /**
     * Test subscription requires valid email.
     */
    public function test_subscription_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test duplicate subscription handling.
     */
    public function test_duplicate_subscription_returns_existing(): void
    {
        Queue::fake();
        
        Subscription::factory()->create([
            'email' => 'existing@example.com',
            'is_confirmed' => true,
        ]);

        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'existing@example.com',
                    'is_confirmed' => true,
                ],
            ]);
    }

    /**
     * Test resubscription after unsubscribe.
     */
    public function test_can_resubscribe_after_unsubscribe(): void
    {
        Queue::fake();
        
        Subscription::factory()->create([
            'email' => 'resubscribe@example.com',
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'resubscribe@example.com',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('subscriptions', [
            'email' => 'resubscribe@example.com',
            'is_active' => true,
        ]);
    }

    /**
     * Test subscription with preferences.
     */
    public function test_subscription_with_preferences(): void
    {
        Queue::fake();
        
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'preferences@example.com',
            'preferences' => [
                'frequency' => 'weekly',
                'new_posts' => true,
                'categories' => [$category->id],
                'content_types' => ['articles', 'tutorials'],
            ],
        ]);

        $response->assertStatus(201);

        $subscription = Subscription::where('email', 'preferences@example.com')->first();
        
        $this->assertEquals('weekly', $subscription->frequency);
        $this->assertTrue($subscription->getPreference('new_posts'));
        $this->assertContains($category->id, $subscription->getPreference('categories', []));
    }

    /**
     * Test subscription confirmation.
     */
    public function test_can_confirm_subscription(): void
    {
        Queue::fake();
        Mail::fake();

        $subscription = Subscription::factory()->create([
            'email' => 'confirm@example.com',
            'is_confirmed' => false,
        ]);

        $response = $this->postJson("/api/v1/subscribe/confirm/{$subscription->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription confirmed successfully!',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'is_confirmed' => true,
        ]);
    }

    /**
     * Test confirmation with invalid token.
     */
    public function test_confirmation_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/subscribe/confirm/invalid-token');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired confirmation token. A new confirmation email has been sent.',
            ]);
    }

    /**
     * Test confirmation of already confirmed subscription.
     */
    public function test_confirmation_of_already_confirmed(): void
    {
        $subscription = Subscription::factory()->create([
            'is_confirmed' => true,
        ]);

        $response = $this->postJson("/api/v1/subscribe/confirm/{$subscription->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test unsubscribe by email.
     */
    public function test_can_unsubscribe_by_email(): void
    {
        Queue::fake();
        Mail::fake();

        $subscription = Subscription::factory()->create([
            'email' => 'unsubscribe@example.com',
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/unsubscribe', [
            'email' => 'unsubscribe@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'You have been unsubscribed. A confirmation email has been sent.',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test unsubscribe by token.
     */
    public function test_can_unsubscribe_by_token(): void
    {
        Queue::fake();
        Mail::fake();

        $subscription = Subscription::factory()->create([
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/unsubscribe', [
            'token' => $subscription->token,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test unsubscribe with non-existent email.
     */
    public function test_unsubscribe_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/unsubscribe', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Subscription not found.',
            ]);
    }

    /**
     * Test update preferences.
     */
    public function test_can_update_preferences(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $category = Category::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/v1/subscriptions/preferences', [
                'subscription_id' => $subscription->id,
                'preferences' => [
                    'frequency' => 'daily',
                    'new_posts' => false,
                    'weekly_digest' => true,
                    'categories' => [$category->id],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Preferences updated successfully.',
            ]);

        $subscription->refresh();
        $this->assertEquals('daily', $subscription->frequency);
        $this->assertFalse($subscription->getPreference('new_posts'));
        $this->assertTrue($subscription->getPreference('weekly_digest'));
    }

    /**
     * Test admin can list subscriptions.
     */
    public function test_admin_can_list_subscriptions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Subscription::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'email', 'is_confirmed', 'is_active'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    /**
     * Test admin can view subscription details.
     */
    public function test_admin_can_view_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscription = Subscription::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $subscription->id,
                    'email' => $subscription->email,
                ],
            ]);
    }

    /**
     * Test admin can delete subscription.
     */
    public function test_admin_can_delete_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscription = Subscription::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription deleted successfully.',
            ]);

        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * Test get subscriber segments.
     */
    public function test_can_get_subscriber_segments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Subscription::factory()->count(3)->create(['frequency' => 'daily', 'is_confirmed' => true]);
        Subscription::factory()->count(5)->create(['frequency' => 'weekly', 'is_confirmed' => true]);
        Subscription::factory()->count(2)->create(['frequency' => 'monthly', 'is_confirmed' => true]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/subscribers/segments');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'confirmed_active' => 10,
                    'daily_digest' => 3,
                    'weekly_digest' => 5,
                    'monthly_digest' => 2,
                ],
            ]);
    }

    /**
     * Test get subscriber statistics.
     */
    public function test_can_get_subscriber_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Subscription::factory()->count(10)->create(['is_confirmed' => true]);
        Subscription::factory()->count(3)->create(['is_confirmed' => false]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/subscribers/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 13,
                    'confirmed' => 10,
                ],
            ]);
    }

    /**
     * Test email open tracking.
     */
    public function test_can_track_email_open(): void
    {
        $subscription = Subscription::factory()->create();
        $tracking = EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => 'newsletter',
            'sent_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/track/open/{$subscription->id}/{$tracking->id}");

        $response->assertStatus(204);

        $tracking->refresh();
        $this->assertEquals(1, $tracking->open_count);
        $this->assertNotNull($tracking->opened_at);
    }

    /**
     * Test email click tracking.
     */
    public function test_can_track_link_click(): void
    {
        $subscription = Subscription::factory()->create();
        $tracking = EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => 'newsletter',
            'sent_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/track/click/{$subscription->id}/link123?email_id={$tracking->id}&url=" . urlencode('https://example.com'));

        $response->assertRedirect('https://example.com');

        $tracking->refresh();
        $this->assertEquals(1, $tracking->click_count);
    }

    /**
     * Test bounce webhook handling.
     */
    public function test_can_handle_bounce_webhook(): void
    {
        $subscription = Subscription::factory()->create([
            'email' => 'bounce@example.com',
        ]);

        $response = $this->postJson('/api/v1/webhooks/mail/bounce', [
            'event' => 'bounced',
            'recipient' => 'bounce@example.com',
            'severity' => 'permanent',
            'reason' => 'Mailbox does not exist',
            'timestamp' => time(),
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('email_trackings', [
            'subscription_id' => $subscription->id,
            'bounce_type' => 'hard',
        ]);
    }

    /**
     * Test complaint webhook handling.
     */
    public function test_can_handle_complaint_webhook(): void
    {
        $subscription = Subscription::factory()->create([
            'email' => 'complaint@example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/webhooks/mail/complaint', [
            [
                'event' => 'spamreport',
                'email' => 'complaint@example.com',
                'timestamp' => time(),
            ],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertFalse($subscription->is_active);
    }

    /**
     * Test GDPR data export.
     */
    public function test_can_export_subscriber_data(): void
    {
        $subscription = Subscription::factory()->create([
            'email' => 'gdpr@example.com',
        ]);

        $response = $this->postJson('/api/v1/subscriptions/export', [
            'email' => 'gdpr@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'gdpr@example.com',
                ],
            ]);
    }

    /**
     * Test GDPR data deletion.
     */
    public function test_can_delete_subscriber_data(): void
    {
        $subscription = Subscription::factory()->create([
            'email' => 'delete@example.com',
        ]);

        $response = $this->deleteJson('/api/v1/subscriptions/delete', [
            'email' => 'delete@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Your data has been deleted.',
            ]);

        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * Test resend confirmation email.
     */
    public function test_can_resend_confirmation(): void
    {
        Queue::fake();
        Mail::fake();

        $subscription = Subscription::factory()->create([
            'email' => 'resend@example.com',
            'is_confirmed' => false,
        ]);

        $response = $this->postJson('/api/v1/subscribe/resend', [
            'email' => 'resend@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Confirmation email sent. Please check your inbox.',
            ]);
    }

    /**
     * Test cannot subscribe bounced email.
     */
    public function test_cannot_subscribe_hard_bounced_email(): void
    {
        $subscription = Subscription::factory()->create([
            'email' => 'hardbounce@example.com',
        ]);

        EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => 'bounced',
            'bounce_type' => 'hard',
            'bounced_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/subscribe', [
            'email' => 'hardbounce@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
