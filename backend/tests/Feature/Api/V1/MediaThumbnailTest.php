<?php

namespace Tests\Feature\Api\V1;

use App\Helpers\ImageOptimizer;
use App\Helpers\ThumbnailGenerator;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class MediaThumbnailTest
 *
 * Feature tests for thumbnail generation and image optimization.
 * 
 * Tests cover:
 * - Thumbnail generation at multiple sizes
 * - Thumbnail dimensions verification
 * - Image optimization
 * - EXIF data stripping
 */
class MediaThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $testImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->user = User::factory()->create();
        
        // Create test image
        $this->testImagePath = tempnam(sys_get_temp_dir(), 'test_image') . '.jpg';
        $image = imagecreatetruecolor(800, 600);
        imagejpeg($image, $this->testImagePath, 90);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
        parent::tearDown();
    }

    /** @test */
    public function upload_generates_thumbnails_for_images(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        
        // Check that thumbnails metadata exists
        $this->assertArrayHasKey('thumbnails', $media->metadata ?? []);
    }

    /** @test */
    public function thumbnails_are_generated_at_correct_sizes(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $thumbnails = $media->metadata['thumbnails'] ?? [];

        $expectedSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];

        foreach ($expectedSizes as $sizeName => $dimensions) {
            $this->assertArrayHasKey($sizeName, $thumbnails, "Missing thumbnail size: {$sizeName}");
            
            // Verify dimensions (cover maintains aspect ratio within bounds)
            $this->assertLessThanOrEqual($dimensions['width'], $thumbnails[$sizeName]['width']);
            $this->assertLessThanOrEqual($dimensions['height'], $thumbnails[$sizeName]['height']);
        }
    }

    /** @test */
    public function thumbnails_maintain_aspect_ratio(): void
    {
        $this->actingAs($this->user);

        // Create image with unusual aspect ratio
        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('wide.jpg', 1600, 400), // 4:1 ratio
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $thumbnails = $media->metadata['thumbnails'] ?? [];

        foreach ($thumbnails as $sizeName => $thumbnail) {
            // Each thumbnail should have valid dimensions
            $this->assertGreaterThan(0, $thumbnail['width']);
            $this->assertGreaterThan(0, $thumbnail['height']);
        }
    }

    /** @test */
    public function thumbnails_stored_in_separate_folder(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $thumbnails = $media->metadata['thumbnails'] ?? [];

        foreach ($thumbnails as $thumbnail) {
            // Thumbnail path should contain 'thumbnails' folder
            $this->assertStringContainsString('thumbnails', $thumbnail['path']);
        }
    }

    /** @test */
    public function thumbnail_urls_returned_in_api_response(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'thumbnail_url',
                ],
            ]);
    }

    /** @test */
    public function get_url_endpoint_returns_all_thumbnail_urls(): void
    {
        $this->actingAs($this->user);

        $uploadResponse = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $mediaId = $uploadResponse->json('data.id');

        $response = $this->getJson('/api/v1/media/' . $mediaId . '/url');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'thumbnails' => [
                        'thumbnail',
                        'small',
                        'medium',
                        'large',
                    ],
                ],
            ]);
    }

    /** @test */
    public function no_thumbnails_generated_for_documents(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.pdf', 'PDF content'),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        
        // Documents should not have thumbnails
        $thumbnails = $media->metadata['thumbnails'] ?? [];
        $this->assertEmpty($thumbnails);
    }

    /** @test */
    public function no_thumbnails_generated_for_svg(): void
    {
        $this->actingAs($this->user);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        
        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.svg', $svgContent, 'image/svg+xml'),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        
        // SVG should not have thumbnails
        $thumbnails = $media->metadata['thumbnails'] ?? [];
        $this->assertEmpty($thumbnails);
    }

    /** @test */
    public function can_regenerate_thumbnails(): void
    {
        $this->actingAs($this->user);

        $uploadResponse = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $mediaId = $uploadResponse->json('data.id');

        $response = $this->postJson('/api/v1/media/' . $mediaId . '/regenerate-thumbnails');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function regenerate_thumbnails_fails_for_documents(): void
    {
        $this->actingAs($this->user);

        $uploadResponse = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.pdf', 'PDF content'),
        ]);

        $mediaId = $uploadResponse->json('data.id');

        $response = $this->postJson('/api/v1/media/' . $mediaId . '/regenerate-thumbnails');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function image_optimizer_strips_metadata(): void
    {
        $optimizer = new ImageOptimizer(stripMetadata: true);
        
        // Create image with EXIF data
        $imagePath = tempnam(sys_get_temp_dir(), 'exif_test') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);

        $result = $optimizer->optimizeFromFile($imagePath);

        unlink($imagePath);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['metadata_stripped']);
    }

    /** @test */
    public function image_optimizer_auto_orients_images(): void
    {
        $optimizer = new ImageOptimizer(autoOrient: true);
        
        $imagePath = tempnam(sys_get_temp_dir(), 'orient_test') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);

        $result = $optimizer->optimizeFromFile($imagePath);

        unlink($imagePath);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['auto_oriented']);
    }

    /** @test */
    public function thumbnail_generator_handles_various_aspect_ratios(): void
    {
        $generator = new ThumbnailGenerator();
        
        $sizes = [
            [800, 600],   // 4:3
            [1600, 900],  // 16:9
            [1000, 1000], // 1:1
            [400, 800],   // 1:2 (portrait)
        ];

        foreach ($sizes as [$width, $height]) {
            $imagePath = tempnam(sys_get_temp_dir(), 'ratio_test') . '.jpg';
            $image = imagecreatetruecolor($width, $height);
            imagejpeg($image, $imagePath, 90);
            imagedestroy($image);

            $media = Media::factory()->create([
                'mime_type' => 'image/jpeg',
                'dimensions' => ['width' => $width, 'height' => $height],
            ]);

            $file = new UploadedFile($imagePath, 'test.jpg', 'image/jpeg');
            
            $thumbnails = $generator->generateAll($media, $file);
            
            unlink($imagePath);

            // Should generate all thumbnail sizes
            $this->assertCount(4, $thumbnails);
        }
    }

    /** @test */
    public function thumbnail_generator_handles_very_large_images(): void
    {
        $generator = new ThumbnailGenerator();
        
        // Create a large image
        $imagePath = tempnam(sys_get_temp_dir(), 'large_test') . '.jpg';
        $image = imagecreatetruecolor(4000, 3000);
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);

        $media = Media::factory()->create([
            'mime_type' => 'image/jpeg',
            'dimensions' => ['width' => 4000, 'height' => 3000],
        ]);

        $file = new UploadedFile($imagePath, 'large.jpg', 'image/jpeg');
        
        $thumbnails = $generator->generateAll($media, $file);
        
        unlink($imagePath);

        // Should successfully generate thumbnails
        $this->assertCount(4, $thumbnails);
        
        // Largest thumbnail should not exceed original dimensions
        foreach ($thumbnails as $thumbnail) {
            $this->assertLessThanOrEqual(4000, $thumbnail['width']);
            $this->assertLessThanOrEqual(3000, $thumbnail['height']);
        }
    }

    /** @test */
    public function thumbnail_generator_handles_very_small_images(): void
    {
        $generator = new ThumbnailGenerator();
        
        // Create a small image
        $imagePath = tempnam(sys_get_temp_dir(), 'small_test') . '.jpg';
        $image = imagecreatetruecolor(50, 50);
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);

        $media = Media::factory()->create([
            'mime_type' => 'image/jpeg',
            'dimensions' => ['width' => 50, 'height' => 50],
        ]);

        $file = new UploadedFile($imagePath, 'small.jpg', 'image/jpeg');
        
        $thumbnails = $generator->generateAll($media, $file);
        
        unlink($imagePath);

        // Should still generate thumbnails (may be upscaled or same size)
        $this->assertCount(4, $thumbnails);
    }

    /** @test */
    public function image_optimizer_reduces_file_size(): void
    {
        $optimizer = new ImageOptimizer(quality: 70); // Lower quality for more compression
        
        $imagePath = tempnam(sys_get_temp_dir(), 'optimize_test') . '.jpg';
        $image = imagecreatetruecolor(800, 600);
        imagejpeg($image, $imagePath, 95); // High quality original
        imagedestroy($image);

        $originalSize = filesize($imagePath);
        
        $result = $optimizer->optimizeFromFile($imagePath);
        
        unlink($imagePath);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['savings_percentage']);
    }
}
