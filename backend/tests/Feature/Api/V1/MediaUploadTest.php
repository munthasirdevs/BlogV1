<?php

namespace Tests\Feature\Api\V1;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class MediaUploadTest
 *
 * Feature tests for media upload functionality.
 * 
 * Tests cover:
 * - Single file upload
 * - Multiple file upload
 * - File validation (type, size, dimensions)
 * - Authentication requirements
 * - Authorization checks
 */
class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected string $testImagePath;
    protected string $testDocumentPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        
        $this->user = User::factory()->create(['email' => 'user@test.com']);
        $this->user->assignRole('author');
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');
        
        // Create test image
        $this->testImagePath = tempnam(sys_get_temp_dir(), 'test_image') . '.jpg';
        imagejpeg(imagecreatetruecolor(100, 100), $this->testImagePath);
        
        // Create test document
        $this->testDocumentPath = tempnam(sys_get_temp_dir(), 'test_doc') . '.pdf';
        file_put_contents($this->testDocumentPath, '%PDF-1.4 test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
        if (file_exists($this->testDocumentPath)) {
            unlink($this->testDocumentPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function guest_cannot_upload_files(): void
    {
        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_upload_image(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'alt_text' => 'Test image',
            'title' => 'Test Title',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'File uploaded successfully',
            ])
            ->assertJsonPath('data.filename', fn($value) => str_ends_with($value, '.jpg'));

        $this->assertDatabaseHas('media', [
            'alt_text' => 'Test image',
            'title' => 'Test Title',
        ]);
    }

    /** @test */
    public function authenticated_user_can_upload_document(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.pdf', 'Test PDF content'),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function upload_requires_file(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    /** @test */
    public function upload_rejects_executable_files(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('malicious.exe', 'executable content'),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function upload_rejects_oversized_images(): void
    {
        $this->actingAs($this->user);

        // Create a file larger than 5MB
        $largeFile = UploadedFile::fake()->image('large.jpg', 100, 100)->size(6000); // 6MB

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $largeFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    /** @test */
    public function upload_rejects_images_exceeding_dimension_limit(): void
    {
        $this->actingAs($this->user);

        // Create image larger than 4000x4000
        $largeImage = tempnam(sys_get_temp_dir(), 'large_image') . '.jpg';
        imagejpeg(imagecreatetruecolor(5000, 5000), $largeImage);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('large.jpg', file_get_contents($largeImage)),
        ]);

        unlink($largeImage);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function upload_stores_file_in_organized_folder_structure(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'collection_name' => 'posts',
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $expectedPathPattern = '/media\/posts\/\d{4}\/\d{2}\/\d{2}\/.*\.jpg/';
        
        $this->assertMatchesRegularExpression($expectedPathPattern, $media->path);
    }

    /** @test */
    public function upload_generates_unique_filename(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        
        // Filename should contain timestamp and random string
        $this->assertMatchesRegularExpression('/\d{14}_[a-zA-Z0-9]{16}\.jpg/', $media->filename);
    }

    /** @test */
    public function upload_preserves_original_filename(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('my-original-image.jpg', 100, 100),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.original_filename', 'my-original-image.jpg');

        $this->assertDatabaseHas('media', [
            'original_filename' => 'my-original-image.jpg',
        ]);
    }

    /** @test */
    public function upload_prevents_duplicate_uploads(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        // First upload
        $response1 = $this->postJson('/api/v1/media/upload', ['file' => $file]);
        $response1->assertStatus(201);

        $firstMediaId = $response1->json('data.id');

        // Second upload with same file
        $response2 = $this->postJson('/api/v1/media/upload', ['file' => $file]);
        $response2->assertStatus(201);

        // Should return the same media record
        $this->assertEquals($firstMediaId, $response2->json('data.id'));
    }

    /** @test */
    public function upload_multiple_allows_up_to_10_files(): void
    {
        $this->actingAs($this->user);

        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $files[] = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
        }

        $response = $this->postJson('/api/v1/media/upload-multiple', [
            'files' => $files,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('meta.successful_count', 10);
    }

    /** @test */
    public function upload_multiple_rejects_more_than_10_files(): void
    {
        $this->actingAs($this->user);

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
        }

        $response = $this->postJson('/api/v1/media/upload-multiple', [
            'files' => $files,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('files');
    }

    /** @test */
    public function upload_multiple_handles_partial_failures(): void
    {
        $this->actingAs($this->user);

        $files = [
            UploadedFile::fake()->image('valid.jpg', 100, 100),
            UploadedFile::fake()->createWithContent('invalid.exe', 'executable'),
            UploadedFile::fake()->image('valid2.jpg', 100, 100),
        ];

        $response = $this->postJson('/api/v1/media/upload-multiple', [
            'files' => $files,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('meta.successful_count', 2)
            ->assertJsonPath('meta.failed_count', 1);
    }

    /** @test */
    public function upload_with_metadata_stores_alt_text(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'alt_text' => 'Descriptive alt text for accessibility',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.alt_text', 'Descriptive alt text for accessibility');
    }

    /** @test */
    public function upload_with_metadata_stores_title_and_caption(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'title' => 'Image Title',
            'caption' => 'Image caption text',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Image Title',
                    'caption' => 'Image caption text',
                ],
            ]);
    }

    /** @test */
    public function upload_sets_uploader_id_to_authenticated_user(): void
    {
        $this->actingAs($this->user);

        $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        ]);

        $this->assertDatabaseHas('media', [
            'uploader_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function upload_stores_file_dimensions_for_images(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $this->assertEquals(800, $media->dimensions['width']);
        $this->assertEquals(600, $media->dimensions['height']);
    }

    /** @test */
    public function upload_does_not_store_dimensions_for_documents(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.pdf', 'PDF content'),
        ]);

        $response->assertStatus(201);

        $media = Media::first();
        $this->assertNull($media->dimensions);
    }
}
