<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds landing_page and hashed_ip_address columns to analytics_events table
     * for enhanced tracking and GDPR compliance.
     */
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            // Add landing page column
            $table->string('landing_page')->nullable()->after('url');
            
            // Add hashed IP for privacy compliance (keep original for backward compat)
            $table->string('hashed_ip_address', 64)->nullable()->after('ip_address');
            
            // Add response time tracking
            $table->unsignedInteger('response_time_ms')->nullable()->after('landing_page');
            
            // Add visitor fingerprint for unique visitor tracking
            $table->string('visitor_fingerprint', 64)->nullable()->after('session_id');
            
            // Add boolean flags for visitor type
            $table->boolean('is_new_visitor')->default(false)->after('visitor_fingerprint');
            
            // Add geographic data
            $table->string('country')->nullable()->after('is_new_visitor');
            $table->string('city')->nullable()->after('country');
            
            // Add device/browser info
            $table->string('device_type')->nullable()->after('city');
            $table->string('browser')->nullable()->after('device_type');
            $table->string('os')->nullable()->after('browser');
            
            // Add referrer domain for easier source tracking
            $table->string('referrer_domain')->nullable()->after('referrer');
            
            // Add traffic source category
            $table->string('traffic_source')->nullable()->after('referrer_domain');
            // Values: direct, organic, social, referral, paid, email, unknown
            
            // Add indexes for new columns
            $table->index('visitor_fingerprint');
            $table->index('hashed_ip_address');
            $table->index('traffic_source');
            $table->index('referrer_domain');
            $table->index('country');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex(['visitor_fingerprint']);
            $table->dropIndex(['hashed_ip_address']);
            $table->dropIndex(['traffic_source']);
            $table->dropIndex(['referrer_domain']);
            $table->dropIndex(['country']);
            $table->dropIndex(['device_type']);
            
            $table->dropColumn([
                'landing_page',
                'hashed_ip_address',
                'response_time_ms',
                'visitor_fingerprint',
                'is_new_visitor',
                'country',
                'city',
                'device_type',
                'browser',
                'os',
                'referrer_domain',
                'traffic_source',
            ]);
        });
    }
};
