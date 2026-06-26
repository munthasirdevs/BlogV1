<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Central feature flag system for the entire platform.
    | Toggle modules on/off without code changes.
    |
    */

    'ai' => [
        'content_generation' => env('FEATURE_AI_CONTENT', true),
        'seo_optimization' => env('FEATURE_AI_SEO', true),
        'auto_tagging' => env('FEATURE_AI_TAGGING', true),
        'semantic_search' => env('FEATURE_AI_SEARCH', true),
    ],

    'billing' => [
        'enabled' => env('FEATURE_BILLING', true),
        'invoices' => env('FEATURE_INVOICES', true),
        'usage_tracking' => env('FEATURE_USAGE_TRACKING', true),
    ],

    'search' => [
        'ai_enhanced' => env('FEATURE_AI_SEARCH', true),
        'autocomplete' => env('FEATURE_AUTOCOMPLETE', true),
        'trending' => env('FEATURE_TRENDING_SEARCH', true),
    ],

    'analytics' => [
        'page_views' => env('FEATURE_PAGE_VIEWS', true),
        'event_tracking' => env('FEATURE_EVENT_TRACKING', true),
        'reports' => env('FEATURE_ANALYTICS_REPORTS', true),
    ],

    'workflow' => [
        'approval' => env('FEATURE_APPROVAL_WORKFLOW', true),
        'scheduling' => env('FEATURE_SCHEDULING', true),
        'revisions' => env('FEATURE_REVISIONS', true),
    ],

    'observability' => [
        'system_logs' => env('FEATURE_SYSTEM_LOGS', true),
        'health_checks' => env('FEATURE_HEALTH_CHECKS', true),
    ],

    'security' => [
        'strong_passwords' => env('FEATURE_STRONG_PASSWORDS', true),
        'rate_limiting' => env('FEATURE_RATE_LIMITING', true),
        'suspicious_activity_detection' => env('FEATURE_SUSPICIOUS_DETECTION', true),
    ],

    'saas' => [
        'multi_tenant' => env('FEATURE_MULTI_TENANT', true),
        'tenant_isolation' => env('FEATURE_TENANT_ISOLATION', true),
    ],
];
