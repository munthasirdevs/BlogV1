<?php

namespace Tests\Feature\Newsletter;

use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Models\EmailCampaign;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use App\Services\NewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Class NewsletterCampaignTest
 *
 * Feature tests for newsletter campaign management.
 */
class NewsletterCampaignTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can create campaign.
     */
    public function test_can_create_campaign(): void
    {
        $service = app(NewsletterService::class);
        
        $campaign = $service->createCampaign(
            'Test Campaign',
            'Test Subject',
            'newsletter',
            ['html' => '<p>Test content</p>']
        );

        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'status' => 'draft',
        ]);
    }

    /**
     * Test can create A/B test campaign.
     */
    public function test_can_create_ab_test_campaign(): void
    {
        $service = app(NewsletterService::class);
        
        $campaign = $service->createAbTestCampaign(
            'A/B Test Campaign',
            'Subject A',
            'Subject B',
            50,
            10
        );

        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'A/B Test Campaign',
            'subject' => 'Subject A',
            'subject_b' => 'Subject B',
            'is_ab_test' => true,
        ]);
    }

    /**
     * Test can schedule campaign.
     */
    public function test_can_schedule_campaign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $campaign = EmailCampaign::factory()->create([
            'status' => 'draft',
        ]);

        $scheduledAt = now()->addHour();

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/campaigns/{$campaign->id}/schedule", [
                'scheduled_at' => $scheduledAt->toIso8601String(),
            ]);

        // Note: Schedule endpoint would need to be added if not using direct service
        $this->assertTrue($campaign->fresh()->isScheduled());
    }

    /**
     * Test campaign statistics.
     */
    public function test_can_get_campaign_stats(): void
    {
        $service = app(NewsletterService::class);
        
        $campaign = EmailCampaign::factory()->create([
            'sent_count' => 100,
            'delivered_count' => 95,
            'opened_count' => 50,
            'clicked_count' => 20,
            'bounced_count' => 5,
        ]);

        $stats = $service->getCampaignStats($campaign->id);

        $this->assertEquals(100, $stats['sent']);
        $this->assertEquals(95, $stats['delivered']);
        $this->assertEquals(50, $stats['opened']);
        $this->assertGreaterThan(0, $stats['open_rate']);
    }

    /**
     * Test can cancel campaign.
     */
    public function test_can_cancel_campaign(): void
    {
        $service = app(NewsletterService::class);
        
        $campaign = EmailCampaign::factory()->create([
            'status' => 'scheduled',
        ]);

        $result = $service->cancelCampaign($campaign->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test cannot cancel sent campaign.
     */
    public function test_cannot_cancel_sent_campaign(): void
    {
        $service = app(NewsletterService::class);
        
        $campaign = EmailCampaign::factory()->create([
            'status' => 'sent',
        ]);

        $result = $service->cancelCampaign($campaign->id);

        $this->assertFalse($result);
    }

    /**
     * Test segmented subscribers.
     */
    public function test_can_get_segmented_subscribers(): void
    {
        $service = app(NewsletterService::class);
        
        $category = Category::factory()->create();
        
        Subscription::factory()->count(5)->create([
            'frequency' => 'daily',
            'is_confirmed' => true,
            'is_active' => true,
            'preferences' => ['categories' => [$category->id]],
        ]);

        Subscription::factory()->count(3)->create([
            'frequency' => 'weekly',
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        $subscribers = $service->getSegmentedSubscribers([
            'frequency' => ['daily'],
        ]);

        $this->assertCount(5, $subscribers);
    }

    /**
     * Test engagement segmentation.
     */
    public function test_can_segment_by_engagement(): void
    {
        $subscription = Subscription::factory()->create([
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        // Create tracking with high engagement
        EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => 'newsletter',
            'open_count' => 10,
            'click_count' => 5,
            'opened_at' => now(),
            'clicked_at' => now(),
        ]);

        $service = app(NewsletterService::class);
        
        $subscribers = $service->getSegmentedSubscribers([
            'engagement' => 'high',
        ]);

        $this->assertGreaterThanOrEqual(1, $subscribers->count());
    }

    /**
     * Test digest email sending.
     */
    public function test_can_send_digest(): void
    {
        Queue::fake();
        
        $service = app(NewsletterService::class);
        
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Subscription::factory()->count(3)->create([
            'frequency' => 'daily',
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        $posts = [
            [
                'title' => $post->title,
                'url' => url('/blog/' . $post->slug),
                'excerpt' => $post->excerpt,
                'category' => $post->category->name,
            ],
        ];

        $count = $service->sendDigest('daily', $posts);

        $this->assertEquals(3, $count);
    }

    /**
     * Test new post notification.
     */
    public function test_can_send_new_post_notification(): void
    {
        Queue::fake();
        
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        Subscription::factory()->count(5)->create([
            'is_confirmed' => true,
            'is_active' => true,
            'preferences' => ['new_posts' => true],
        ]);

        $service = app(NewsletterService::class);
        $count = $service->sendNewPostNotification($post);

        $this->assertEquals(5, $count);
    }

    /**
     * Test new post notification with category filter.
     */
    public function test_new_post_notification_respects_category_preferences(): void
    {
        Queue::fake();
        
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $post = Post::factory()->create([
            'category_id' => $category1->id,
            'status' => 'published',
        ]);

        // Subscriber interested in category1
        Subscription::factory()->create([
            'is_confirmed' => true,
            'is_active' => true,
            'preferences' => [
                'new_posts' => true,
                'categories' => [$category1->id],
            ],
        ]);

        // Subscriber interested in category2 (should not receive)
        Subscription::factory()->create([
            'is_confirmed' => true,
            'is_active' => true,
            'preferences' => [
                'new_posts' => true,
                'categories' => [$category2->id],
            ],
        ]);

        $service = app(NewsletterService::class);
        $count = $service->sendNewPostNotification($post);

        $this->assertEquals(1, $count);
    }

    /**
     * Test email tracking record creation.
     */
    public function test_creates_tracking_on_email_send(): void
    {
        $subscription = Subscription::factory()->create();
        
        $tracking = EmailTracking::createTracking(
            $subscription->id,
            EmailTracking::TYPE_NEWSLETTER,
            'Test Subject'
        );

        $this->assertNotNull($tracking);
        $this->assertEquals($subscription->id, $tracking->subscription_id);
        $this->assertEquals(EmailTracking::TYPE_NEWSLETTER, $tracking->email_type);
    }

    /**
     * Test engagement score calculation.
     */
    public function test_engagement_score_calculation(): void
    {
        $tracking = EmailTracking::factory()->create([
            'open_count' => 5,
            'click_count' => 3,
            'opened_at' => now(),
            'clicked_at' => now(),
        ]);

        $score = $tracking->getEngagementScore();

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test excluded emails (bounced/complained).
     */
    public function test_excludes_bounced_emails(): void
    {
        $subscription = Subscription::factory()->create([
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        EmailTracking::create([
            'subscription_id' => $subscription->id,
            'email_type' => 'bounced',
            'bounce_type' => 'hard',
            'bounced_at' => now(),
        ]);

        $repo = new \App\Repositories\SubscriptionRepository();
        $excluded = $repo->getExcludedEmails();

        $this->assertContains($subscription->email, $excluded);
    }

    /**
     * Test campaign open rate calculation.
     */
    public function test_campaign_open_rate_calculation(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'delivered_count' => 100,
            'opened_count' => 45,
        ]);

        $openRate = $campaign->getOpenRate();

        $this->assertEquals(45.0, $openRate);
    }

    /**
     * Test campaign click rate calculation.
     */
    public function test_campaign_click_rate_calculation(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'delivered_count' => 100,
            'clicked_count' => 15,
        ]);

        $clickRate = $campaign->getClickRate();

        $this->assertEquals(15.0, $clickRate);
    }

    /**
     * Test campaign bounce rate calculation.
     */
    public function test_campaign_bounce_rate_calculation(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'sent_count' => 100,
            'bounced_count' => 5,
        ]);

        $bounceRate = $campaign->getBounceRate();

        $this->assertEquals(5.0, $bounceRate);
    }
}
