<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedImage;
use App\Models\MediaFile;
use App\Services\Media\FeaturedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeaturedImageController extends Controller
{
    public function __construct(
        protected FeaturedImageService $featuredImageService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'media_id' => ['required', 'exists:media_files,id'],
            'model_type' => ['required', 'string'],
            'model_id' => ['required', 'integer'],
        ]);

        $media = MediaFile::findOrFail($request->input('media_id'));

        $featuredImage = $this->featuredImageService->generateVariants(
            $media,
            $request->input('model_type'),
            $request->input('model_id')
        );

        return response()->json([
            'success' => true,
            'featured_image' => [
                'id' => $featuredImage->id,
                'uuid' => $featuredImage->uuid,
                'thumbnail_url' => $featuredImage->thumbnail_path ? asset('storage/' . $featuredImage->thumbnail_path) : null,
                'medium_url' => $featuredImage->medium_path ? asset('storage/' . $featuredImage->medium_path) : null,
                'large_url' => $featuredImage->large_path ? asset('storage/' . $featuredImage->large_path) : null,
                'webp_url' => $featuredImage->webp_path ? asset('storage/' . $featuredImage->webp_path) : null,
                'dominant_color' => $featuredImage->dominant_color,
                'alt_text' => $featuredImage->alt_text,
                'caption' => $featuredImage->caption,
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $featuredImage = FeaturedImage::findOrFail($id);

        $this->featuredImageService->delete($featuredImage);

        return response()->json([
            'success' => true,
            'message' => 'Featured image deleted successfully.',
        ]);
    }
}
