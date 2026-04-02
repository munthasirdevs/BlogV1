<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\AnalyticsEvent;
use App\Models\PostView;
use App\Models\PostViewSummary;
use App\Models\AnalyticsDailyStat;
use App\Models\ActiveSession;
use App\Jobs\AggregatePostViews;
use App\Jobs\CleanupAnalytics;
use App\Services\AnalyticsService;
use App\Repositories\AnalyticsRepository;
use App\Helpers\UserAgentParser;
use App\Helpers\GeoLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Class AnalyticsModelsTest
 *
 * Feature tests for analytics models and their functionality.
 */
class AnalyticsModelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AnalyticsEvent model can be created.
     */
    public function test_analytics_event_model_can_be_created(): void
    {
        $event = AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'test-session-123',
            'visitor_fingerprint' => 'test-fingerprint',
            'is_new_visitor' => true,
            'url' => '/test-page',
        ]);

        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'event_type' => 'page_view',
        ]);
    }

    /**
     * Test AnalyticsEvent scopes work correctly.
     */
    public function test_analytics_event_scopes(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        // Create events with different types
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'session-1',
            'occurred_at' => now(),
        ]);

        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_POST_VIEW,
            'session_id' => 'session-2',
            'occurred_at' => now(),
        ]);

        // Test page views scope
        $pageViews = AnalyticsEvent::pageViews()->count();
        $this->assertEquals(1, $pageViews);

        // Test post views scope
        $postViews = AnalyticsEvent::postViews()->count();
        $this->assertEquals(1, $postViews);

        // Test date range scope
        $inRange = AnalyticsEvent::dateRange($startDate, $endDate)->count();
        $this->assertEquals(2, $inRange);

        // Test new visitors scope
        AnalyticsEvent::create([
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'session_id' => 'session-3',
            'is_new_visitor' => true,
            'occurred_at' => now(),
        ]);

        $newVisitors = AnalyticsEvent::newVisitors()->count();
        $this->assertEquals(1, $newVisitors);
    }

    /**
     * Test visitor fingerprint generation.
     */
    public function test_visitor_fingerprint_generation(): void
    {
        $fingerprint1 = AnalyticsEvent::generateVisitorFingerprint('192.168.1.1', 'Mozilla/5.0');
        $fingerprint2 = AnalyticsEvent::generateVisitorFingerprint('192.168.1.1', 'Mozilla/5.0');
        $fingerprint3 = AnalyticsEvent::generateVisitorFingerprint('192.168.1.2', 'Mozilla/5.0');

        $this->assertEquals($fingerprint1, $fingerprint2);
        $this->assertNotEquals($fingerprint1, $fingerprint3);
        $this->assertEquals(64, strlen($fingerprint1)); // SHA256 produces 64 char hex
    }

    /**
     * Test IP address hashing.
     */
    public function test_ip_address_hashing(): void
    {
        $hash1 = AnalyticsEvent::hashIpAddress('192.168.1.1');
        $hash2 = AnalyticsEvent::hashIpAddress('192.168.1.1');
        $hash3 = AnalyticsEvent::hashIpAddress('192.168.1.2');

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertNotNull($hash1);
    }

    /**
     * Test traffic source categorization.
     */
    public function test_traffic_source_categorization(): void
    {
        // Direct traffic
        $this->assertEquals('direct', AnalyticsEvent::categorizeTrafficSource(null, 'https://example.com'));
        $this->assertEquals('direct', AnalyticsEvent::categorizeTrafficSource('https://example.com/page', 'https://example.com/other'));

        // Organic search
        $this->assertEquals('organic', AnalyticsEvent::categorizeTrafficSource('https://google.com/search', 'https://example.com'));
        $this->assertEquals('organic', AnalyticsEvent::categorizeTrafficSource('https://bing.com/search', 'https://example.com'));

        // Social media
        $this->assertEquals('social', AnalyticsEvent::categorizeTrafficSource('https://facebook.com/post', 'https://example.com'));
        $this->assertEquals('social', AnalyticsEvent::categorizeTrafficSource('https://twitter.com/post', 'https://example.com'));

        // Referral
        $this->assertEquals('referral', AnalyticsEvent::categorizeTrafficSource('https://other-site.com/link', 'https://example.com'));
    }

    /**
     * Test PostViewSummary model.
     */
    public function test_post_view_summary_model(): void
    {
        $post = Post::factory()->create();

        $summary = PostViewSummary::create([
            'post_id' => $post->id,
            'view_date' => now()->toDateString(),
            'total_views' => 100,
            'unique_views' => 80,
            'new_visitors' => 30,
            'returning_visitors' => 50,
            'referrer_breakdown' => ['direct' => 40, 'organic' => 60],
            'device_breakdown' => ['desktop' => 60, 'mobile' => 40],
        ]);

        $this->assertDatabaseHas('post_views_summary', [
            'post_id' => $post->id,
            'total_views' => 100,
        ]);

        // Test upsert
        $summary = PostViewSummary::upsertSummary(
            $post->id,
            now()->toDateString(),
            150,
            100,
            40,
            60,
            ['direct' => 50, 'organic' => 100]
        );

        $this->assertEquals(150, $summary->total_views);
    }

    /**
     * Test AnalyticsDailyStat model.
     */
    public function test_analytics_daily_stat_model(): void
    {
        $stat = AnalyticsDailyStat::create([
            'stat_date' => now()->toDateString(),
            'total_page_views' => 1000,
            'unique_visitors' => 500,
            'new_visitors' => 200,
            'returning_visitors' => 300,
            'bounce_rate' => 45.5,
            'traffic_sources' => ['direct' => 400, 'organic' => 600],
        ]);

        $this->assertDatabaseHas('analytics_daily_stats', [
            'stat_date' => now()->toDateString(),
            'total_page_views' => 1000,
        ]);

        // Test getForDate
        $retrieved = AnalyticsDailyStat::getForDate(now()->toDateString());
        $this->assertNotNull($retrieved);
        $this->assertEquals(1000, $retrieved->total_page_views);
    }

    /**
     * Test ActiveSession model.
     */
    public function test_active_session_model(): void
    {
        $session = ActiveSession::create([
            'session_id' => 'test-session-123',
            'current_url' => '/posts/test',
            'current_page_title' => 'Test Post',
            'last_seen_at' => now(),
            'page_views' => 5,
            'country' => 'US',
            'device_type' => 'desktop',
        ]);

        $this->assertDatabaseHas('active_sessions', [
            'session_id' => 'test-session-123',
        ]);

        // Test active scope
        $active = ActiveSession::active()->count();
        $this->assertEquals(1, $active);

        // Test isActive method
        $this->assertTrue($session->isActive());

        // Test touchActivity
        $session->touchActivity('/new-page', 'New Page');
        $this->assertEquals(6, $session->fresh()->page_views);
    }

    /**
     * Test ActiveSession cleanup.
     */
    public function test_active_session_cleanup(): void
    {
        // Create expired session
        ActiveSession::create([
            'session_id' => 'expired-session',
            'last_seen_at' => now()->subMinutes(30),
        ]);

        // Create active session
        ActiveSession::create([
            'session_id' => 'active-session',
            'last_seen_at' => now(),
        ]);

        // Cleanup
        $deleted = ActiveSession::cleanupExpired(5);
        $this->assertEquals(1, $deleted);

        $this->assertDatabaseMissing('active_sessions', [
            'session_id' => 'expired-session',
        ]);

        $this->assertDatabaseHas('active_sessions', [
            'session_id' => 'active-session',
        ]);
    }

    /**
     * Test AnalyticsEvent record methods.
     */
    public function test_analytics_event_record_methods(): void
    {
        $event = AnalyticsEvent::recordPageView(
            '/test-page',
            'session-123',
            null,
            'https://google.com'
        );

        $this->assertEquals(AnalyticsEvent::TYPE_PAGE_VIEW, $event->event_type);
        $this->assertEquals('session-123', $event->session_id);
    }

    /**
     * Test AnalyticsEvent getDailyViews.
     */
    public function test_analytics_event_get_daily_views(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        // Create events for different days
        for ($i = 0; $i < 5; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'session_id' => 'session-' . $i,
                'occurred_at' => now()->subDays($i),
            ]);
        }

        $dailyViews = AnalyticsEvent::getDailyViews($startDate, $endDate);
        $this->assertGreaterThanOrEqual(1, $dailyViews->count());
    }

    /**
     * Test AnalyticsEvent getBounceRate.
     */
    public function test_analytics_event_get_bounce_rate(): void
    {
        // Create sessions with single view (bounces)
        for ($i = 0; $i < 5; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'session_id' => 'bounce-session-' . $i,
                'occurred_at' => now(),
            ]);
        }

        // Create sessions with multiple views (not bounces)
        for ($i = 0; $i < 5; $i++) {
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'session_id' => 'multi-session-' . $i,
                'occurred_at' => now(),
            ]);
            AnalyticsEvent::create([
                'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
                'session_id' => 'multi-session-' . $i,
                'occurred_at' => now()->addMinute(),
            ]);
        }

        $bounceRate = AnalyticsEvent::getBounceRate(now()->subHour(), now());
        $this->assertGreaterThan(0, $bounceRate);
        $this->assertLessThan(100, $bounceRate);
    }

    /**
     * Test UserAgentParser helper.
     */
    public function test_user_agent_parser(): void
    {
        $chromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $firefoxUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0';
        $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1';

        // Test Chrome detection
        $chromeParsed = UserAgentParser::parse($chromeUa);
        $this->assertEquals('Chrome', $chromeParsed['browser']);
        $this->assertEquals('desktop', $chromeParsed['device_type']);

        // Test Firefox detection
        $firefoxParsed = UserAgentParser::parse($firefoxUa);
        $this->assertEquals('Firefox', $firefoxParsed['browser']);

        // Test mobile detection
        $mobileParsed = UserAgentParser::parse($mobileUa);
        $this->assertTrue($mobileParsed['is_mobile']);
        $this->assertEquals('mobile', $mobileParsed['device_type']);

        // Test bot detection
        $botUa = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        $botParsed = UserAgentParser::parse($botUa);
        $this->assertTrue($botParsed['is_bot']);
    }

    /**
     * Test GeoLocation helper.
     */
    public function test_geo_location_helper(): void
    {
        // Test private IP handling
        $localLocation = GeoLocation::getLocation('127.0.0.1');
        $this->assertEquals('Local', $localLocation['country']);

        // Test private IP detection
        $this->assertTrue(GeoLocation::isPrivateIp('127.0.0.1'));
        $this->assertTrue(GeoLocation::isPrivateIp('192.168.1.1'));
        $this->assertTrue(GeoLocation::isPrivateIp('10.0.0.1'));
        $this->assertFalse(GeoLocation::isPrivateIp('8.8.8.8'));
    }
}
