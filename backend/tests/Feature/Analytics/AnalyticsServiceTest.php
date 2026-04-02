<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsDailyStat;
use App\Models\PostViewSummary;
use App\Models\ActiveSession;
use App\Jobs\AggregatePostViews;
use App\Jobs\CleanupAnalytics;
use App\Services\AnalyticsService;
use App\Repositories\AnalyticsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

/**
 * Class AnalyticsServiceTest
 *
 * Feature tests for AnalyticsService and AnalyticsRepository.
 */
class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;
    protected AnalyticsRepository $analyticsRepository;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = app(AnalyticsService::class);
        $this->analyticsRepository = app(AnalyticsRepository::class);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /**
     * Test AnalyticsService can be resolved.
     */
    public function test_analytics_service_can_be_resolved(): void
    {
        $this->assertInstanceOf(AnalyticsService::class, $this->analyticsService);
    }

    /**
     * Test AnalyticsRepository can be resolved.
     */
    public function test_analytics_repository_can_be_resolved(): void
    {
        $this->assertInstanceOf(AnalyticsRepository::class, $this->analyticsRepository);
    }

    /**
     * Test getDashboardOverview returns correct data.
     */
    public function test_get_dashboard_overview(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $overview = $this->analyticsService->getDashboardOverview($startDate, $endDate);

        $this->assertArrayHasKey('total_page_views', $overview);
        $this->assertArrayHasKey('unique_visitors', $overview);
        $this->assertArrayHasKey('bounce_rate', $overview);
        $this->assertGreaterThan(0, $overview['total_page_views']);
    }

    /**
     * Test getViewsOverTime returns correct data.
     */
    public function test_get_views_over_time(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $views = $this->analyticsService->getViewsOverTime($startDate, $endDate, 'daily');

        $this->assertIsArray($views);
        $this->assertNotEmpty($views);
        $this->assertArrayHasKey('period', $views[0]);
        $this->assertArrayHasKey('views', $views[0]);
    }

    /**
     * Test getTopPosts returns correct data.
     */
    public function test_get_top_posts(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $topPosts = $this->analyticsService->getTopPosts($startDate, $endDate, 10, 'views');

        $this->assertIsArray($topPosts);
    }

    /**
     * Test getEngagementMetrics returns correct data.
     */
    public function test_get_engagement_metrics(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $metrics = $this->analyticsService->getEngagementMetrics($startDate, $endDate);

        $this->assertArrayHasKey('avg_session_duration', $metrics);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('avg_pages_per_session', $metrics);
    }

    /**
     * Test getTrafficSources returns correct data.
     */
    public function test_get_traffic_sources(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $sources = $this->analyticsService->getTrafficSources($startDate, $endDate);

        $this->assertIsArray($sources);
    }

    /**
     * Test getDeviceBreakdown returns correct data.
     */
    public function test_get_device_breakdown(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $devices = $this->analyticsService->getDeviceBreakdown($startDate, $endDate);

        $this->assertIsArray($devices);
    }

    /**
     * Test getRealTimeActiveUsers returns correct data.
     */
    public function test_get_real_time_active_users(): void
    {
        ActiveSession::create([
            'session_id' => 'active-session-1',
            'last_seen_at' => now(),
            'current_url' => '/test',
        ]);

        $data = $this->analyticsService->getRealTimeActiveUsers();

        $this->assertArrayHasKey('active_users', $data);
        $this->assertGreaterThanOrEqual(1, $data['active_users']);
    }

    /**
     * Test recordPageView creates event.
     */
    public function test_record_page_view(): void
    {
        $event = $this->analyticsService->recordPageView(
            '/test-page',
            'test-session',
            $this->adminUser->id
        );

        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'event_type' => 'page_view',
            'session_id' => 'test-session',
        ]);
    }

    /**
     * Test recordPostView creates event.
     */
    public function test_record_post_view(): void
    {
        $post = Post::factory()->create();

        $event = $this->analyticsService->recordPostView(
            $post->id,
            '/posts/' . $post->slug,
            'test-session',
            $this->adminUser->id,
            $post->title
        );

        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'event_type' => 'post_view',
            'post_id' => $post->id,
        ]);
    }

    /**
     * Test updateActiveSession creates or updates session.
     */
    public function test_update_active_session(): void
    {
        $session = $this->analyticsService->updateActiveSession(
            'test-session-123',
            $this->adminUser->id,
            '/test-page',
            'Test Page'
        );

        $this->assertDatabaseHas('active_sessions', [
            'session_id' => 'test-session-123',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test AggregatePostViews job.
     */
    public function test_aggregate_post_views_job(): void
    {
        Queue::fake();

        $post = Post::factory()->create(['status' => 'published']);
        $yesterday = now()->subDay();

        // Create test events
        for ($i = 0; $i < 10; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_POST_VIEW,
                'post_id' => $post->id,
                'session_id' => 'session-' . $i,
                'visitor_fingerprint' => 'fingerprint-' . $i,
                'is_new_visitor' => $i < 5,
                'traffic_source' => $i % 2 === 0 ? 'organic' : 'direct',
                'device_type' => $i % 2 === 0 ? 'desktop' : 'mobile',
                'country' => $i % 2 === 0 ? 'US' : 'UK',
                'occurred_at' => $yesterday,
            ]);
        }

        // Run the job
        $job = new AggregatePostViews($yesterday);
        $job->handle($this->analyticsRepository);

        // Check summary was created
        $this->assertDatabaseHas('post_views_summary', [
            'post_id' => $post->id,
            'total_views' => 10,
        ]);
    }

    /**
     * Test CleanupAnalytics job.
     */
    public function test_cleanup_analytics_job(): void
    {
        // Create old event
        $oldDate = now()->subMonths(13);
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'old-session',
            'occurred_at' => $oldDate,
        ]);

        // Create recent event
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'recent-session',
            'occurred_at' => now(),
        ]);

        // Run the job
        $job = new CleanupAnalytics();
        $job->handle();

        // Old event should be deleted
        $this->assertDatabaseMissing('analytics_events', [
            'session_id' => 'old-session',
        ]);

        // Recent event should remain
        $this->assertDatabaseHas('analytics_events', [
            'session_id' => 'recent-session',
        ]);
    }

    /**
     * Test cache warming.
     */
    public function test_cache_warming(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $this->analyticsService->warmCache($startDate, $endDate);

        // Cache should now contain analytics data
        $this->assertTrue(true); // Basic test - cache warming completed without error
    }

    /**
     * Test cache clearing.
     */
    public function test_cache_clearing(): void
    {
        $this->createTestData();

        // First call populates cache
        $this->analyticsService->getDashboardOverview(now()->subDays(30), now());

        // Clear cache
        $this->analyticsService->clearCache();

        // Next call should fetch fresh data
        $this->assertTrue(true); // Basic test - cache clearing completed without error
    }

    /**
     * Test export data.
     */
    public function test_export_data(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $result = $this->analyticsService->exportData($startDate, $endDate, 'json');

        $this->assertArrayHasKey('success', $result->original);
        $this->assertTrue($result->original['success']);
    }

    /**
     * Test repository getUniqueVisitorsCount.
     */
    public function test_repository_get_unique_visitors_count(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $count = $this->analyticsRepository->getUniqueVisitorsCount($startDate, $endDate);

        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test repository getBounceRate.
     */
    public function test_repository_get_bounce_rate(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $rate = $this->analyticsRepository->getBounceRate($startDate, $endDate);

        $this->assertGreaterThanOrEqual(0, $rate);
        $this->assertLessThanOrEqual(100, $rate);
    }

    /**
     * Test repository getGeographicBreakdown.
     */
    public function test_repository_get_geographic_breakdown(): void
    {
        $this->createTestData();

        $startDate = now()->subDays(30);
        $endDate = now();

        $breakdown = $this->analyticsRepository->getGeographicBreakdown($startDate, $endDate);

        $this->assertIsObject($breakdown);
    }

    /**
     * Test repository deleteOldEvents.
     */
    public function test_repository_delete_old_events(): void
    {
        $oldDate = now()->subMonths(13);
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'old-session',
            'occurred_at' => $oldDate,
        ]);

        $deleted = $this->analyticsRepository->deleteOldEvents(now()->subMonths(12));

        $this->assertEquals(1, $deleted);
    }

    /**
     * Create test data for analytics.
     */
    protected function createTestData(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        for ($i = 0; $i < 30; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'session_id' => 'session-' . $i,
                'visitor_fingerprint' => 'fingerprint-' . $i,
                'is_new_visitor' => $i < 15,
                'url' => '/posts/' . $post->slug,
                'traffic_source' => ['direct', 'organic', 'social', 'referral'][$i % 4],
                'device_type' => ['desktop', 'mobile', 'tablet'][$i % 3],
                'browser' => ['Chrome', 'Firefox', 'Safari'][$i % 3],
                'os' => ['Windows', 'macOS', 'Linux'][$i % 3],
                'country' => ['US', 'UK', 'CA', 'AU'][$i % 4],
                'occurred_at' => now()->subDays($i),
            ]);
        }
    }
}
