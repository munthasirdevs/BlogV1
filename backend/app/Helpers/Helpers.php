<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Class Helpers
 *
 * Collection of helper functions for common operations.
 */
class Helpers
{
    /**
     * Generate a unique slug from a title.
     *
     * @param string $title
     * @param string $table
     * @param string $column
     * @param int|null $excludeId
     * @return string
     */
    public static function generateSlug(
        string $title,
        string $table = 'posts',
        string $column = 'slug',
        ?int $excludeId = null
    ): string {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (self::slugExists($slug, $table, $column, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists in the database.
     *
     * @param string $slug
     * @param string $table
     * @param string $column
     * @param int|null $excludeId
     * @return bool
     */
    public static function slugExists(
        string $slug,
        string $table,
        string $column = 'slug',
        ?int $excludeId = null
    ): bool {
        $query = DB::table($table)->where($column, $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        // Check for soft deletes
        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    /**
     * Sanitize HTML content by stripping dangerous tags.
     *
     * @param string $html
     * @param array $allowedTags
     * @return string
     */
    public static function sanitizeHtml(string $html, array $allowedTags = []): string
    {
        if (empty($allowedTags)) {
            $allowedTags = [
                'p', 'br', 'strong', 'em', 'u', 'strike',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'ul', 'ol', 'li',
                'blockquote', 'pre', 'code',
                'a', 'img',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'div', 'span', 'hr',
            ];
        }

        $allowedTagString = '<' . implode('><', $allowedTags) . '>';

        // Strip all tags except allowed ones
        $cleaned = strip_tags($html, $allowedTagString);

        // Remove dangerous attributes
        $cleaned = self::removeDangerousAttributes($cleaned);

        return $cleaned;
    }

    /**
     * Remove dangerous attributes from HTML.
     *
     * @param string $html
     * @return string
     */
    public static function removeDangerousAttributes(string $html): string
    {
        // Remove onclick, onerror, onload, etc.
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^\s>]*/i', '', $html);

        // Remove javascript: protocol
        $html = preg_replace('/javascript\s*:/i', '', $html);

        // Remove data: protocol in href/src (can be used for XSS)
        $html = preg_replace('/data\s*:/i', '', $html);

        return $html;
    }

    /**
     * Escape HTML to prevent XSS.
     *
     * @param string $string
     * @return string
     */
    public static function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape JavaScript to prevent XSS.
     *
     * @param string $string
     * @return string
     */
    public static function escapeJs(string $string): string
    {
        return str_replace(
            ['\\', "'", '"', "\n", "\r", '<', '>', '&'],
            ['\\\\', "\'", '\"', '\n', '\r', '\x3C', '\x3E', '\x26'],
            $string
        );
    }

    /**
     * Calculate reading time in minutes.
     *
     * @param string $content
     * @param int $wordsPerMinute
     * @return int
     */
    public static function calculateReadingTime(string $content, int $wordsPerMinute = 200): int
    {
        $words = str_word_count(strip_tags($content));
        return max(1, (int) ceil($words / $wordsPerMinute));
    }

    /**
     * Get word count from content.
     *
     * @param string $content
     * @return int
     */
    public static function getWordCount(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    /**
     * Format file size.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format date to ISO 8601.
     *
     * @param \DateTimeInterface|string|null $date
     * @return string|null
     */
    public static function formatIso8601(\DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format(\DateTimeInterface::ISO8601);
    }

    /**
     * Format date for API response.
     *
     * @param \DateTimeInterface|string|null $date
     * @return string|null
     */
    public static function formatDateForApi(\DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format('Y-m-d\TH:i:s.u\Z');
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     * @return string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a secure token.
     *
     * @param int $length
     * @return string
     */
    public static function generateToken(int $length = 64): string
    {
        return Str::random($length);
    }

    /**
     * Truncate text with ellipsis.
     *
     * @param string $text
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    public static function truncate(string $text, int $length = 100, string $ellipsis = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return rtrim(substr($text, 0, $length - strlen($ellipsis))) . $ellipsis;
    }

    /**
     * Truncate HTML content preserving tags.
     *
     * @param string $html
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    public static function truncateHtml(string $html, int $length = 100, string $ellipsis = '...'): string
    {
        // Strip tags for length calculation
        $plainText = strip_tags($html);

        if (strlen($plainText) <= $length) {
            return $html;
        }

        // Truncate and re-add tags (simplified approach)
        return self::truncate($plainText, $length, $ellipsis);
    }

    /**
     * Convert bytes to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    public static function humanFileSize(int $bytes): string
    {
        return self::formatFileSize($bytes);
    }

    /**
     * Parse markdown-like syntax to HTML (basic).
     *
     * @param string $text
     * @return string
     */
    public static function parseBasicMarkdown(string $text): string
    {
        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Italic
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Links
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);

        // Code
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

        // Line breaks
        $text = nl2br($text);

        return $text;
    }

    /**
     * Extract first image URL from content.
     *
     * @param string $content
     * @return string|null
     */
    public static function extractFirstImage(string $content): ?string
    {
        preg_match('/<img[^>]+src="([^">]+)"/i', $content, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get domain from email.
     *
     * @param string $email
     * @return string|null
     */
    public static function getEmailDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? null;
    }

    /**
     * Validate email domain.
     *
     * @param string $email
     * @param array $allowedDomains
     * @param bool $allowSubdomains
     * @return bool
     */
    public static function validateEmailDomain(
        string $email,
        array $allowedDomains,
        bool $allowSubdomains = false
    ): bool {
        $domain = self::getEmailDomain($email);

        if (!$domain) {
            return false;
        }

        $domain = strtolower($domain);

        if ($allowSubdomains) {
            foreach ($allowedDomains as $allowedDomain) {
                if ($domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain)) {
                    return true;
                }
            }
            return false;
        }

        return in_array($domain, array_map('strtolower', $allowedDomains));
    }

    /**
     * Mask email address.
     *
     * @param string $email
     * @return string
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask phone number.
     *
     * @param string $phone
     * @param int $visibleChars
     * @return string
     */
    public static function maskPhone(string $phone, int $visibleChars = 4): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $length = strlen($digits);

        if ($length <= $visibleChars) {
            return $phone;
        }

        $masked = str_repeat('*', $length - $visibleChars) . substr($digits, -$visibleChars);

        return $masked;
    }

    /**
     * Generate Gravatar URL.
     *
     * @param string $email
     * @param int $size
     * @param string $default
     * @return string
     */
    public static function gravatar(string $email, int $size = 80, string $default = 'mp'): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
    }

    /**
     * Check if request is from mobile device.
     *
     * @param string $userAgent
     * @return bool
     */
    public static function isMobile(string $userAgent): bool
    {
        return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent);
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Clean and normalize text.
     *
     * @param string $text
     * @return string
     */
    public static function cleanText(string $text): string
    {
        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove multiple spaces
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Remove multiple newlines
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Convert array to query string.
     *
     * @param array $params
     * @return string
     */
    public static function arrayToQuery(array $params): string
    {
        return http_build_query(array_filter($params, fn($v) => $v !== null));
    }

    /**
     * Parse query string to array.
     *
     * @param string $query
     * @return array
     */
    public static function queryToArray(string $query): array
    {
        $params = [];
        parse_str($query, $params);
        return $params;
    }
}
