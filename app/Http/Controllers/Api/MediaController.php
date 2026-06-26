<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,png,webp,gif,mp4,pdf', 'max:102400'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->input('folder_id'),
            auth()->id()
        );

        return response()->json(['success' => true, 'data' => $media], 201);
    }
}
