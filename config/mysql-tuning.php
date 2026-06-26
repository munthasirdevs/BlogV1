<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MySQL Performance Tuning Configuration
    |--------------------------------------------------------------------------
    |
    | Recommended MySQL 8+ settings for XenonBlog.
    | Apply these to your my.cnf / my.ini file.
    |
    */

    'recommended_settings' => [
        'innodb_buffer_pool_size' => '2G',         // 70-80% of available RAM for dedicated DB servers
        'innodb_log_file_size' => '512M',           // Larger log files reduce write I/O
        'innodb_flush_log_at_trx_commit' => 2,      // 2 = best performance, 1 = safest
        'innodb_flush_method' => 'O_DIRECT',        // Avoids double buffering
        'innodb_file_per_table' => 1,               // Separate files per table for easier management
        'query_cache_type' => 0,                    // Disabled in MySQL 8+ (deprecated)
        'tmp_table_size' => '64M',                  // Temp table size for complex queries
        'max_heap_table_size' => '64M',             // In-memory table limit
        'max_connections' => 500,                   // Max concurrent connections
        'thread_cache_size' => 256,                 // Thread reuse cache
        'table_open_cache' => 4000,                 // Table descriptor cache
        'sort_buffer_size' => '4M',                 // Per-session sort buffer (don't set too high)
        'read_buffer_size' => '2M',                 // Per-session read buffer
        'join_buffer_size' => '2M',                 // Per-session join buffer
        'innodb_io_capacity' => 2000,               // IOPS capacity for SSD
        'innodb_io_capacity_max' => 4000,           // Max IOPS
        'innodb_read_io_threads' => 8,              // Parallel read threads
        'innodb_write_io_threads' => 8,             // Parallel write threads
    ],

    'index_recommendations' => [
        'Always index foreign key columns' => true,
        'Use composite indexes for WHERE + ORDER BY' => true,
        'Avoid over-indexing (max 5-6 per table)' => true,
        'Use partial indexes for long VARCHAR columns' => false,
        'Monitor slow query log regularly' => true,
        'Use EXPLAIN on all new queries' => true,
    ],

    'query_optimization_rules' => [
        'Never use SELECT * in production' => true,
        'Always specify columns in SELECT' => true,
        'Use eager loading to prevent N+1' => true,
        'Paginate all large result sets' => true,
        'Avoid WHERE clauses on unindexed columns' => true,
        'Use whereRaw for complex conditions carefully' => true,
        'Batch insert/update large data sets' => true,
    ],
];
