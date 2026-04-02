# Phase 11: Analytics & Tracking System - API Documentation

## Overview

The Analytics & Tracking System provides comprehensive analytics collection, aggregation, and dashboard metrics for the blog platform. It includes event tracking, visitor analytics, traffic analysis, and reporting capabilities.

## Access Control

All analytics endpoints require authentication and are restricted to users with **Admin** or **Editor** roles.

## Base URL

```
/api/v1/analytics
```

---

## Endpoints

### 1. Dashboard Overview

Get summary metrics for the dashboard.

**Endpoint:** `GET /api/v1/analytics/overview`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_page_views": 15420,
      "unique_visitors": 8234,
      "new_visitors": 4521,
      "returning_visitors": 3713,
      "total_sessions": 9876,
      "avg_session_duration": 245.67,
      "avg_pages_per_session": 3.45,
      "bounce_rate": 42.5,
      "total_posts_viewed": 156
    },
    "period": {
      "start_date": "2026-03-03",
      "end_date": "2026-04-02",
      "days": 30
    }
  }
}
```

---

### 2. Views Over Time

Get page views over a time period with grouping options.

**Endpoint:** `GET /api/v1/analytics/views`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |
| group_by | string | No | daily | Grouping: daily, weekly, monthly |

**Response:**
```json
{
  "success": true,
  "data": {
    "views": [
      {
        "period": "2026-04-01",
        "views": 523,
        "unique_views": 412,
        "unique_visitors": 389
      },
      {
        "period": "2026-04-02",
        "views": 612,
        "unique_views": 487,
        "unique_visitors": 456
      }
    ],
    "group_by": "daily",
    "period": {
      "start_date": "2026-04-01",
      "end_date": "2026-04-02"
    }
  }
}
```

---

### 3. Traffic Data

Get comprehensive traffic data including sources and referrers.

**Endpoint:** `GET /api/v1/analytics/traffic`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "overview": { ... },
    "views_over_time": [...],
    "traffic_sources": {
      "direct": 4500,
      "organic": 6200,
      "social": 2100,
      "referral": 1800,
      "email": 820
    },
    "top_referrers": [
      {"domain": "google.com", "count": 3200},
      {"domain": "facebook.com", "count": 1500},
      {"domain": "twitter.com", "count": 890}
    ]
  }
}
```

---

### 4. Post Performance

Get post performance analytics.

**Endpoint:** `GET /api/v1/analytics/posts`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "top_by_views": [...],
    "top_by_unique": [...],
    "top_by_engagement": [...]
  }
}
```

---

### 5. Top Posts

Get top performing posts with sorting options.

**Endpoint:** `GET /api/v1/analytics/posts/top`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |
| sort_by | string | No | views | Sort: views, unique_views, engagement |
| limit | integer | No | 10 | Limit: 10, 20, 50 |

**Response:**
```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "post_id": 1,
        "post_title": "Getting Started with Laravel",
        "post_slug": "getting-started-with-laravel",
        "views": 1523,
        "unique_views": 1245,
        "logged_in_views": 456,
        "engagement_score": 2890.5,
        "post": {
          "title": "Getting Started with Laravel",
          "slug": "getting-started-with-laravel",
          "status": "published",
          "published_at": "2026-03-15T10:00:00Z",
          "thumbnail_url": "/storage/thumbnails/post-1.jpg"
        }
      }
    ],
    "sort_by": "views",
    "limit": 10,
    "period": {
      "start_date": "2026-03-03",
      "end_date": "2026-04-02"
    }
  }
}
```

---

### 6. Engagement Metrics

Get user engagement metrics.

**Endpoint:** `GET /api/v1/analytics/engagement`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "avg_session_duration": 245.67,
    "avg_session_duration_formatted": "4m 5s",
    "avg_pages_per_session": 3.45,
    "bounce_rate": 42.5,
    "total_sessions": 9876,
    "total_page_views": 15420
  }
}
```

---

### 7. Traffic Sources

Get traffic source breakdown with percentages.

**Endpoint:** `GET /api/v1/analytics/sources`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "sources": [
      {
        "source": "organic",
        "count": 6200,
        "percentage": 40.26
      },
      {
        "source": "direct",
        "count": 4500,
        "percentage": 29.22
      },
      {
        "source": "social",
        "count": 2100,
        "percentage": 13.64
      }
    ],
    "top_referrers": [
      {"domain": "google.com", "count": 3200},
      {"domain": "facebook.com", "count": 1500}
    ],
    "total": 15400,
    "period": {
      "start_date": "2026-03-03",
      "end_date": "2026-04-02"
    }
  }
}
```

---

### 8. Geographic Data

Get visitor geographic distribution.

**Endpoint:** `GET /api/v1/analytics/geo`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |
| limit | integer | No | 20 | Max countries to return (max 100) |

**Response:**
```json
{
  "success": true,
  "data": {
    "countries": [
      {
        "country": "US",
        "count": 5200,
        "percentage": 33.76
      },
      {
        "country": "UK",
        "count": 2800,
        "percentage": 18.18
      },
      {
        "country": "CA",
        "count": 1500,
        "percentage": 9.74
      }
    ],
    "total": 15400,
    "period": {
      "start_date": "2026-03-03",
      "end_date": "2026-04-02"
    }
  }
}
```

---

### 9. Device Breakdown

Get device, browser, and OS breakdown.

**Endpoint:** `GET /api/v1/analytics/devices`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "devices": [
      {"type": "desktop", "count": 9240, "percentage": 60.0},
      {"type": "mobile", "count": 5390, "percentage": 35.0},
      {"type": "tablet", "count": 770, "percentage": 5.0}
    ],
    "browsers": [
      {"name": "Chrome", "count": 8470, "percentage": 55.0},
      {"name": "Safari", "count": 3850, "percentage": 25.0},
      {"name": "Firefox", "count": 2310, "percentage": 15.0}
    ],
    "operating_systems": [
      {"name": "Windows", "count": 6930, "percentage": 45.0},
      {"name": "macOS", "count": 3850, "percentage": 25.0},
      {"name": "Android", "count": 3080, "percentage": 20.0}
    ],
    "period": {
      "start_date": "2026-03-03",
      "end_date": "2026-04-02"
    }
  }
}
```

---

### 10. Real-time Active Users

Get currently active users (last 5 minutes).

**Endpoint:** `GET /api/v1/analytics/realtime`

**Response:**
```json
{
  "success": true,
  "data": {
    "active_users": 42,
    "sessions": [
      {
        "session_id": "abc123...",
        "user_id": 5,
        "current_url": "/posts/laravel-tips",
        "current_page_title": "Laravel Tips",
        "last_seen_at": "2026-04-02T15:30:00Z",
        "country": "US"
      }
    ],
    "by_country": {
      "US": 15,
      "UK": 8,
      "CA": 5
    }
  },
  "cached_until": "2026-04-02T15:30:30Z"
}
```

---

### 11. Audience Insights

Get comprehensive audience insights.

**Endpoint:** `GET /api/v1/analytics/audience`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "total_visitors": 8234,
    "new_visitors": 4521,
    "returning_visitors": 3713,
    "new_visitor_percentage": 54.9,
    "returning_visitor_percentage": 45.1,
    "device_breakdown": {...},
    "browser_breakdown": {...},
    "os_breakdown": {...},
    "geographic_breakdown": {...}
  }
}
```

---

### 12. Export Data

Export analytics data in JSON or CSV format.

**Endpoint:** `GET /api/v1/analytics/export`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | Yes | - | Start date (YYYY-MM-DD) |
| end_date | string | Yes | - | End date (YYYY-MM-DD) |
| format | string | No | json | Format: json, csv |

**Response (JSON):**
```json
{
  "success": true,
  "data": {
    "export_date": "2026-04-02T15:30:00Z",
    "date_range": {
      "start": "2026-03-03",
      "end": "2026-04-02"
    },
    "overview": {...},
    "daily_stats": [...],
    "top_posts": [...],
    "traffic_sources": {...},
    "geographic": [...]
  }
}
```

**Note:** For CSV exports with date ranges > 90 days, the response will be queued for async processing.

---

### 13. Clear Cache

Clear analytics cache.

**Endpoint:** `POST /api/v1/analytics/cache/clear`

**Response:**
```json
{
  "success": true,
  "message": "Analytics cache cleared successfully"
}
```

---

### 14. Warm Cache

Pre-populate analytics cache.

**Endpoint:** `POST /api/v1/analytics/cache/warm`

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| start_date | string | No | 30 days ago | Start date (YYYY-MM-DD) |
| end_date | string | No | today | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "message": "Analytics cache warmed successfully"
}
```

---

## Scheduled Jobs

### Daily Aggregation (AggregatePostViews)
- **Schedule:** Daily at 00:00
- **Purpose:** Aggregates post views data for the previous day
- **Creates:** PostViewSummary and AnalyticsDailyStat records

### Monthly Cleanup (CleanupAnalytics)
- **Schedule:** Monthly on 1st at 03:00
- **Purpose:** Deletes raw analytics events older than 12 months
- **Retention:** Aggregated data is kept indefinitely

### Session Cleanup
- **Schedule:** Hourly
- **Purpose:** Removes expired active sessions

### Cache Warming
- **Schedule:** Daily at 05:00
- **Purpose:** Pre-populates cache for common queries

---

## Data Models

### AnalyticsEvent
Tracks individual analytics events with the following fields:
- `event_type`: page_view, post_view, click, search, etc.
- `user_id`: Associated user (nullable)
- `post_id`: Associated post (nullable)
- `session_id`: Session identifier
- `visitor_fingerprint`: Unique visitor hash
- `is_new_visitor`: Boolean flag
- `traffic_source`: direct, organic, social, referral, email, paid
- `device_type`: desktop, mobile, tablet
- `browser`: Chrome, Firefox, Safari, etc.
- `os`: Windows, macOS, Linux, Android, iOS
- `country`, `city`: Geographic data
- `referrer`, `referrer_domain`: Referral information
- `response_time_ms`: Page load time

### PostViewSummary
Daily aggregated post statistics:
- `post_id`: Associated post
- `view_date`: Date of aggregation
- `total_views`, `unique_views`: View counts
- `new_visitors`, `returning_visitors`: Visitor types
- `referrer_breakdown`, `device_breakdown`, `country_breakdown`: JSON breakdowns

### AnalyticsDailyStat
Daily aggregated site statistics:
- `stat_date`: Date of aggregation
- Traffic, engagement, source, device, and geographic metrics

### ActiveSession
Real-time active session tracking:
- `session_id`: Unique session identifier
- `current_url`, `current_page_title`: Current page info
- `last_seen_at`: Last activity timestamp
- Geographic and device information

---

## Privacy & GDPR Compliance

- IP addresses are hashed using SHA-256 with app key salt
- Raw events are retained for 12 months only
- Aggregated data is kept indefinitely (no PII)
- Bot traffic is automatically excluded
- Users can be tracked anonymously via session ID

---

## Caching Strategy

| Data Type | Cache TTL |
|-----------|-----------|
| Dashboard Overview | 1 hour |
| Views Over Time | 6 hours |
| Top Posts | 6 hours |
| Engagement Metrics | 1 hour |
| Traffic Sources | 6 hours |
| Device Breakdown | 6 hours |
| Real-time Users | 30 seconds |

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized. Admin or Editor role required."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "start_date": ["Start date must be before or equal to end date"]
  }
}
```

---

## Testing

Run analytics tests:
```bash
php artisan test --filter=Analytics
```

Test specific test classes:
```bash
php artisan test tests/Feature/Analytics/AnalyticsEndpointsTest.php
php artisan test tests/Feature/Analytics/AnalyticsModelsTest.php
php artisan test tests/Feature/Analytics/AnalyticsServiceTest.php
php artisan test tests/Feature/Analytics/EventTrackingMiddlewareTest.php
```

---

## Implementation Files

### Models
- `app/Models/AnalyticsEvent.php`
- `app/Models/PostViewSummary.php`
- `app/Models/AnalyticsDailyStat.php`
- `app/Models/ActiveSession.php`

### Repository
- `app/Repositories/AnalyticsRepository.php`

### Services
- `app/Services/AnalyticsService.php`

### Controllers
- `app/Http/Controllers/Api/V1/Admin/AnalyticsController.php`

### Middleware
- `app/Http/Middleware/EventTrackingMiddleware.php`

### Jobs
- `app/Jobs/AggregatePostViews.php`
- `app/Jobs/CleanupAnalytics.php`

### Helpers
- `app/Helpers/UserAgentParser.php`
- `app/Helpers/GeoLocation.php`

### Requests
- `app/Http/Requests/Analytics/AnalyticsDateRangeRequest.php`
- `app/Http/Requests/Analytics/TopPostsRequest.php`
- `app/Http/Requests/Analytics/ExportAnalyticsRequest.php`

### Migrations
- `database/migrations/2026_01_02_000020_create_post_views_summary_table.php`
- `database/migrations/2026_01_02_000021_create_analytics_daily_stats_table.php`
- `database/migrations/2026_01_02_000022_create_active_sessions_table.php`
- `database/migrations/2026_01_02_000023_add_landing_page_and_hashed_ip_to_analytics_events.php`

### Tests
- `tests/Feature/Analytics/AnalyticsEndpointsTest.php`
- `tests/Feature/Analytics/AnalyticsModelsTest.php`
- `tests/Feature/Analytics/AnalyticsServiceTest.php`
- `tests/Feature/Analytics/EventTrackingMiddlewareTest.php`
