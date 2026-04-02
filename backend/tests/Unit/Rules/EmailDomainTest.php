<?php

namespace Tests\Unit\Rules;

use App\Rules\EmailDomain;
use Tests\TestCase;

/**
 * Class EmailDomainTest
 *
 * Tests for the EmailDomain validation rule.
 */
class EmailDomainTest extends TestCase
{
    public function test_validation_passes_with_allowed_domain(): void
    {
        $rule = new EmailDomain(['example.com', 'test.com']);
        
        $validator = validator(
            ['email' => 'user@example.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_disallowed_domain(): void
    {
        $rule = new EmailDomain(['example.com', 'test.com']);
        
        $validator = validator(
            ['email' => 'user@other.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('must be from one of the following domains', $validator->errors()->first('email'));
    }

    public function test_validation_is_case_insensitive(): void
    {
        $rule = new EmailDomain(['EXAMPLE.COM']);
        
        $validator = validator(
            ['email' => 'user@example.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_subdomain_when_allowed(): void
    {
        $rule = new EmailDomain(['example.com'], true);
        
        $validator = validator(
            ['email' => 'user@mail.example.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_subdomain_when_not_allowed(): void
    {
        $rule = new EmailDomain(['example.com'], false);
        
        $validator = validator(
            ['email' => 'user@mail.example.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_validation_passes_with_empty_email(): void
    {
        $rule = new EmailDomain(['example.com']);
        
        $validator = validator(
            ['email' => ''],
            ['email' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_email(): void
    {
        $rule = new EmailDomain(['example.com']);
        
        $validator = validator(
            ['email' => 'invalid-email'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_custom_error_message(): void
    {
        $rule = (new EmailDomain(['example.com']))->withMessage('Custom error message');
        
        $validator = validator(
            ['email' => 'user@other.com'],
            ['email' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('Custom error message', $validator->errors()->first('email'));
    }
}
