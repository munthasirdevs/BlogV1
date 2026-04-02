<?php

namespace Tests\Feature\Api\V1;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class MediaValidationTest
 *
 * Feature tests for media upload validation.
 * 
 * Tests cover:
 * - Invalid file types
 * - Oversized files
 * - Invalid MIME types
 * - Clear error messages
 */
class MediaValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function upload_rejects_executable_file_types(): void
    {
        $this->actingAs($this->user);

        $executables = [
            ['malicious.exe', 'application/x-msdownload'],
            ['script.bat', 'application/x-bat'],
            ['program.com', 'application/x-com'],
        ];

        foreach ($executables as [$filename, $mimeType]) {
            $response = $this->postJson('/api/v1/media/upload', [
                'file' => UploadedFile::fake()->createWithContent($filename, 'executable content', $mimeType),
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                ]);
        }
    }

    /** @test */
    public function upload_rejects_script_files(): void
    {
        $this->actingAs($this->user);

        $scripts = [
            ['script.php', 'application/x-php'],
            ['script.py', 'application/x-python'],
            ['script.sh', 'application/x-sh'],
        ];

        foreach ($scripts as [$filename, $mimeType]) {
            $response = $this->postJson('/api/v1/media/upload', [
                'file' => UploadedFile::fake()->createWithContent($filename, 'script content', $mimeType),
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function upload_rejects_html_files(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('page.html', '<html></html>', 'text/html'),
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function upload_accepts_valid_image_types(): void
    {
        $this->actingAs($this->user);

        $validImages = [
            ['test.jpg', 'image/jpeg'],
            ['test.png', 'image/png'],
            ['test.gif', 'image/gif'],
            ['test.webp', 'image/webp'],
        ];

        foreach ($validImages as [$filename, $mimeType]) {
            $response = $this->postJson('/api/v1/media/upload', [
                'file' => UploadedFile::fake()->createWithContent($filename, 'image content', $mimeType),
            ]);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function upload_accepts_valid_document_types(): void
    {
        $this->actingAs($this->user);

        $validDocuments = [
            ['test.pdf', 'application/pdf'],
            ['test.txt', 'text/plain'],
        ];

        foreach ($validDocuments as [$filename, $mimeType]) {
            $response = $this->postJson('/api/v1/media/upload', [
                'file' => UploadedFile::fake()->createWithContent($filename, 'document content', $mimeType),
            ]);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function upload_rejects_files_exceeding_5mb_image_limit(): void
    {
        $this->actingAs($this->user);

        // Create file larger than 5MB (5242880 bytes)
        $largeFile = UploadedFile::fake()->image('large.jpg', 100, 100)->size(6000); // 6MB

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $largeFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    /** @test */
    public function upload_accepts_files_at_exact_size_limit(): void
    {
        $this->actingAs($this->user);

        // Create file at exactly 5MB (or very close)
        $exactFile = UploadedFile::fake()->image('exact.jpg', 100, 100)->size(5120); // 5MB in KB

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $exactFile,
        ]);

        // Should pass validation (might fail at storage level, but that's different)
        $response->assertStatus(201);
    }

    /** @test */
    public function upload_rejects_images_exceeding_4000x4000_dimensions(): void
    {
        $this->actingAs($this->user);

        // Create image larger than 4000x4000
        $largeImage = tempnam(sys_get_temp_dir(), 'large') . '.jpg';
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
    public function upload_accepts_images_at_exact_dimension_limit(): void
    {
        $this->actingAs($this->user);

        // Create image at exactly 4000x4000
        $exactImage = tempnam(sys_get_temp_dir(), 'exact') . '.jpg';
        imagejpeg(imagecreatetruecolor(4000, 4000), $exactImage);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('exact.jpg', file_get_contents($exactImage)),
        ]);

        unlink($exactImage);

        $response->assertStatus(201);
    }

    /** @test */
    public function upload_rejects_mismatched_mime_and_extension(): void
    {
        $this->actingAs($this->user);

        // Create a PNG file but name it .jpg
        $imagePath = tempnam(sys_get_temp_dir(), 'mismatch') . '.png';
        imagepng(imagecreatetruecolor(100, 100), $imagePath);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('fake.jpg', file_get_contents($imagePath)),
        ]);

        unlink($imagePath);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function upload_returns_clear_error_message_for_invalid_type(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('malicious.exe', 'exe content'),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);

        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function upload_returns_clear_error_message_for_oversized_file(): void
    {
        $this->actingAs($this->user);

        $largeFile = UploadedFile::fake()->image('large.jpg', 100, 100)->size(6000);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $largeFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $errorMessages = $response->json('errors.file');
        $this->assertIsArray($errorMessages);
        $this->assertNotEmpty($errorMessages);
    }

    /** @test */
    public function upload_returns_clear_error_message_for_large_dimensions(): void
    {
        $this->actingAs($this->user);

        $largeImage = tempnam(sys_get_temp_dir(), 'large') . '.jpg';
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
    public function upload_multiple_validates_each_file(): void
    {
        $this->actingAs($this->user);

        $files = [
            UploadedFile::fake()->image('valid.jpg', 100, 100),
            UploadedFile::fake()->createWithContent('invalid.exe', 'exe'),
            UploadedFile::fake()->image('valid2.jpg', 100, 100),
        ];

        $response = $this->postJson('/api/v1/media/upload-multiple', [
            'files' => $files,
        ]);

        // Should handle partial failures
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'successful_count' => 2,
                    'failed_count' => 1,
                ],
            ]);
    }

    /** @test */
    public function upload_multiple_rejects_array_with_too_many_files(): void
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
    public function upload_validates_alt_text_max_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'alt_text' => str_repeat('a', 300), // Exceeds 255 char limit
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('alt_text');
    }

    /** @test */
    public function upload_validates_title_max_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'title' => str_repeat('a', 300),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    /** @test */
    public function upload_validates_caption_max_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
            'caption' => str_repeat('a', 600),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('caption');
    }

    /** @test */
    public function upload_accepts_svg_files(): void
    {
        $this->actingAs($this->user);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('test.svg', $svgContent, 'image/svg+xml'),
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function upload_handles_empty_file_gracefully(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => null,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }
}
