<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsDailyStat;
use App\Models\PostViewSummary;
use App\Models\ActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Class AnalyticsEndpointsTest
 *
 * Feature tests for analytics API endpoints.
 * Tests all analytics endpoints for proper functionality,
 * authorization, and data accuracy.
 */
class AnalyticsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $editorUser;
    protected User $regularUser;
    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->adminUser = User::factory()->create(['email' => 'admin@test.com']);
        $this->adminUser->assignRole('admin');

        $this->editorUser = User::factory()->create(['email' => 'editor@test.com']);
        $this->editorUser->assignRole('editor');

        $this->regularUser = User::factory()->create(['email' => 'user@test.com']);
        $this->regularUser->assignRole('user');
    }

    /**
     * Test overview endpoint requires authentication.
     */
    public function test_overview_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analytics/overview');

        $response->assertStatus(401);
    }

    /**
     * Test overview endpoint requires admin or editor role.
     */
    public function test_overview_endpoint_requires_admin_or_editor_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/analytics/overview');

        $response->assertStatus(403);
    }

    /**
     * Test overview endpoint returns data for admin.
     */
    public function test_overview_endpoint_returns_data_for_admin(): void
    {
        // Create some test data
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/overview');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_page_views' => 10,
                    ],
                ],
            ]);
    }

    /**
     * Test overview endpoint accepts date range parameters.
     */
    public function test_overview_endpoint_accepts_date_range(): void
    {
        $this->createTestAnalyticsData();

        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/analytics/overview?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test views endpoint returns views over time.
     */
    public function test_views_endpoint_returns_views_over_time(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/views');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'views' => [],
                    'group_by' => 'daily',
                ],
            ]);
    }

    /**
     * Test views endpoint supports grouping.
     */
    public function test_views_endpoint_supports_grouping(): void
    {
        $this->createTestAnalyticsData();

        foreach (['daily', 'weekly', 'monthly'] as $groupBy) {
            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/analytics/views?group_by={$groupBy}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'group_by' => $groupBy,
                    ],
                ]);
        }
    }

    /**
     * Test traffic endpoint returns traffic data.
     */
    public function test_traffic_endpoint_returns_traffic_data(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/traffic');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test posts endpoint returns post performance.
     */
    public function test_posts_endpoint_returns_post_performance(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/posts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test top posts endpoint returns sorted posts.
     */
    public function test_top_posts_endpoint_returns_sorted_posts(): void
    {
        $this->createTestAnalyticsData();

        foreach (['views', 'unique_views', 'engagement'] as $sortBy) {
            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/analytics/posts/top?sort_by={$sortBy}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
        }
    }

    /**
     * Test top posts endpoint supports limit parameter.
     */
    public function test_top_posts_endpoint_supports_limit(): void
    {
        $this->createTestAnalyticsData();

        foreach ([10, 20, 50] as $limit) {
            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/analytics/posts/top?limit={$limit}");

            $response->assertStatus(200);
        }
    }

    /**
     * Test engagement endpoint returns engagement metrics.
     */
    public function test_engagement_endpoint_returns_engagement_metrics(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/engagement');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test sources endpoint returns traffic sources.
     */
    public function test_sources_endpoint_returns_traffic_sources(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/sources');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test geo endpoint returns geographic data.
     */
    public function test_geo_endpoint_returns_geographic_data(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/geo');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test devices endpoint returns device breakdown.
     */
    public function test_devices_endpoint_returns_device_breakdown(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/devices');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test realtime endpoint returns active users.
     */
    public function test_realtime_endpoint_returns_active_users(): void
    {
        // Create active session
        ActiveSession::create([
            'session_id' => 'test-session-123',
            'current_url' => '/posts/test',
            'current_page_title' => 'Test Post',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/realtime');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test audience endpoint returns audience insights.
     */
    public function test_audience_endpoint_returns_audience_insights(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/audience');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test export endpoint returns export data.
     */
    public function test_export_endpoint_returns_export_data(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/analytics/export');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test export endpoint supports format parameter.
     */
    public function test_export_endpoint_supports_format_parameter(): void
    {
        $this->createTestAnalyticsData();

        foreach (['json', 'csv'] as $format) {
            $response = $this->actingAs($this->adminUser)
                ->getJson("/api/v1/analytics/export?format={$format}");

            $response->assertStatus(200);
        }
    }

    /**
     * Test editor can access analytics endpoints.
     */
    public function test_editor_can_access_analytics_endpoints(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->editorUser)
            ->getJson('/api/v1/analytics/overview');

        $response->assertStatus(200);
    }

    /**
     * Test cache clear endpoint.
     */
    public function test_cache_clear_endpoint(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/cache/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test cache warm endpoint.
     */
    public function test_cache_warm_endpoint(): void
    {
        $this->createTestAnalyticsData();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/cache/warm');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Create test analytics data.
     */
    protected function createTestAnalyticsData(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // Create analytics events for the past 30 days
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i);

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
                'referrer_domain' => $i % 4 === 0 ? null : 'google.com',
                'occurred_at' => $date,
            ]);
        }
    }
}
