<?php

namespace Tests\Unit\Rules;

use App\Rules\ImageDimensions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Class ImageDimensionsTest
 *
 * Tests for the ImageDimensions validation rule.
 */
class ImageDimensionsTest extends TestCase
{
    public function test_validation_passes_with_valid_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $rule = new ImageDimensions(minWidth: 100, minHeight: 100);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_width_too_small(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 50, 600);
        $rule = new ImageDimensions(minWidth: 100);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('width must be at least', $validator->errors()->first('image'));
    }

    public function test_validation_fails_when_height_too_small(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 50);
        $rule = new ImageDimensions(minHeight: 100);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('height must be at least', $validator->errors()->first('image'));
    }

    public function test_validation_fails_when_width_too_large(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 5000, 600);
        $rule = new ImageDimensions(maxWidth: 4096);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('width must not exceed', $validator->errors()->first('image'));
    }

    public function test_validation_fails_when_height_too_large(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 5000);
        $rule = new ImageDimensions(maxHeight: 4096);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('height must not exceed', $validator->errors()->first('image'));
    }

    public function test_validation_fails_when_file_too_large(): void
    {
        // Create a file that's too large (simulated)
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(6000); // 6000 KB
        $rule = new ImageDimensions(maxSize: 5120); // 5120 KB = 5MB
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('size must not exceed', $validator->errors()->first('image'));
    }

    public function test_validation_fails_with_non_image_file(): void
    {
        $file = UploadedFile::fake()->create('test.pdf');
        $rule = new ImageDimensions();
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('must be an image', $validator->errors()->first('image'));
    }

    public function test_validation_passes_with_empty_file(): void
    {
        $rule = new ImageDimensions();
        
        $validator = validator(
            ['image' => null],
            ['image' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_custom_error_messages(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 50, 600);
        $rule = (new ImageDimensions(minWidth: 100))->withMessages([
            'min_width' => 'Custom width error',
        ]);
        
        $validator = validator(
            ['image' => $file],
            ['image' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('Custom width error', $validator->errors()->first('image'));
    }
}
