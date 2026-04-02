<?php

/**
 * Global helper functions for the blog application.
 *
 * These functions are loaded automatically via composer.json autoload files.
 */

use App\Helpers\Helpers;

if (!function_exists('generate_slug')) {
    /**
     * Generate a unique slug from a title.
     *
     * @param string $title
     * @param string $table
     * @param string $column
     * @param int|null $excludeId
     * @return string
     */
    function generate_slug(string $title, string $table = 'posts', string $column = 'slug', ?int $excludeId = null): string
    {
        return Helpers::generateSlug($title, $table, $column, $excludeId);
    }
}

if (!function_exists('sanitize_html')) {
    /**
     * Sanitize HTML content by stripping dangerous tags.
     *
     * @param string $html
     * @param array $allowedTags
     * @return string
     */
    function sanitize_html(string $html, array $allowedTags = []): string
    {
        return Helpers::sanitizeHtml($html, $allowedTags);
    }
}

if (!function_exists('escape_html')) {
    /**
     * Escape HTML to prevent XSS.
     *
     * @param string $string
     * @return string
     */
    function escape_html(string $string): string
    {
        return Helpers::escapeHtml($string);
    }
}

if (!function_exists('escape_js')) {
    /**
     * Escape JavaScript to prevent XSS.
     *
     * @param string $string
     * @return string
     */
    function escape_js(string $string): string
    {
        return Helpers::escapeJs($string);
    }
}

if (!function_exists('reading_time')) {
    /**
     * Calculate reading time in minutes.
     *
     * @param string $content
     * @param int $wordsPerMinute
     * @return int
     */
    function reading_time(string $content, int $wordsPerMinute = 200): int
    {
        return Helpers::calculateReadingTime($content, $wordsPerMinute);
    }
}

if (!function_exists('word_count')) {
    /**
     * Get word count from content.
     *
     * @param string $content
     * @return int
     */
    function word_count(string $content): int
    {
        return Helpers::getWordCount($content);
    }
}

if (!function_exists('format_file_size')) {
    /**
     * Format file size.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function format_file_size(int $bytes, int $precision = 2): string
    {
        return Helpers::formatFileSize($bytes, $precision);
    }
}

if (!function_exists('format_iso8601')) {
    /**
     * Format date to ISO 8601.
     *
     * @param \DateTimeInterface|string|null $date
     * @return string|null
     */
    function format_iso8601(\DateTimeInterface|string|null $date): ?string
    {
        return Helpers::formatIso8601($date);
    }
}

if (!function_exists('format_date_api')) {
    /**
     * Format date for API response.
     *
     * @param \DateTimeInterface|string|null $date
     * @return string|null
     */
    function format_date_api(\DateTimeInterface|string|null $date): ?string
    {
        return Helpers::formatDateForApi($date);
    }
}

if (!function_exists('random_string')) {
    /**
     * Generate a random string.
     *
     * @param int $length
     * @return string
     */
    function random_string(int $length = 32): string
    {
        return Helpers::randomString($length);
    }
}

if (!function_exists('generate_token')) {
    /**
     * Generate a secure token.
     *
     * @param int $length
     * @return string
     */
    function generate_token(int $length = 64): string
    {
        return Helpers::generateToken($length);
    }
}

if (!function_exists('truncate')) {
    /**
     * Truncate text with ellipsis.
     *
     * @param string $text
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    function truncate(string $text, int $length = 100, string $ellipsis = '...'): string
    {
        return Helpers::truncate($text, $length, $ellipsis);
    }
}

if (!function_exists('truncate_html')) {
    /**
     * Truncate HTML content preserving tags.
     *
     * @param string $html
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    function truncate_html(string $html, int $length = 100, string $ellipsis = '...'): string
    {
        return Helpers::truncateHtml($html, $length, $ellipsis);
    }
}

if (!function_exists('human_file_size')) {
    /**
     * Convert bytes to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    function human_file_size(int $bytes): string
    {
        return Helpers::humanFileSize($bytes);
    }
}

if (!function_exists('parse_markdown')) {
    /**
     * Parse markdown-like syntax to HTML (basic).
     *
     * @param string $text
     * @return string
     */
    function parse_markdown(string $text): string
    {
        return Helpers::parseBasicMarkdown($text);
    }
}

if (!function_exists('extract_first_image')) {
    /**
     * Extract first image URL from content.
     *
     * @param string $content
     * @return string|null
     */
    function extract_first_image(string $content): ?string
    {
        return Helpers::extractFirstImage($content);
    }
}

if (!function_exists('get_email_domain')) {
    /**
     * Get domain from email.
     *
     * @param string $email
     * @return string|null
     */
    function get_email_domain(string $email): ?string
    {
        return Helpers::getEmailDomain($email);
    }
}

if (!function_exists('validate_email_domain')) {
    /**
     * Validate email domain.
     *
     * @param string $email
     * @param array $allowedDomains
     * @param bool $allowSubdomains
     * @return bool
     */
    function validate_email_domain(string $email, array $allowedDomains, bool $allowSubdomains = false): bool
    {
        return Helpers::validateEmailDomain($email, $allowedDomains, $allowSubdomains);
    }
}

if (!function_exists('mask_email')) {
    /**
     * Mask email address.
     *
     * @param string $email
     * @return string
     */
    function mask_email(string $email): string
    {
        return Helpers::maskEmail($email);
    }
}

if (!function_exists('mask_phone')) {
    /**
     * Mask phone number.
     *
     * @param string $phone
     * @param int $visibleChars
     * @return string
     */
    function mask_phone(string $phone, int $visibleChars = 4): string
    {
        return Helpers::maskPhone($phone, $visibleChars);
    }
}

if (!function_exists('gravatar')) {
    /**
     * Generate Gravatar URL.
     *
     * @param string $email
     * @param int $size
     * @param string $default
     * @return string
     */
    function gravatar(string $email, int $size = 80, string $default = 'mp'): string
    {
        return Helpers::gravatar($email, $size, $default);
    }
}

if (!function_exists('is_mobile')) {
    /**
     * Check if request is from mobile device.
     *
     * @param string $userAgent
     * @return bool
     */
    function is_mobile(string $userAgent): bool
    {
        return Helpers::isMobile($userAgent);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Get client IP address.
     *
     * @return string
     */
    function get_client_ip(): string
    {
        return Helpers::getClientIp();
    }
}

if (!function_exists('clean_text')) {
    /**
     * Clean and normalize text.
     *
     * @param string $text
     * @return string
     */
    function clean_text(string $text): string
    {
        return Helpers::cleanText($text);
    }
}

if (!function_exists('array_to_query')) {
    /**
     * Convert array to query string.
     *
     * @param array $params
     * @return string
     */
    function array_to_query(array $params): string
    {
        return Helpers::arrayToQuery($params);
    }
}

if (!function_exists('query_to_array')) {
    /**
     * Parse query string to array.
     *
     * @param string $query
     * @return array
     */
    function query_to_array(string $query): array
    {
        return Helpers::queryToArray($query);
    }
}
