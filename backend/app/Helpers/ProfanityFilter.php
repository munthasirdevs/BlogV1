<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Class ProfanityFilter
 *
 * Filters profanity and inappropriate language from comment content.
 *
 * Features:
 * - Configurable profanity list
 * - Replace with asterisks or reject
 * - Skip for moderators/admins
 * - Case-insensitive matching
 * - Word boundary detection
 *
 * @package App\Helpers
 */
class ProfanityFilter
{
    /**
     * Cache key for profanity list.
     */
    const CACHE_KEY = 'profanity_filter:list';

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Default profanity list (should be extended via config).
     */
    protected static array $defaultProfanityList = [
        // Add common profanity words here
        // This is a minimal list - extend via config/profanity.php
    ];

    /**
     * Replacement character for filtered words.
     */
    protected static string $replacementChar = '*';

    /**
     * Check if content contains profanity.
     *
     * @param string $content
     * @return bool
     */
    public static function containsProfanity(string $content): bool
    {
        $profanityList = self::getProfanityList();

        if (empty($profanityList)) {
            return false;
        }

        $content = strtolower($content);

        foreach ($profanityList as $word) {
            // Use word boundary matching
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $content)) {
                return true;
            }
            // Also check for partial matches (for words without spaces)
            if (Str::contains($content, strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter profanity from content by replacing with asterisks.
     *
     * @param string $content
     * @return string
     */
    public static function filter(string $content): string
    {
        $profanityList = self::getProfanityList();

        if (empty($profanityList)) {
            return $content;
        }

        foreach ($profanityList as $word) {
            $content = self::replaceWord($content, $word);
        }

        return $content;
    }

    /**
     * Replace a specific word with asterisks.
     *
     * @param string $content
     * @param string $word
     * @return string
     */
    protected static function replaceWord(string $content, string $word): string
    {
        $replacement = str_repeat(self::$replacementChar, strlen($word));

        // Replace with word boundary matching (case-insensitive)
        return preg_replace(
            '/\b' . preg_quote($word, '/') . '\b/i',
            $replacement,
            $content
        );
    }

    /**
     * Get the profanity list from cache or config.
     *
     * @return array
     */
    public static function getProfanityList(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            // Try to load from config first
            $configList = config('profanity.list', []);

            // Merge with default list
            return array_merge(self::$defaultProfanityList, $configList);
        });
    }

    /**
     * Set the profanity list (for admin management).
     *
     * @param array $list
     * @return void
     */
    public static function setProfanityList(array $list): void
    {
        Cache::put(self::CACHE_KEY, $list, self::CACHE_TTL);
    }

    /**
     * Add a word to the profanity list.
     *
     * @param string $word
     * @return void
     */
    public static function addWord(string $word): void
    {
        $list = self::getProfanityList();

        if (!in_array(strtolower($word), $list)) {
            $list[] = strtolower($word);
            self::setProfanityList($list);
        }
    }

    /**
     * Remove a word from the profanity list.
     *
     * @param string $word
     * @return void
     */
    public static function removeWord(string $word): void
    {
        $list = self::getProfanityList();
        $list = array_diff($list, [strtolower($word)]);
        self::setProfanityList(array_values($list));
    }

    /**
     * Clear the profanity cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if user should bypass profanity filter.
     *
     * @param mixed $user
     * @return bool
     */
    public static function shouldBypass($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check if user has moderator or admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'moderator', 'editor']);
        }

        return false;
    }

    /**
     * Validate content for profanity.
     *
     * @param string $content
     * @param mixed $user
     * @param bool $allowFilter If true, filter instead of reject
     * @return array{passed: bool, filtered_content: string, errors: array}
     */
    public static function validate(string $content, $user = null, bool $allowFilter = false): array
    {
        // Bypass for privileged users
        if (self::shouldBypass($user)) {
            return [
                'passed' => true,
                'filtered_content' => $content,
                'errors' => [],
            ];
        }

        if (!self::containsProfanity($content)) {
            return [
                'passed' => true,
                'filtered_content' => $content,
                'errors' => [],
            ];
        }

        if ($allowFilter) {
            return [
                'passed' => true,
                'filtered_content' => self::filter($content),
                'errors' => ['Some words have been filtered.'],
            ];
        }

        return [
            'passed' => false,
            'filtered_content' => $content,
            'errors' => ['Content contains inappropriate language.'],
        ];
    }

    /**
     * Get severity level of profanity in content.
     *
     * @param string $content
     * @return string 'none', 'mild', 'moderate', 'severe'
     */
    public static function getSeverity(string $content): string
    {
        $profanityList = self::getProfanityList();
        $matchCount = 0;

        $content = strtolower($content);

        foreach ($profanityList as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $content)) {
                $matchCount++;
            }
        }

        if ($matchCount === 0) {
            return 'none';
        } elseif ($matchCount === 1) {
            return 'mild';
        } elseif ($matchCount <= 3) {
            return 'moderate';
        }

        return 'severe';
    }

    /**
     * Check if content is spammy (excessive links, caps, etc.).
     *
     * @param string $content
     * @return bool
     */
    public static function isSpammy(string $content): bool
    {
        // Check for excessive URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
        if ($urlCount >= 3) {
            return true;
        }

        // Check for excessive caps (more than 50% caps)
        $capsCount = preg_match_all('/[A-Z]/', $content);
        $alphaCount = preg_match_all('/[a-zA-Z]/', $content);
        if ($alphaCount > 0 && ($capsCount / $alphaCount) > 0.5) {
            return true;
        }

        // Check for repetitive characters
        if (preg_match('/(.)\1{4,}/', $content)) {
            return true;
        }

        return false;
    }
}
