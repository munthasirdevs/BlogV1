<?php

namespace App\Helpers;

/**
 * Class UserAgentParser
 *
 * Parses user agent strings to extract browser, OS, and device information.
 * Provides device type detection (desktop, mobile, tablet).
 */
class UserAgentParser
{
    /**
     * Known mobile device keywords.
     */
    private static array $mobileKeywords = [
        'android', 'webos', 'iphone', 'ipad', 'ipod', 'blackberry',
        'iemobile', 'opera mini', 'mobile', 'palm', 'windows ce',
        'kindle', 'silk', 'fire', 'playbook', 'touch', 'phone',
    ];

    /**
     * Known tablet device keywords.
     */
    private static array $tabletKeywords = [
        'ipad', 'android', 'playbook', 'silk', 'kindle', 'tablet',
        'galaxy tab', 'nook', 'touchpad', 'surface',
    ];

    /**
     * Browser detection patterns.
     */
    private static array $browserPatterns = [
        'Edge' => 'Edge\/([\d.]+)',
        'Edg' => 'Edg\/([\d.]+)',
        'Chrome' => 'Chrome\/([\d.]+)',
        'Safari' => 'Version\/([\d.]+).*Safari',
        'Firefox' => 'Firefox\/([\d.]+)',
        'MSIE' => 'MSIE ([\d.]+)',
        'Trident' => 'Trident\/([\d.]+).*rv:([\d.]+)',
        'Opera' => 'Opera\/([\d.]+)',
        'OPR' => 'OPR\/([\d.]+)',
    ];

    /**
     * OS detection patterns.
     */
    private static array $osPatterns = [
        'Windows' => 'Windows (?:NT )?([\d.]+)',
        'Windows Phone' => 'Windows Phone(?: OS)? ([\d.]+)',
        'macOS' => 'Mac OS X ([\d_]+)',
        'iOS' => 'OS ([\d_]+).*iPhone|OS ([\d_]+).*iPad',
        'Android' => 'Android ([\d.]+)',
        'Linux' => 'Linux',
        'Ubuntu' => 'Ubuntu\/([\d.]+)',
        'Chrome OS' => 'CrOS ([\d.]+)',
    ];

    /**
     * Parse user agent and return all detected information.
     *
     * @param string|null $userAgent
     * @return array
     */
    public static function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'browser' => 'Unknown',
                'browser_version' => null,
                'os' => 'Unknown',
                'os_version' => null,
                'device_type' => 'Unknown',
                'is_mobile' => false,
                'is_tablet' => false,
                'is_desktop' => false,
                'is_bot' => false,
            ];
        }

        return [
            'browser' => self::getBrowser($userAgent),
            'browser_version' => self::getBrowserVersion($userAgent),
            'os' => self::getOs($userAgent),
            'os_version' => self::getOsVersion($userAgent),
            'device_type' => self::getDeviceType($userAgent),
            'is_mobile' => self::isMobile($userAgent),
            'is_tablet' => self::isTablet($userAgent),
            'is_desktop' => self::isDesktop($userAgent),
            'is_bot' => self::isBot($userAgent),
        ];
    }

    /**
     * Get browser name from user agent.
     *
     * @param string $userAgent
     * @return string
     */
    public static function getBrowser(string $userAgent): string
    {
        // Check for Edge first (contains Chrome)
        if (preg_match('/Edg\/([\d.]+)/', $userAgent)) {
            return 'Edge';
        }

        if (preg_match('/Edge\/([\d.]+)/', $userAgent)) {
            return 'Edge';
        }

        // Check for Opera (contains Chrome)
        if (preg_match('/OPR\/([\d.]+)/', $userAgent)) {
            return 'Opera';
        }

        if (preg_match('/Opera\/([\d.]+)/', $userAgent)) {
            return 'Opera';
        }

        // Check for Chrome (contains Safari)
        if (preg_match('/Chrome\/([\d.]+)/', $userAgent) && !preg_match('/Edge/', $userAgent)) {
            // Exclude Chrome on iOS (which reports as Chrome but is Safari)
            if (!preg_match('/iPhone|iPad|iPod/', $userAgent)) {
                return 'Chrome';
            }
        }

        // Check for Firefox
        if (preg_match('/Firefox\/([\d.]+)/', $userAgent)) {
            return 'Firefox';
        }

        // Check for Safari (must be after Chrome)
        if (preg_match('/Safari\/([\d.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            return 'Safari';
        }

        // Check for Internet Explorer
        if (preg_match('/MSIE ([\d.]+)/', $userAgent) || preg_match('/Trident\/([\d.]+)/', $userAgent)) {
            return 'Internet Explorer';
        }

        // Check for Samsung Internet
        if (preg_match('/SamsungBrowser\/([\d.]+)/', $userAgent)) {
            return 'Samsung Internet';
        }

        return 'Other';
    }

    /**
     * Get browser version from user agent.
     *
     * @param string $userAgent
     * @return string|null
     */
    public static function getBrowserVersion(string $userAgent): ?string
    {
        foreach (self::$browserPatterns as $browser => $pattern) {
            if (preg_match('/' . $pattern . '/', $userAgent, $matches)) {
                // Handle version with multiple captures (like Trident)
                $version = end($matches);
                return $version;
            }
        }

        return null;
    }

    /**
     * Get operating system name from user agent.
     *
     * @param string $userAgent
     * @return string
     */
    public static function getOs(string $userAgent): string
    {
        if (preg_match('/Windows (?:NT )?([\d.]+)/', $userAgent, $matches)) {
            $version = $matches[1];
            if ($version === '10.0') return 'Windows';
            if ($version === '6.3') return 'Windows 8.1';
            if ($version === '6.2') return 'Windows 8';
            if ($version === '6.1') return 'Windows 7';
            return 'Windows';
        }

        if (preg_match('/Windows Phone(?: OS)? ([\d.]+)/', $userAgent)) {
            return 'Windows Phone';
        }

        if (preg_match('/Mac OS X ([\d_]+)/', $userAgent, $matches)) {
            return 'macOS';
        }

        if (preg_match('/OS ([\d_]+).*iPhone|OS ([\d_]+).*iPad/', $userAgent)) {
            return 'iOS';
        }

        if (preg_match('/Android ([\d.]+)/', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/Ubuntu\/([\d.]+)/', $userAgent)) {
            return 'Ubuntu';
        }

        if (preg_match('/CrOS ([\d.]+)/', $userAgent)) {
            return 'Chrome OS';
        }

        if (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Other';
    }

    /**
     * Get operating system version from user agent.
     *
     * @param string $userAgent
     * @return string|null
     */
    public static function getOsVersion(string $userAgent): ?string
    {
        if (preg_match('/Windows (?:NT )?([\d.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Windows Phone(?: OS)? ([\d.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Mac OS X ([\d_]+)/', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }

        if (preg_match('/OS ([\d_]+).*iPhone|OS ([\d_]+).*iPad/', $userAgent, $matches)) {
            $version = $matches[1] ?? $matches[2];
            return str_replace('_', '.', $version);
        }

        if (preg_match('/Android ([\d.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Ubuntu\/([\d.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        if (preg_match('/CrOS ([\d.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get device type from user agent.
     *
     * @param string $userAgent
     * @return string
     */
    public static function getDeviceType(string $userAgent): string
    {
        if (self::isTablet($userAgent)) {
            return 'tablet';
        }

        if (self::isMobile($userAgent)) {
            return 'mobile';
        }

        if (self::isDesktop($userAgent)) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Check if user agent is from a mobile device.
     *
     * @param string $userAgent
     * @return bool
     */
    public static function isMobile(string $userAgent): bool
    {
        $userAgentLower = strtolower($userAgent);

        // Check if it's a tablet first (tablets contain mobile keywords)
        if (self::isTablet($userAgent)) {
            return false;
        }

        foreach (self::$mobileKeywords as $keyword) {
            if (strpos($userAgentLower, $keyword) !== false) {
                return true;
            }
        }

        // Check for mobile-specific patterns
        if (preg_match('/Mobile\/\w+|Android.*Mobile/i', $userAgent)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user agent is from a tablet device.
     *
     * @param string $userAgent
     * @return bool
     */
    public static function isTablet(string $userAgent): bool
    {
        $userAgentLower = strtolower($userAgent);

        // iPad is always a tablet
        if (strpos($userAgentLower, 'ipad') !== false) {
            return true;
        }

        foreach (self::$tabletKeywords as $keyword) {
            if (strpos($userAgentLower, $keyword) !== false) {
                // Additional check for Android tablets
                if ($keyword === 'android') {
                    // Android tablets don't have "Mobile" in user agent
                    if (strpos($userAgentLower, 'mobile') === false) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        // Check for tablet-specific screen sizes in user agent
        if (preg_match('/Tablet|PlayBook|Silk-Accelerated=true/i', $userAgent)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user agent is from a desktop device.
     *
     * @param string $userAgent
     * @return bool
     */
    public static function isDesktop(string $userAgent): bool
    {
        return !self::isMobile($userAgent) && !self::isTablet($userAgent) && !self::isBot($userAgent);
    }

    /**
     * Check if user agent is from a bot/crawler.
     *
     * @param string $userAgent
     * @return bool
     */
    public static function isBot(string $userAgent): bool
    {
        $botKeywords = [
            'bot', 'crawler', 'spider', 'slurp', 'search',
            'yahoo', 'googlebot', 'bingbot', 'yandex', 'baiduspider',
            'facebookexternalhit', 'twitterbot', 'linkedinbot',
            'applebot', 'duckduckbot', 'semrush', 'ahrefsbot',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($botKeywords as $keyword) {
            if (strpos($userAgentLower, $keyword) !== false) {
                return true;
            }
        }

        // Check for common bot patterns
        $botPatterns = [
            '/Googlebot/i',
            '/Bingbot/i',
            '/Slurp/i',
            '/DuckDuckBot/i',
            '/Baiduspider/i',
            '/YandexBot/i',
            '/Sogou/i',
            '/Exabot/i',
            '/facebot/i',
            '/ia_archiver/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get browser family (Chromium, Firefox, Safari, etc.).
     *
     * @param string $userAgent
     * @return string
     */
    public static function getBrowserFamily(string $userAgent): string
    {
        $browser = self::getBrowser($userAgent);

        $chromiumBrowsers = ['Chrome', 'Edge', 'Opera', 'Samsung Internet'];
        $safariBrowsers = ['Safari'];
        $firefoxBrowsers = ['Firefox'];
        $ieBrowsers = ['Internet Explorer'];

        if (in_array($browser, $chromiumBrowsers)) {
            return 'Chromium';
        }

        if (in_array($browser, $safariBrowsers)) {
            return 'Safari';
        }

        if (in_array($browser, $firefoxBrowsers)) {
            return 'Firefox';
        }

        if (in_array($browser, $ieBrowsers)) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Get OS family (Windows, macOS, Linux, Mobile).
     *
     * @param string $userAgent
     * @return string
     */
    public static function getOsFamily(string $userAgent): string
    {
        $os = self::getOs($userAgent);

        $windowsOS = ['Windows', 'Windows Phone'];
        $appleOS = ['macOS', 'iOS'];
        $linuxOS = ['Linux', 'Ubuntu', 'Android', 'Chrome OS'];

        if (in_array($os, $windowsOS)) {
            return 'Windows';
        }

        if (in_array($os, $appleOS)) {
            return 'Apple';
        }

        if (in_array($os, $linuxOS)) {
            return 'Linux';
        }

        return 'Other';
    }
}
