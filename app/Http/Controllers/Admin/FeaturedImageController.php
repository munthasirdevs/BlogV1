<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedImage;
use App\Models\MediaFile;
use App\Services\AI\AIService;
use App\Services\Media\FeaturedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeaturedImageController extends Controller
{
    public function __construct(
        protected FeaturedImageService $featuredImageService,
        protected AIService $aiService
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

    public function aiEnrich(int $id): JsonResponse
    {
        $featuredImage = FeaturedImage::with('media')->findOrFail($id);

        try {
            $altText = $this->aiService->generateContent(
                "Generate concise SEO alt text (max 120 chars) for this image: {$featuredImage->media?->original_name}. Focus on describing the image content naturally with relevant keywords.",
                'meta_description'
            );

            $caption = $this->aiService->generateContent(
                "Write a brief, engaging caption (1-2 sentences) for a blog image titled '{$featuredImage->title}'. Make it descriptive and contextually relevant.",
                'article'
            );

            $featuredImage->update([
                'alt_text' => $altText ?: $featuredImage->alt_text,
                'caption' => $caption ?: $featuredImage->caption,
                'seo_score' => 85,
            ]);

            return response()->json([
                'success' => true,
                'alt_text' => $featuredImage->alt_text,
                'caption' => $featuredImage->caption,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function ogImage(int $id): JsonResponse
    {
        $featuredImage = FeaturedImage::findOrFail($id);

        if (!$featuredImage->large_path) {
            return response()->json(['error' => 'No large variant available'], 404);
        }

        $ogUrl = asset('storage/' . $featuredImage->large_path);

        return response()->json([
            'success' => true,
            'og:image' => $ogUrl,
            'og:image:width' => 1200,
            'og:image:height' => 628,
            'og:image:alt' => $featuredImage->alt_text ?? $featuredImage->title ?? 'Featured image',
        ]);
    }
}
