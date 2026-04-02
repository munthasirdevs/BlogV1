<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class MentionParser
 *
 * Parses @username mentions in comment content and triggers notifications.
 *
 * Features:
 * - Parse @username patterns in text
 * - Validate mentioned users exist
 * - Limit mentions (10 max per comment)
 * - Link to user profiles
 * - Trigger notifications to mentioned users
 *
 * @package App\Helpers
 */
class MentionParser
{
    /**
     * Maximum number of mentions allowed per comment.
     */
    const MAX_MENTIONS = 10;

    /**
     * Regex pattern for matching @username mentions.
     */
    const MENTION_PATTERN = '/@([a-zA-Z0-9_]+)/';

    /**
     * Parse mentions from content and return mentioned users.
     *
     * @param string $content
     * @return Collection<int, User>
     */
    public static function parse(string $content): Collection
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches);

        if (empty($matches[1])) {
            return collect();
        }

        // Limit to max mentions
        $usernames = array_slice(array_unique($matches[1]), 0, self::MAX_MENTIONS);

        // Fetch users by username/name
        $users = User::whereIn('name', $usernames)
            ->orWhereIn('username', $usernames)
            ->get()
            ->keyBy('id');

        return $users;
    }

    /**
     * Parse mentions and return with validation results.
     *
     * @param string $content
     * @return array{valid: Collection<int, User>, invalid: array<int, string>}
     */
    public static function parseWithValidation(string $content): array
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches);

        if (empty($matches[1])) {
            return [
                'valid' => collect(),
                'invalid' => [],
            ];
        }

        // Get all mentioned usernames (limited)
        $allMentions = array_slice(array_unique($matches[1]), 0, self::MAX_MENTIONS);

        // Fetch existing users
        $existingUsers = User::whereIn('name', $allMentions)
            ->orWhereIn('username', $allMentions)
            ->get()
            ->keyBy('id');

        // Get valid usernames
        $validUsernames = $existingUsers->pluck('name')
            ->merge($existingUsers->pluck('username'))
            ->unique()
            ->toArray();

        // Find invalid mentions
        $invalidMentions = array_diff($allMentions, $validUsernames);

        return [
            'valid' => $existingUsers,
            'invalid' => array_values($invalidMentions),
        ];
    }

    /**
     * Replace @mentions with HTML links in content.
     *
     * @param string $content
     * @return string
     */
    public static function linkify(string $content): string
    {
        return preg_replace_callback(self::MENTION_PATTERN, function ($matches) {
            $username = $matches[1];

            // Find user by username or name
            $user = User::where('username', $username)
                ->orWhere('name', $username)
                ->first();

            if ($user) {
                $profileUrl = config('app.frontend_url', '/frontend') . "/users/{$user->id}";
                return "<a href=\"{$profileUrl}\" class=\"mention\" data-user-id=\"{$user->id}\">@{$user->name}</a>";
            }

            // Return original if user not found
            return "@{$username}";
        }, $content);
    }

    /**
     * Replace @mentions with markdown-style links.
     *
     * @param string $content
     * @return string
     */
    public static function linkifyMarkdown(string $content): string
    {
        return preg_replace_callback(self::MENTION_PATTERN, function ($matches) {
            $username = $matches[1];

            $user = User::where('username', $username)
                ->orWhere('name', $username)
                ->first();

            if ($user) {
                $profileUrl = config('app.frontend_url', '/frontend') . "/users/{$user->id}";
                return "[@{$user->name}]({$profileUrl})";
            }

            return "@{$username}";
        }, $content);
    }

    /**
     * Get mentioned user IDs from content.
     *
     * @param string $content
     * @return array<int>
     */
    public static function getMentionedUserIds(string $content): array
    {
        return self::parse($content)->pluck('id')->toArray();
    }

    /**
     * Check if content exceeds max mentions.
     *
     * @param string $content
     * @return bool
     */
    public static function exceedsMaxMentions(string $content): bool
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches);

        return count(array_unique($matches[1])) > self::MAX_MENTIONS;
    }

    /**
     * Get count of mentions in content.
     *
     * @param string $content
     * @return int
     */
    public static function getMentionCount(string $content): int
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches);

        return count(array_unique($matches[1]));
    }

    /**
     * Validate mentions in content.
     *
     * @param string $content
     * @return array{valid: bool, errors: array<string>}
     */
    public static function validate(string $content): array
    {
        $errors = [];

        // Check max mentions
        if (self::exceedsMaxMentions($content)) {
            $errors[] = "Maximum " . self::MAX_MENTIONS . " mentions allowed per comment.";
        }

        // Check for invalid mentions
        $result = self::parseWithValidation($content);
        if (!empty($result['invalid'])) {
            $invalidList = implode(', ', array_map(fn($u) => "@{$u}", $result['invalid']));
            $errors[] = "Invalid mentions: {$invalidList}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Extract plain usernames from content (without @).
     *
     * @param string $content
     * @return array<string>
     */
    public static function extractUsernames(string $content): array
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Remove all mentions from content.
     *
     * @param string $content
     * @return string
     */
    public static function removeMentions(string $content): string
    {
        return preg_replace(self::MENTION_PATTERN, '', $content);
    }

    /**
     * Get mention suggestions based on query.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public static function suggestUsers(string $query, int $limit = 5): Collection
    {
        return User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('username', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'name', 'username', 'avatar']);
    }
}
