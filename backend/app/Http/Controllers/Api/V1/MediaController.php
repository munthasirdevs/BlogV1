<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Class MediaController
 *
 * Controller for managing media files.
 * 
 * Endpoints:
 * - POST /api/v1/media/upload - Upload single file
 * - POST /api/v1/media/upload-multiple - Upload multiple files
 * - GET /api/v1/media - List media library
 * - GET /api/v1/media/{id} - Show media details
 * - GET /api/v1/media/{id}/url - Get media URL
 * - GET /api/v1/media/{id}/usage - Get media usage
 * - GET /api/v1/media/search - Search media
 * - PUT /api/v1/media/{id} - Update metadata
 * - DELETE /api/v1/media/{id} - Soft delete
 * - POST /api/v1/media/{id}/restore - Restore deleted
 * - GET /api/v1/media/statistics - Get statistics
 */
class MediaController extends Controller
{
    /**
     * Media service instance.
     */
    protected MediaService $mediaService;

    /**
     * Create a new MediaController instance.
     *
     * @param MediaService $mediaService
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of media files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'collection_name', 'uploader_id', 'search', 'from_date', 'to_date', 'is_public']);
        $perPage = $request->get('per_page', 15);

        $media = $this->mediaService->getMediaLibrary($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => MediaResource::collection($media),
            'meta' => [
                'current_page' => $media->currentPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'last_page' => $media->lastPage(),
                'from' => $media->firstItem(),
                'to' => $media->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created media file.
     *
     * @param UploadMediaRequest $request
     * @return JsonResponse
     */
    public function store(UploadMediaRequest $request): JsonResponse
    {
        try {
            $file = $request->getFile();
            $metadata = $request->getMetadata();
            $metadata['uploader_id'] = $request->user()->id;

            $media = $this->mediaService->uploadFile($file, $metadata);

            return response()->json([
                'success' => true,
                'data' => new MediaResource($media),
                'message' => 'File uploaded successfully',
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified media file.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        // Check authorization
        Gate::authorize('view', $media);

        return response()->json([
            'success' => true,
            'data' => new MediaResource($media),
        ]);
    }

    /**
     * Update the specified media file's metadata.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        // Check authorization
        Gate::authorize('update', $media);

        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'collection_name' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $media = $this->mediaService->updateMetadata($id, $validated);

        return response()->json([
            'success' => true,
            'data' => new MediaResource($media),
            'message' => 'Media metadata updated successfully',
        ]);
    }

    /**
     * Remove the specified media file.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        // Check authorization
        Gate::authorize('delete', $media);

        try {
            $this->mediaService->deleteMedia($id);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload a media file.
     *
     * @param UploadMediaRequest $request
     * @return JsonResponse
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Upload multiple media files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['required', 'file', 'max:10240'], // 10MB max
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'collection_name' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        // Check authorization
        Gate::authorize('create', Media::class);

        $files = $request->file('files', []);
        
        // Limit to 10 files
        if (count($files) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 10 files allowed per request',
            ], 422);
        }

        $metadata = [
            'alt_text' => $validated['alt_text'] ?? null,
            'title' => $validated['title'] ?? null,
            'collection_name' => $validated['collection_name'] ?? 'default',
            'is_public' => $validated['is_public'] ?? true,
            'uploader_id' => $request->user()->id,
        ];

        $results = $this->mediaService->uploadMultiple($files, $metadata);

        return response()->json([
            'success' => true,
            'data' => [
                'successful' => MediaResource::collection($results['successful']),
                'failed' => $results['failed'],
            ],
            'meta' => [
                'successful_count' => $results['successful']->count(),
                'failed_count' => count($results['failed']),
                'total_count' => count($files),
            ],
            'message' => "Uploaded {$results['successful']->count()} of " . count($files) . ' files successfully',
        ]);
    }

    /**
     * Get media URL.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function url(int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        Gate::authorize('view', $media);

        $url = $this->mediaService->getMediaUrl($id);
        $thumbnails = [];

        if (str_starts_with($media->mime_type, 'image/')) {
            $thumbnails = $this->mediaService->getAllThumbnailUrls($media);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $id,
                'url' => $url,
                'thumbnails' => $thumbnails,
            ],
        ]);
    }

    /**
     * Get media usage information.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function usage(int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        Gate::authorize('view', $media);

        $usage = $this->mediaService->getMediaUsage($id);

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    /**
     * Search media files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $perPage = $request->get('per_page', 15);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        $media = $this->mediaService->searchMedia($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => MediaResource::collection($media),
            'meta' => [
                'current_page' => $media->currentPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'query' => $query,
            ],
        ]);
    }

    /**
     * Restore a soft-deleted media.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $media = Media::withTrashed()->find($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        // Check authorization (same as delete)
        Gate::authorize('delete', $media);

        $restored = $this->mediaService->restoreMedia($id);

        if (!$restored) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore media',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new MediaResource($media->fresh()),
            'message' => 'Media restored successfully',
        ]);
    }

    /**
     * Get media statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        Gate::authorize('viewAny', Media::class);

        $statistics = $this->mediaService->getStatistics();
        $storageUsage = $this->mediaService->getTotalStorageUsage();

        return response()->json([
            'success' => true,
            'data' => array_merge($statistics, [
                'storage' => $storageUsage,
            ]),
        ]);
    }

    /**
     * Get user's storage usage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storageUsage(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $usage = $this->mediaService->getUserStorageUsage($userId);

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    /**
     * Regenerate thumbnails for an image.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function regenerateThumbnails(int $id): JsonResponse
    {
        $media = $this->mediaService->getMedia($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        }

        Gate::authorize('update', $media);

        $result = $this->mediaService->regenerateThumbnails($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnails' => $result['thumbnails'],
            ],
            'message' => $result['message'],
        ]);
    }
}
