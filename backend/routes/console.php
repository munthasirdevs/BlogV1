<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\AggregatePostViews;
use App\Jobs\CleanupAnalytics;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Analytics Scheduled Jobs
|--------------------------------------------------------------------------
|
| Schedule analytics aggregation and cleanup jobs.
|
*/

// Aggregate post views daily at midnight
// This job processes the previous day's analytics data and creates summary records
Schedule::job(new AggregatePostViews())
    ->dailyAt('00:00')
    ->name('analytics:aggregate-post-views')
    ->emailOutputOnFailure(config('services.analytics.alert_email'))
    ->withoutOverlapping()
    ->onOneServer();

// Aggregate daily stats daily at 00:30
// This runs after the post views aggregation to ensure data is available
Schedule::call(function () {
    $yesterday = now()->subDay();
    AggregatePostViews::dispatch($yesterday);
})
    ->dailyAt('00:30')
    ->name('analytics:aggregate-daily-stats')
    ->withoutOverlapping();

// Clean up old analytics events monthly on the 1st at 3:00 AM
// This job deletes raw events older than 12 months (retention policy)
Schedule::job(new CleanupAnalytics())
    ->monthlyOn(1, '03:00')
    ->name('analytics:cleanup')
    ->emailOutputOnFailure(config('services.analytics.alert_email'))
    ->withoutOverlapping()
    ->onOneServer();

// Clean up expired active sessions hourly
// This ensures the active_sessions table doesn't grow indefinitely
Schedule::call(function () {
    \App\Models\ActiveSession::cleanupExpired();
})
    ->hourly()
    ->name('analytics:cleanup-sessions')
    ->withoutOverlapping();

// Warm analytics cache daily at 5:00 AM
// This pre-populates cache for common queries to improve dashboard performance
Schedule::call(function () {
    try {
        $analyticsService = app(\App\Services\AnalyticsService::class);
        $analyticsService->warmCache(now()->subDays(30), now());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Analytics cache warm failed', [
            'error' => $e->getMessage(),
        ]);
    }
})
    ->dailyAt('05:00')
    ->name('analytics:warm-cache')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Other Scheduled Commands
|--------------------------------------------------------------------------
*/

// Run database backups weekly (if configured)
// Schedule::command('backup:run')->weeklyOn(1, '01:00');

// Clear old sessions from database
Schedule::command('session:table')->daily();

// Clear old notification records
Schedule::command('notifications:table')->daily();

// Prune failed jobs older than 7 days
Schedule::command('queue:prune-failed')->weekly();

// Prune expired cache locks
Schedule::command('cache:prune-stale-tags')->hourly();

/*
|--------------------------------------------------------------------------
| Notification Scheduled Jobs
|--------------------------------------------------------------------------
|
| Schedule notification cleanup and digest jobs.
|
*/

// Clean up old read notifications weekly on Sunday at 4:00 AM
// This deletes read notifications older than 30 days
Schedule::command('notifications:cleanup-old --days=30')
    ->weeklyOn(0, '04:00')
    ->name('notifications:cleanup-old')
    ->withoutOverlapping()
    ->onOneServer();

// Send daily notification digest at 7:00 AM every day
Schedule::job(new \App\Jobs\SendNotificationDigest('daily', 1))
    ->dailyAt('07:00')
    ->name('notifications:daily-digest')
    ->withoutOverlapping()
    ->onOneServer();

// Send weekly notification digest at 7:00 AM every Monday
Schedule::job(new \App\Jobs\SendNotificationDigest('weekly', 7))
    ->weeklyOn(1, '07:00')
    ->name('notifications:weekly-digest')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Newsletter Scheduled Jobs
|--------------------------------------------------------------------------
|
| Schedule newsletter digest and maintenance jobs.
|
*/

// Send daily digest at 8:00 AM every day
Schedule::job(new \App\Console\Jobs\DailyDigest(
    app(\App\Services\NewsletterService::class),
    app(\App\Services\SubscriptionService::class)
))
    ->dailyAt('08:00')
    ->name('newsletter:daily-digest')
    ->withoutOverlapping()
    ->onOneServer();

// Send weekly digest at 8:00 AM every Monday
Schedule::job(new \App\Console\Jobs\WeeklyDigest(
    app(\App\Services\NewsletterService::class),
    app(\App\Services\SubscriptionService::class)
))
    ->weeklyOn(1, '08:00')
    ->name('newsletter:weekly-digest')
    ->withoutOverlapping()
    ->onOneServer();

// Send monthly digest at 8:00 AM on the 1st of every month
Schedule::job(new \App\Console\Jobs\MonthlyDigest(
    app(\App\Services\NewsletterService::class),
    app(\App\Services\SubscriptionService::class)
))
    ->monthlyOn(1, '08:00')
    ->name('newsletter:monthly-digest')
    ->withoutOverlapping()
    ->onOneServer();

// Clean up old unconfirmed subscriptions daily at 3:00 AM
Schedule::call(function () {
    $subscriptionService = app(\App\Services\SubscriptionService::class);
    $deleted = $subscriptionService->cleanupOldUnconfirmed();
    \Illuminate\Support\Facades\Log::info('Cleaned up old unconfirmed subscriptions', [
        'deleted_count' => $deleted,
    ]);
})
    ->dailyAt('03:00')
    ->name('newsletter:cleanup-unconfirmed')
    ->withoutOverlapping();

// Process due scheduled campaigns every 5 minutes
Schedule::call(function () {
    $newsletterService = app(\App\Services\NewsletterService::class);
    $campaigns = \App\Models\EmailCampaign::due()->get();
    
    foreach ($campaigns as $campaign) {
        try {
            $newsletterService->startCampaign($campaign->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to start scheduled campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})
    ->everyFiveMinutes()
    ->name('newsletter:process-scheduled-campaigns')
    ->withoutOverlapping();
