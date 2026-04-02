# Phase 12: Newsletter & Email Subscription System

## Overview

This phase implements a complete newsletter and email subscription system with double opt-in, preference management, campaign management, email tracking, and webhook handling for bounce/complaint notifications.

## Features

- ✅ Double opt-in subscription flow
- ✅ Email confirmation with 24-hour token expiration
- ✅ One-click unsubscribe (GDPR compliant)
- ✅ Subscription preferences management
- ✅ Daily, weekly, and monthly digest emails
- ✅ New post notification emails
- ✅ Email open and click tracking
- ✅ Bounce and complaint handling (Mailgun/SendGrid)
- ✅ A/B testing support for campaigns
- ✅ Rate-limited email sending
- ✅ Subscriber segmentation
- ✅ GDPR data export/deletion

## API Endpoints

### Public Endpoints

#### Subscribe to Newsletter
```http
POST /api/v1/subscribe
Content-Type: application/json

{
    "email": "user@example.com",
    "preferences": {
        "frequency": "weekly",
        "new_posts": true,
        "categories": [1, 2, 3],
        "content_types": ["articles", "tutorials"]
    }
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Subscription created! Please check your email to confirm.",
    "data": {
        "email": "user@example.com",
        "is_confirmed": false,
        "subscribed_at": "2024-01-15T10:30:00.000Z"
    }
}
```

#### Confirm Subscription
```http
POST /api/v1/subscribe/confirm/{token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Subscription confirmed successfully!",
    "data": {
        "email": "user@example.com",
        "confirmed_at": "2024-01-15T10:35:00.000Z"
    }
}
```

#### Unsubscribe
```http
POST /api/v1/unsubscribe
Content-Type: application/json

{
    "email": "user@example.com"
}
// OR
{
    "token": "subscription-token"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "You have been unsubscribed. A confirmation email has been sent."
}
```

#### Resend Confirmation
```http
POST /api/v1/subscribe/resend
Content-Type: application/json

{
    "email": "user@example.com"
}
```

### Authenticated Endpoints

#### Update Preferences
```http
PUT /api/v1/subscriptions/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
    "subscription_id": 1,
    "preferences": {
        "frequency": "daily",
        "new_posts": false,
        "weekly_digest": true,
        "categories": [1, 2]
    }
}
```

#### Export Data (GDPR)
```http
POST /api/v1/subscriptions/export
Authorization: Bearer {token}
Content-Type: application/json

{
    "email": "user@example.com"
}
```

#### Delete Data (GDPR)
```http
DELETE /api/v1/subscriptions/delete
Authorization: Bearer {token}
Content-Type: application/json

{
    "email": "user@example.com"
}
```

### Admin Endpoints

#### List Subscriptions
```http
GET /api/v1/subscriptions?page=1&per_page=15&search=email@example.com
Authorization: Bearer {admin-token}
```

#### Get Subscription Details
```http
GET /api/v1/subscriptions/{id}
Authorization: Bearer {admin-token}
```

#### Delete Subscription
```http
DELETE /api/v1/subscriptions/{id}
Authorization: Bearer {admin-token}
```

#### Get Subscriber Segments
```http
GET /api/v1/admin/subscribers/segments
Authorization: Bearer {admin-token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "confirmed_active": 150,
        "daily_digest": 30,
        "weekly_digest": 80,
        "monthly_digest": 40,
        "instant_notifications": 100,
        "wants_new_posts": 120,
        "high_engagement": 45,
        "medium_engagement": 60,
        "low_engagement": 45
    }
}
```

#### Get Subscriber Statistics
```http
GET /api/v1/admin/subscribers/stats
Authorization: Bearer {admin-token}
```

### Tracking Endpoints

#### Track Email Open
```http
POST /api/v1/track/open/{subscriberId}/{emailId}
```

Returns a 1x1 transparent pixel (204 No Content).

#### Track Link Click
```http
GET /api/v1/track/click/{subscriberId}/{linkId}?email_id={emailId}&url={encodedUrl}
```

Redirects to the original URL.

### Webhook Endpoints

#### Mailgun Bounce Webhook
```http
POST /api/v1/webhooks/mail/bounce
Content-Type: application/json

{
    "event": "bounced",
    "recipient": "user@example.com",
    "severity": "permanent",
    "reason": "Mailbox does not exist",
    "timestamp": 1704067200
}
```

#### SendGrid Event Webhook
```http
POST /api/v1/webhooks/mail/complaint
Content-Type: application/json

[
    {
        "event": "spamreport",
        "email": "user@example.com",
        "timestamp": 1704067200
    }
]
```

## Database Schema

### subscriptions
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| email | string | Subscriber email (unique) |
| user_id | bigint | Associated user (nullable) |
| token | string | Confirmation token |
| subscribed_at | timestamp | Subscription date |
| confirmed_at | timestamp | Confirmation date |
| unsubscribed_at | timestamp | Unsubscribe date |
| is_confirmed | boolean | Confirmation status |
| is_active | boolean | Active status |
| preferences | json | Email preferences |
| frequency | string | Email frequency |
| ip_address | string | Signup IP |
| user_agent | string | Signup user agent |
| deleted_at | timestamp | Soft delete |

### email_campaigns
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Campaign name |
| subject | string | Email subject |
| subject_b | string | A/B test subject B |
| status | string | draft/scheduled/sending/sent/cancelled |
| total_recipients | int | Total recipients |
| sent_count | int | Emails sent |
| opened_count | int | Opens |
| clicked_count | int | Clicks |
| bounced_count | int | Bounces |
| is_ab_test | boolean | A/B test flag |
| ab_test_winner | string | Winner variant (a/b) |

### email_trackings
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| subscription_id | bigint | Subscriber |
| email_campaign_id | bigint | Campaign (nullable) |
| email_type | string | confirmation/digest/newsletter/new_post |
| subject | string | Email subject |
| sent_at | timestamp | Sent time |
| opened_at | timestamp | First open time |
| open_count | int | Total opens |
| clicked_at | timestamp | First click time |
| click_count | int | Total clicks |
| bounced_at | timestamp | Bounce time |
| bounce_type | string | hard/soft |
| complained_at | timestamp | Complaint time |

## Scheduled Jobs

| Job | Schedule | Description |
|-----|----------|-------------|
| DailyDigest | Daily at 8:00 AM | Send daily digest to subscribers |
| WeeklyDigest | Monday at 8:00 AM | Send weekly digest |
| MonthlyDigest | 1st of month at 8:00 AM | Send monthly digest |
| Cleanup Unconfirmed | Daily at 3:00 AM | Remove old unconfirmed subscriptions |
| Process Scheduled Campaigns | Every 5 minutes | Send due scheduled campaigns |

## Email Templates

All templates are mobile-responsive and located in `resources/views/emails/subscription/`:

1. **confirm.blade.php** - Subscription confirmation email
2. **welcome.blade.php** - Welcome email after confirmation
3. **digest.blade.php** - Daily/weekly/monthly digest
4. **unsubscribe_confirm.blade.php** - Unsubscribe confirmation
5. **new_post.blade.php** - New post notification

## Configuration

### Environment Variables

```env
# Newsletter Configuration
NEWSLETTER_RATE_LIMIT=100
NEWSLETTER_BATCH_SIZE=50
NEWSLETTER_BATCH_DELAY=60
NEWSLETTER_FROM_ADDRESS="newsletter@example.com"
NEWSLETTER_FROM_NAME="Blog Newsletter"
NEWSLETTER_REPLY_TO="noreply@example.com"
NEWSLETTER_TRACK_OPENS=true
NEWSLETTER_TRACK_CLICKS=true

# Email Provider (optional)
MAILGUN_API_KEY=
MAILGUN_DOMAIN=
SENDGRID_API_KEY=
```

### Mail Configuration

Edit `config/mail.php` to customize newsletter settings:

```php
'newsletter' => [
    'rate_limit' => env('NEWSLETTER_RATE_LIMIT', 100),
    'batch_size' => env('NEWSLETTER_BATCH_SIZE', 50),
    'batch_delay' => env('NEWSLETTER_BATCH_DELAY', 60),
    'track_opens' => env('NEWSLETTER_TRACK_OPENS', true),
    'track_clicks' => env('NEWSLETTER_TRACK_CLICKS', true),
],
```

## Usage Examples

### Subscribe a User

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$subscription = $subscriptionService->subscribe(
    'user@example.com',
    [
        'frequency' => 'weekly',
        'new_posts' => true,
    ]
);
```

### Confirm Subscription

```php
$subscription = $subscriptionService->confirmSubscription($token);
```

### Send Campaign

```php
use App\Services\NewsletterService;

$newsletterService = app(NewsletterService::class);

$campaign = $newsletterService->createCampaign(
    'January Newsletter',
    'Check out our latest articles!',
    'newsletter',
    ['html' => $htmlContent]
);

$newsletterService->startCampaign($campaign->id);
```

### Send New Post Notification

```php
// Triggered automatically when a post is published
$newsletterService->sendNewPostNotification($post);
```

## Testing

Run the newsletter tests:

```bash
php artisan test --filter=SubscriptionTest
php artisan test --filter=NewsletterCampaignTest
```

## Security Considerations

1. **Double Opt-In**: All subscriptions require email confirmation
2. **Token Expiration**: Confirmation tokens expire after 24 hours
3. **One-Click Unsubscribe**: Easy unsubscribe without authentication
4. **Bounce Handling**: Hard bounced emails are automatically unsubscribed
5. **Complaint Handling**: Spam complaints result in immediate unsubscription
6. **Rate Limiting**: Email sending is rate-limited to prevent throttling
7. **GDPR Compliance**: Data export and deletion endpoints available

## Migration Commands

Run migrations to create the necessary tables:

```bash
php artisan migrate
```

## Queue Configuration

Ensure your queue worker is running:

```bash
php artisan queue:work --queue=emails
```

For production, use a process manager like Supervisor:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work --queue=emails --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
```
