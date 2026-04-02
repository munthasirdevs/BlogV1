<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helpers;
use Tests\TestCase;

/**
 * Class HelpersTest
 *
 * Tests for the helper functions.
 */
class HelpersTest extends TestCase
{
    public function test_generate_slug(): void
    {
        $slug = Helpers::generateSlug('Hello World', 'posts', 'slug');
        $this->assertEquals('hello-world', $slug);
    }

    public function test_sanitize_html(): void
    {
        $html = '<p>Hello <script>alert("XSS")</script> World</p>';
        $clean = Helpers::sanitizeHtml($html);
        
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('<p>', $clean);
    }

    public function test_escape_html(): void
    {
        $string = '<script>alert("XSS")</script>';
        $escaped = Helpers::escapeHtml($string);
        
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    public function test_escape_js(): void
    {
        $string = "'; alert('XSS'); //";
        $escaped = Helpers::escapeJs($string);
        
        $this->assertStringContainsString('\\\'', $escaped);
    }

    public function test_calculate_reading_time(): void
    {
        $content = str_repeat('word ', 400);
        $time = Helpers::calculateReadingTime($content, 200);
        
        $this->assertEquals(2, $time);
    }

    public function test_get_word_count(): void
    {
        $content = 'Hello world this is a test';
        $count = Helpers::getWordCount($content);
        
        $this->assertEquals(6, $count);
    }

    public function test_format_file_size_bytes(): void
    {
        $formatted = Helpers::formatFileSize(500);
        $this->assertEquals('500 B', $formatted);
    }

    public function test_format_file_size_kb(): void
    {
        $formatted = Helpers::formatFileSize(1024);
        $this->assertEquals('1 KB', $formatted);
    }

    public function test_format_file_size_mb(): void
    {
        $formatted = Helpers::formatFileSize(1024 * 1024);
        $this->assertEquals('1 MB', $formatted);
    }

    public function test_format_iso8601(): void
    {
        $date = new \DateTime('2024-01-15 10:00:00');
        $formatted = Helpers::formatIso8601($date);
        
        $this->assertStringContainsString('2024-01-15', $formatted);
    }

    public function test_format_iso8601_null(): void
    {
        $formatted = Helpers::formatIso8601(null);
        $this->assertNull($formatted);
    }

    public function test_random_string(): void
    {
        $string = Helpers::randomString(32);
        $this->assertEquals(32, strlen($string));
    }

    public function test_generate_token(): void
    {
        $token = Helpers::generateToken(64);
        $this->assertEquals(64, strlen($token));
    }

    public function test_truncate(): void
    {
        $text = 'This is a long text that needs to be truncated';
        $truncated = Helpers::truncate($text, 20);
        
        $this->assertLessThanOrEqual(23, strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }

    public function test_truncate_short_text(): void
    {
        $text = 'Short';
        $truncated = Helpers::truncate($text, 20);
        
        $this->assertEquals('Short', $truncated);
    }

    public function test_parse_basic_markdown(): void
    {
        $text = '**bold** and *italic* and `code`';
        $parsed = Helpers::parseBasicMarkdown($text);
        
        $this->assertStringContainsString('<strong>bold</strong>', $parsed);
        $this->assertStringContainsString('<em>italic</em>', $parsed);
        $this->assertStringContainsString('<code>code</code>', $parsed);
    }

    public function test_extract_first_image(): void
    {
        $content = '<p>Text</p><img src="https://example.com/image.jpg" alt="Image"><p>More text</p>';
        $url = Helpers::extractFirstImage($content);
        
        $this->assertEquals('https://example.com/image.jpg', $url);
    }

    public function test_get_email_domain(): void
    {
        $domain = Helpers::getEmailDomain('user@example.com');
        $this->assertEquals('example.com', $domain);
    }

    public function test_validate_email_domain(): void
    {
        $valid = Helpers::validateEmailDomain('user@example.com', ['example.com', 'test.com']);
        $this->assertTrue($valid);

        $invalid = Helpers::validateEmailDomain('user@other.com', ['example.com', 'test.com']);
        $this->assertFalse($invalid);
    }

    public function test_validate_email_domain_with_subdomains(): void
    {
        $valid = Helpers::validateEmailDomain('user@mail.example.com', ['example.com'], true);
        $this->assertTrue($valid);

        $invalid = Helpers::validateEmailDomain('user@mail.example.com', ['example.com'], false);
        $this->assertFalse($invalid);
    }

    public function test_mask_email(): void
    {
        $masked = Helpers::maskEmail('john.doe@example.com');
        $this->assertStringStartsWith('jo', $masked);
        $this->assertStringEndsWith('@example.com', $masked);
        $this->assertStringContainsString('***', $masked);
    }

    public function test_mask_phone(): void
    {
        $masked = Helpers::maskPhone('1234567890', 4);
        $this->assertStringEndsWith('7890', $masked);
        $this->assertStringContainsString('******', $masked);
    }

    public function test_gravatar(): void
    {
        $url = Helpers::gravatar('user@example.com', 100, 'identicon');
        $this->assertStringContainsString('gravatar.com/avatar/', $url);
        $this->assertStringContainsString('s=100', $url);
        $this->assertStringContainsString('d=identicon', $url);
    }

    public function test_clean_text(): void
    {
        $text = "Hello\r\nWorld  with   spaces";
        $cleaned = Helpers::cleanText($text);
        
        $this->assertStringContainsString('Hello', $cleaned);
        $this->assertStringContainsString('World', $cleaned);
    }

    public function test_array_to_query(): void
    {
        $params = ['page' => 1, 'per_page' => 10, 'sort' => 'created_at'];
        $query = Helpers::arrayToQuery($params);
        
        $this->assertStringContainsString('page=1', $query);
        $this->assertStringContainsString('per_page=10', $query);
    }

    public function test_query_to_array(): void
    {
        $query = 'page=1&per_page=10&sort=created_at';
        $params = Helpers::queryToArray($query);
        
        $this->assertEquals(1, $params['page']);
        $this->assertEquals(10, $params['per_page']);
    }

    public function test_human_file_size(): void
    {
        $formatted = Helpers::humanFileSize(2 * 1024 * 1024);
        $this->assertEquals('2 MB', $formatted);
    }

    public function test_global_helper_functions(): void
    {
        // Test that global helper functions work
        $this->assertEquals('hello-world', generate_slug('Hello World'));
        $this->assertStringContainsString('&lt;', escape_html('<'));
        $this->assertGreaterThan(0, reading_time(str_repeat('word ', 200)));
        $this->assertEquals('1 KB', format_file_size(1024));
    }
}
