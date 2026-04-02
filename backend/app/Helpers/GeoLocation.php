<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class GeoLocation
 *
 * Provides geographic location detection from IP addresses.
 * Supports multiple providers with fallback mechanisms.
 * 
 * Note: For production use, consider installing the geoip2 package
 * and using MaxMind's GeoIP2 database for better accuracy.
 */
class GeoLocation
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Cache prefix for geo lookups.
     */
    const CACHE_PREFIX = 'geo:';

    /**
     * Free IP geolocation API endpoint.
     */
    const IPAPI_ENDPOINT = 'http://ip-api.com/json/';

    /**
     * Alternative free API endpoint.
     */
    const IPAPI_COM_ENDPOINT = 'https://ipapi.co/';

    /**
     * Get location data from IP address.
     *
     * @param string|null $ipAddress
     * @return array
     */
    public static function getLocation(?string $ipAddress): array
    {
        if (empty($ipAddress)) {
            return self::getEmptyLocation();
        }

        // Handle localhost and private IPs
        if (self::isPrivateIp($ipAddress)) {
            return self::getLocalLocation();
        }

        // Check cache first
        $cacheKey = self::CACHE_PREFIX . md5($ipAddress);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Try to get location from API
        $location = self::fetchFromApi($ipAddress);

        // Cache the result
        Cache::put($cacheKey, $location, self::CACHE_TTL);

        return $location;
    }

    /**
     * Fetch location data from external API.
     *
     * @param string $ipAddress
     * @return array
     */
    protected static function fetchFromApi(string $ipAddress): array
    {
        try {
            // Try ip-api.com first (free, no rate limit for non-commercial)
            $response = Http::timeout(5)->get(self::IPAPI_ENDPOINT . $ipAddress);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'region_code' => $data['region'] ?? null,
                        'city' => $data['city'] ?? null,
                        'zip' => $data['zip'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                        'isp' => $data['isp'] ?? null,
                        'organization' => $data['org'] ?? null,
                        'as' => $data['as'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('GeoLocation API fetch failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to ipapi.co
        try {
            $response = Http::timeout(5)->get(self::IPAPI_COM_ENDPOINT . $ipAddress . '/json/');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'region' => $data['region'] ?? null,
                    'region_code' => $data['region_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['postal'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'organization' => null,
                    'as' => $data['asn'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('GeoLocation fallback API failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
        }

        // Return empty location if all APIs fail
        return self::getEmptyLocation();
    }

    /**
     * Get country from IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function getCountry(?string $ipAddress): ?string
    {
        $location = self::getLocation($ipAddress);
        return $location['country'];
    }

    /**
     * Get country code from IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function getCountryCode(?string $ipAddress): ?string
    {
        $location = self::getLocation($ipAddress);
        return $location['country_code'];
    }

    /**
     * Get city from IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function getCity(?string $ipAddress): ?string
    {
        $location = self::getLocation($ipAddress);
        return $location['city'];
    }

    /**
     * Get region from IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function getRegion(?string $ipAddress): ?string
    {
        $location = self::getLocation($ipAddress);
        return $location['region'];
    }

    /**
     * Get timezone from IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function getTimezone(?string $ipAddress): ?string
    {
        $location = self::getLocation($ipAddress);
        return $location['timezone'];
    }

    /**
     * Get coordinates from IP address.
     *
     * @param string|null $ipAddress
     * @return array
     */
    public static function getCoordinates(?string $ipAddress): array
    {
        $location = self::getLocation($ipAddress);
        return [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ];
    }

    /**
     * Check if IP is a private/local address.
     *
     * @param string $ipAddress
     * @return bool
     */
    public static function isPrivateIp(string $ipAddress): bool
    {
        // Localhost
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return true;
        }

        // Private IPv4 ranges
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
        ];

        $ipLong = ip2long($ipAddress);

        if ($ipLong === false) {
            return true; // Invalid IP, treat as private
        }

        foreach ($privateRanges as $range) {
            if (self::ipInRange($ipAddress, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in a CIDR range.
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    protected static function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Get empty location array.
     *
     * @return array
     */
    protected static function getEmptyLocation(): array
    {
        return [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'region_code' => null,
            'city' => null,
            'zip' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'isp' => null,
            'organization' => null,
            'as' => null,
        ];
    }

    /**
     * Get local location (for development).
     *
     * @return array
     */
    protected static function getLocalLocation(): array
    {
        return [
            'country' => 'Local',
            'country_code' => 'XX',
            'region' => 'Development',
            'region_code' => 'DEV',
            'city' => 'Localhost',
            'zip' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => date_default_timezone_get(),
            'isp' => 'Local Network',
            'organization' => 'Development',
            'as' => null,
        ];
    }

    /**
     * Clear cache for specific IP.
     *
     * @param string $ipAddress
     * @return bool
     */
    public static function clearCache(string $ipAddress): bool
    {
        $cacheKey = self::CACHE_PREFIX . md5($ipAddress);
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all geo cache.
     *
     * @return bool
     */
    public static function clearAllCache(): bool
    {
        // Note: This requires cache tags support
        try {
            Cache::tags(['geo'])->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get location from request IP.
     *
     * @return array
     */
    public static function getLocationFromRequest(): array
    {
        return self::getLocation(request()->ip());
    }

    /**
     * Batch get locations for multiple IPs.
     *
     * @param array $ipAddresses
     * @return array
     */
    public static function batchGetLocations(array $ipAddresses): array
    {
        $results = [];

        foreach ($ipAddresses as $ip) {
            $results[$ip] = self::getLocation($ip);
        }

        return $results;
    }

    /**
     * Get country flag emoji from country code.
     *
     * @param string|null $countryCode
     * @return string|null
     */
    public static function getCountryFlag(?string $countryCode): ?string
    {
        if (empty($countryCode) || strlen($countryCode) !== 2) {
            return null;
        }

        // Convert country code to flag emoji
        $flag = mb_convert_encoding(
            '&#' . (127397 + ord(strtoupper($countryCode)[0])) . ';',
            'UTF-8',
            'HTML-ENTITIES'
        );
        $flag .= mb_convert_encoding(
            '&#' . (127397 + ord(strtoupper($countryCode)[1])) . ';',
            'UTF-8',
            'HTML-ENTITIES'
        );

        return $flag;
    }

    /**
     * Get continent from country code.
     *
     * @param string|null $countryCode
     * @return string|null
     */
    public static function getContinent(?string $countryCode): ?string
    {
        if (empty($countryCode)) {
            return null;
        }

        $continentMap = [
            // North America
            'US' => 'North America', 'CA' => 'North America', 'MX' => 'North America',
            // South America
            'BR' => 'South America', 'AR' => 'South America', 'CL' => 'South America',
            // Europe
            'GB' => 'Europe', 'DE' => 'Europe', 'FR' => 'Europe', 'IT' => 'Europe',
            'ES' => 'Europe', 'NL' => 'Europe', 'PL' => 'Europe', 'UA' => 'Europe',
            // Asia
            'CN' => 'Asia', 'JP' => 'Asia', 'IN' => 'Asia', 'KR' => 'Asia',
            'ID' => 'Asia', 'TH' => 'Asia', 'VN' => 'Asia', 'PH' => 'Asia',
            // Africa
            'NG' => 'Africa', 'EG' => 'Africa', 'ZA' => 'Africa', 'KE' => 'Africa',
            // Oceania
            'AU' => 'Oceania', 'NZ' => 'Oceania',
        ];

        return $continentMap[strtoupper($countryCode)] ?? 'Unknown';
    }
}
