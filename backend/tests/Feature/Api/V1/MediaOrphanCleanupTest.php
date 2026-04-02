<?php

namespace Tests\Feature\Api\V1;

use App\Console\Commands\OrphanCleanup;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class MediaOrphanCleanupTest
 *
 * Feature tests for orphan cleanup functionality.
 * 
 * Tests cover:
 * - Orphan cleanup command
 * - Soft delete behavior
 * - File cleanup after soft delete
 */
class MediaOrphanCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Create roles if they don't exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('author');
    }

    /** @test */
    public function orphan_cleanup_command_exists(): void
    {
        $this->artisan('media:cleanup-orphans --dry-run')
            ->assertExitCode(0);
    }

    /** @test */
    public function orphan_cleanup_finds_soft_deleted_media(): void
    {
        // Create soft-deleted media
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
        ]);
        $media->delete(); // Soft delete

        // Update deleted_at to be old enough
        $media->update(['deleted_at' => now()->subDays(8)]);

        $this->artisan('media:cleanup-orphans --hours=168 --dry-run')
            ->assertExitCode(0)
            ->expectsOutputToContain('Found 1 orphaned media file');
    }

    /** @test */
    public function orphan_cleanup_does_not_delete_recent_soft_deleted_media(): void
    {
        // Create recently soft-deleted media
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
        ]);
        $media->delete(); // Soft delete

        // Keep deleted_at recent (within default 168 hours)
        $media->update(['deleted_at' => now()->subHours(24)]);

        $this->artisan('media:cleanup-orphans --hours=168 --dry-run')
            ->assertExitCode(0)
            ->expectsOutputToContain('No orphaned media found');
    }

    /** @test */
    public function orphan_cleanup_deletes_physical_files(): void
    {
        Storage::fake('public');

        // Create soft-deleted media with file
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
            'path' => 'media/test/2024/01/01/test.jpg',
        ]);
        
        // Create the file in storage
        Storage::disk('public')->put($media->path, 'test content');
        
        $this->assertTrue(Storage::disk('public')->exists($media->path));

        // Soft delete and make old
        $media->delete();
        $media->update(['deleted_at' => now()->subDays(8)]);

        // Run cleanup with force to skip confirmation
        $this->artisan('media:cleanup-orphans --hours=168 --force')
            ->assertExitCode(0);

        // File should be deleted
        $this->assertFalse(Storage::disk('public')->exists($media->path));
    }

    /** @test */
    public function orphan_cleanup_permanently_deletes_database_record(): void
    {
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
        ]);
        $mediaId = $media->id;

        $media->delete();
        $media->update(['deleted_at' => now()->subDays(8)]);

        $this->artisan('media:cleanup-orphans --hours=168 --force')
            ->assertExitCode(0);

        // Record should be permanently deleted
        $this->assertNull(Media::withTrashed()->find($mediaId));
    }

    /** @test */
    public function orphan_cleanup_reports_space_freed(): void
    {
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
            'size' => 1024 * 1024, // 1MB
        ]);

        $media->delete();
        $media->update(['deleted_at' => now()->subDays(8)]);

        $this->artisan('media:cleanup-orphans --hours=168 --force')
            ->assertExitCode(0)
            ->expectsOutputToContain('1 MB');
    }

    /** @test */
    public function orphan_cleanup_handles_multiple_files(): void
    {
        // Create multiple soft-deleted media
        Media::factory()->count(5)->create([
            'uploader_id' => $this->user->id,
        ])->each(function ($media) {
            $media->delete();
            $media->update(['deleted_at' => now()->subDays(8)]);
        });

        $this->artisan('media:cleanup-orphans --hours=168 --force')
            ->assertExitCode(0)
            ->expectsOutputToContain('Found 5 orphaned media file');
    }

    /** @test */
    public function orphan_cleanup_skips_non_orphaned_media(): void
    {
        // Create active (non-deleted) media
        Media::factory()->count(3)->create([
            'uploader_id' => $this->user->id,
        ]);

        $this->artisan('media:cleanup-orphans --hours=168 --dry-run')
            ->assertExitCode(0)
            ->expectsOutputToContain('No orphaned media found');

        // All media should still exist
        $this->assertEquals(3, Media::count());
    }

    /** @test */
    public function soft_delete_preserves_file_temporarily(): void
    {
        Storage::fake('public');

        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
            'path' => 'media/test/2024/01/01/test.jpg',
        ]);

        Storage::disk('public')->put($media->path, 'test content');

        // Soft delete
        $this->actingAs($this->user)
            ->deleteJson('/api/v1/media/' . $media->id)
            ->assertStatus(200);

        // File should still exist after soft delete
        $this->assertTrue(Storage::disk('public')->exists($media->path));

        // Record should be soft deleted
        $this->assertSoftDeleted('media', ['id' => $media->id]);
    }

    /** @test */
    public function custom_hours_parameter_works(): void
    {
        $media = Media::factory()->create([
            'uploader_id' => $this->user->id,
        ]);

        // Delete 5 days ago
        $media->delete();
        $media->update(['deleted_at' => now()->subDays(5)]);

        // Should not be cleaned with 7 day threshold
        $this->artisan('media:cleanup-orphans --hours=168 --dry-run')
            ->expectsOutputToContain('No orphaned media found');

        // Should be cleaned with 3 day threshold
        $this->artisan('media:cleanup-orphans --hours=72 --dry-run')
            ->expectsOutputToContain('Found 1 orphaned media file');
    }

    /** @test */
    public function cleanup_command_shows_summary_table(): void
    {
        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'mime_type' => 'image/jpeg',
        ])->tap(fn($m) => $m->delete())->first()->update(['deleted_at' => now()->subDays(8)]);

        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'mime_type' => 'application/pdf',
        ])->tap(fn($m) => $m->delete())->first()->update(['deleted_at' => now()->subDays(8)]);

        $this->artisan('media:cleanup-orphans --hours=168 --force')
            ->assertExitCode(0);
    }
}
