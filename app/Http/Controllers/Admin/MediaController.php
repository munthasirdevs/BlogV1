<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(): View
    {
        $files = MediaFile::with('uploader', 'folder')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        $folders = MediaFolder::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.media.index', compact('files', 'folders'));
    }

    public function create(): View
    {
        $folders = MediaFolder::orderBy('name')->get();

        return view('admin.media.upload', compact('folders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,png,webp,gif,svg,mp4,pdf,docx', 'max:204800'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $this->mediaService->upload(
            $request->file('file'),
            $request->input('folder_id'),
            auth()->id()
        );

        return redirect()->route('admin.media.index')
            ->with('success', 'File uploaded successfully.');
    }

    public function edit(MediaFile $medium): View
    {
        $folders = MediaFolder::orderBy('name')->get();

        return view('admin.media.edit', compact('medium', 'folders'));
    }

    public function update(Request $request, MediaFile $medium): RedirectResponse
    {
        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $medium->update($validated);

        return redirect()->route('admin.media.edit', $medium)
            ->with('success', 'Media updated successfully.');
    }

    public function destroy(MediaFile $medium): RedirectResponse
    {
        $this->mediaService->delete($medium);

        return redirect()->route('admin.media.index')
            ->with('success', 'File deleted successfully.');
    }

    public function uploadZip(Request $request): RedirectResponse
    {
        $request->validate([
            'zip_file' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $results = $this->mediaService->uploadZip(
            $request->file('zip_file'),
            $request->input('folder_id'),
            auth()->id()
        );

        $message = $results['uploaded'] . ' files uploaded.';
        if ($results['skipped'] > 0) $message .= ' ' . $results['skipped'] . ' duplicates skipped.';
        if (!empty($results['errors'])) $message .= ' ' . count($results['errors']) . ' errors.';

        return redirect()->route('admin.media.index')->with('success', $message);
    }

    public function aiGenerateMetadata(MediaFile $medium): JsonResponse
    {
        $result = $this->mediaService->generateAiMetadata($medium);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.media.index')
                ->with('error', 'No files selected for deletion.');
        }

        $ids = json_decode($ids, true);

        if (!is_array($ids) || empty($ids)) {
            return redirect()->route('admin.media.index')
                ->with('error', 'Invalid file selection.');
        }

        $files = MediaFile::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($files as $file) {
            try {
                $this->mediaService->delete($file);
                $count++;
            } catch (\Exception $e) {
                Log::error('Bulk delete failed for media file', [
                    'id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('admin.media.index')
            ->with('success', $count . ' file(s) deleted successfully.');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,png,webp,gif,svg,mp4,pdf,docx', 'max:204800'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->input('folder_id'),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $media->id,
                'url' => $media->file_url,
                'thumbnail' => $this->mediaService->getUrl($media, 'thumb'),
                'name' => $media->original_name,
                'size' => $media->file_size,
            ],
        ]);
    }
}
