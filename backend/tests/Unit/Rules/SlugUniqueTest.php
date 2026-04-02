<?php

namespace Tests\Unit\Rules;

use App\Rules\SlugUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class SlugUniqueTest
 *
 * Tests for the SlugUnique validation rule.
 */
class SlugUniqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        \App\Models\Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content',
            'user_id' => 1,
            'category_id' => 1,
        ]);
    }

    public function test_validation_fails_when_slug_exists(): void
    {
        $rule = new SlugUnique('posts', 'slug');
        
        $validator = validator(
            ['slug' => 'test-post'],
            ['slug' => $rule]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('The slug has already been taken.', $validator->errors()->first('slug'));
    }

    public function test_validation_passes_when_slug_is_unique(): void
    {
        $rule = new SlugUnique('posts', 'slug');
        
        $validator = validator(
            ['slug' => 'unique-slug'],
            ['slug' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_when_excluding_current_record(): void
    {
        $post = \App\Models\Post::first();
        $rule = new SlugUnique('posts', 'slug', $post->id);
        
        $validator = validator(
            ['slug' => 'test-post'],
            ['slug' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_empty_slug(): void
    {
        $rule = new SlugUnique('posts', 'slug');
        
        $validator = validator(
            ['slug' => ''],
            ['slug' => $rule]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_with_model_exclude(): void
    {
        $post = \App\Models\Post::first();
        $rule = new SlugUnique('posts', 'slug', $post);
        
        $validator = validator(
            ['slug' => 'test-post'],
            ['slug' => $rule]
        );

        $this->assertTrue($validator->passes());
    }
}
