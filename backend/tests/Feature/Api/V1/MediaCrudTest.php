<?php

namespace Tests\Feature\Api\V1;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class MediaCrudTest
 *
 * Feature tests for media CRUD operations.
 * 
 * Tests cover:
 * - List media library
 * - Show media details
 * - Update metadata
 * - Soft delete
 * - Restore
 * - Search functionality
 */
class MediaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected User $admin;
    protected Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Create roles if they don't exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        
        $this->user = User::factory()->create(['email' => 'user@test.com']);
        $this->user->assignRole('author');
        $this->otherUser = User::factory()->create(['email' => 'other@test.com']);
        $this->otherUser->assignRole('author');
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');
        
        $this->media = Media::factory()->create([
            'uploader_id' => $this->user->id,
            'filename' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    /** @test */
    public function can_list_media_library(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.0.id', $this->media->id);
    }

    /** @test */
    public function media_library_is_paginated(): void
    {
        $this->actingAs($this->user);

        // Create more media items
        Media::factory()->count(20)->create(['uploader_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/media?per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'per_page' => 10,
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function can_filter_media_by_type(): void
    {
        $this->actingAs($this->user);

        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'mime_type' => 'application/pdf',
            'filename' => 'document.pdf',
        ]);

        $response = $this->getJson('/api/v1/media?type=image');

        $response->assertStatus(200);
        
        $images = collect($response->json('data'))->filter(fn($m) => 
            str_starts_with($m['mime_type'], 'image/')
        );
        $this->assertCount($images->count(), $response->json('data'));
    }

    /** @test */
    public function can_filter_media_by_collection(): void
    {
        $this->actingAs($this->user);

        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'collection_name' => 'featured',
        ]);

        $response = $this->getJson('/api/v1/media?collection_name=featured');

        $response->assertStatus(200);
        
        $featured = collect($response->json('data'))->filter(fn($m) => 
            $m['collection_name'] === 'featured'
        );
        $this->assertCount($featured->count(), $response->json('data'));
    }

    /** @test */
    public function can_filter_media_by_uploader(): void
    {
        $this->actingAs($this->admin);

        Media::factory()->create([
            'uploader_id' => $this->otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/media?uploader_id=' . $this->otherUser->id);

        $response->assertStatus(200);
        
        $all = collect($response->json('data'))->every(fn($m) => 
            $m['uploader']['id'] === $this->otherUser->id
        );
        $this->assertTrue($all);
    }

    /** @test */
    public function can_filter_media_by_date_range(): void
    {
        $this->actingAs($this->user);

        $oldMedia = Media::factory()->create([
            'uploader_id' => $this->user->id,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/media?from_date=' . now()->subDays(5)->toDateString());

        $response->assertStatus(200);
        
        // Old media should not be in results
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($oldMedia->id));
    }

    /** @test */
    public function can_show_single_media(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/' . $this->media->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->media->id,
                    'filename' => $this->media->filename,
                ],
            ]);
    }

    /** @test */
    public function show_returns_404_for_nonexistent_media(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Media not found',
            ]);
    }

    /** @test */
    public function can_update_media_metadata(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/api/v1/media/' . $this->media->id, [
            'alt_text' => 'Updated alt text',
            'title' => 'Updated title',
            'caption' => 'Updated caption',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'alt_text' => 'Updated alt text',
                    'title' => 'Updated title',
                    'caption' => 'Updated caption',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'id' => $this->media->id,
            'alt_text' => 'Updated alt text',
        ]);
    }

    /** @test */
    public function cannot_update_file_through_metadata_endpoint(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/api/v1/media/' . $this->media->id, [
            'file' => UploadedFile::fake()->image('new-image.jpg', 100, 100),
        ]);

        // File field should be ignored
        $response->assertStatus(200);
        
        $this->media->refresh();
        $this->assertEquals('test-image.jpg', $this->media->filename);
    }

    /** @test */
    public function user_can_update_own_media(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/api/v1/media/' . $this->media->id, [
            'alt_text' => 'My update',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_media(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson('/api/v1/media/' . $this->media->id, [
            'alt_text' => 'Admin update',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.alt_text', 'Admin update');
    }

    /** @test */
    public function user_cannot_update_other_users_media(): void
    {
        $this->actingAs($this->otherUser);

        $response = $this->putJson('/api/v1/media/' . $this->media->id, [
            'alt_text' => 'Unauthorized update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_soft_delete_media(): void
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson('/api/v1/media/' . $this->media->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        $this->assertSoftDeleted('media', ['id' => $this->media->id]);
    }

    /** @test */
    public function soft_deleted_media_not_included_in_list(): void
    {
        $this->actingAs($this->user);

        $this->media->delete();

        $response = $this->getJson('/api/v1/media');

        $response->assertStatus(200);
        
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($this->media->id));
    }

    /** @test */
    public function can_restore_soft_deleted_media(): void
    {
        $this->actingAs($this->user);

        $this->media->delete();

        $response = $this->postJson('/api/v1/media/' . $this->media->id . '/restore');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Media restored successfully',
            ]);

        $this->assertNotSoftDeleted('media', ['id' => $this->media->id]);
    }

    /** @test */
    public function user_can_delete_own_media(): void
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson('/api/v1/media/' . $this->media->id);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_delete_any_media(): void
    {
        $this->actingAs($this->admin);

        $response = $this->deleteJson('/api/v1/media/' . $this->media->id);

        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_delete_other_users_media(): void
    {
        $this->actingAs($this->otherUser);

        $response = $this->deleteJson('/api/v1/media/' . $this->media->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_search_media_by_filename(): void
    {
        $this->actingAs($this->user);

        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'filename' => 'unique-search-test.jpg',
        ]);

        $response = $this->getJson('/api/v1/media/search?q=unique-search');

        $response->assertStatus(200);
        
        $found = collect($response->json('data'))->firstWhere('filename', 'unique-search-test.jpg');
        $this->assertNotNull($found);
    }

    /** @test */
    public function can_search_media_by_alt_text(): void
    {
        $this->actingAs($this->user);

        Media::factory()->create([
            'uploader_id' => $this->user->id,
            'alt_text' => 'Very unique alt text for searching',
        ]);

        $response = $this->getJson('/api/v1/media/search?q=unique%20alt%20text');

        $response->assertStatus(200);
        
        $found = collect($response->json('data'))->firstWhere('alt_text', 'Very unique alt text for searching');
        $this->assertNotNull($found);
    }

    /** @test */
    public function search_requires_query_parameter(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/search');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Search query is required',
            ]);
    }

    /** @test */
    public function search_returns_paginated_results(): void
    {
        $this->actingAs($this->user);

        // Create media with similar names
        for ($i = 0; $i < 20; $i++) {
            Media::factory()->create([
                'uploader_id' => $this->user->id,
                'filename' => "search-test-{$i}.jpg",
            ]);
        }

        $response = $this->getJson('/api/v1/media/search?q=search-test&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10);
    }

    /** @test */
    public function get_media_url_returns_correct_url(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/' . $this->media->id . '/url');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'url',
                    'thumbnails',
                ],
            ]);
    }

    /** @test */
    public function get_media_usage_returns_usage_info(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/' . $this->media->id . '/usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'in_use',
                    'usages',
                ],
            ]);
    }

    /** @test */
    public function get_statistics_returns_media_counts(): void
    {
        $this->actingAs($this->admin);

        Media::factory()->count(5)->create(['mime_type' => 'image/jpeg']);
        Media::factory()->count(3)->create(['mime_type' => 'application/pdf']);

        $response = $this->getJson('/api/v1/media/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_count',
                    'total_size',
                    'by_type',
                    'images_count',
                    'documents_count',
                ],
            ]);
    }

    /** @test */
    public function get_storage_usage_returns_user_storage_info(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/media/storage-usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'used',
                    'used_formatted',
                    'limit',
                    'percentage',
                ],
            ]);
    }
}
