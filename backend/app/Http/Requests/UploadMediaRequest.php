<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class UploadMediaRequest
 *
 * Form request for validating media upload requests.
 * 
 * Validation Rules:
 * - File type validation (images: jpg, png, gif, webp, svg)
 * - File size validation (images: 5MB max, documents: 10MB max)
 * - Image dimensions validation (max 4000x4000)
 * - MIME type validation
 */
class UploadMediaRequest extends FormRequest
{
    /**
     * Maximum file size for images in bytes (5MB).
     */
    const MAX_IMAGE_SIZE = 5242880; // 5 * 1024 * 1024

    /**
     * Maximum file size for documents in bytes (10MB).
     */
    const MAX_DOCUMENT_SIZE = 10485760; // 10 * 1024 * 1024

    /**
     * Maximum image dimensions.
     */
    const MAX_IMAGE_WIDTH = 4000;
    const MAX_IMAGE_HEIGHT = 4000;

    /**
     * Allowed image MIME types.
     */
    const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    /**
     * Allowed document MIME types.
     */
    const ALLOWED_DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', \App\Models\Media::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:' . (self::MAX_IMAGE_SIZE / 1024)], // in KB
            'file.*' => ['file', 'max:' . (self::MAX_IMAGE_SIZE / 1024)],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'collection_name' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'No file was uploaded. Please select a file to upload.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.max' => 'The file size must not exceed ' . (self::MAX_IMAGE_SIZE / 1024 / 1024) . ' MB.',
            'file.*.max' => 'One or more files exceed the maximum size of ' . (self::MAX_IMAGE_SIZE / 1024 / 1024) . ' MB.',
            'alt_text.max' => 'The alt text must not exceed 255 characters.',
            'title.max' => 'The title must not exceed 255 characters.',
            'caption.max' => 'The caption must not exceed 500 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'file',
            'alt_text' => 'alt text',
            'collection_name' => 'collection name',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $this->validateFileTypes($validator);
                $this->validateFileSize($validator);
                $this->validateImageDimensions($validator);
                $this->validateMimeTypeMatchesExtension($validator);
            }
        });
    }

    /**
     * Validate file types are allowed.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateFileTypes($validator): void
    {
        $files = $this->file('file') ? (is_array($this->file('file')) ? $this->file('file') : [$this->file('file')]) : [];

        foreach ($files as $index => $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            // Check if it's an allowed image type
            if (in_array($mimeType, self::ALLOWED_IMAGE_MIME_TYPES)) {
                continue;
            }

            // Check if it's an allowed document type
            if (in_array($mimeType, self::ALLOWED_DOCUMENT_MIME_TYPES)) {
                continue;
            }

            // Check for SVG specifically (sometimes detected as text/xml)
            if ($extension === 'svg' && $mimeType === 'text/xml') {
                continue;
            }

            $field = $index !== null ? "file.{$index}" : "file";
            $validator->errors()->add($field, 'The uploaded file type is not allowed. Allowed types: images (jpg, png, gif, webp, svg) and documents (pdf, doc, docx, xls, xlsx, txt, csv).');
        }
    }

    /**
     * Validate file size based on type.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateFileSize($validator): void
    {
        $files = $this->file('file') ? (is_array($this->file('file')) ? $this->file('file') : [$this->file('file')]) : [];

        foreach ($files as $index => $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Determine max size based on type
            $maxSize = str_starts_with($mimeType, 'image/') 
                ? self::MAX_IMAGE_SIZE 
                : self::MAX_DOCUMENT_SIZE;

            if ($size > $maxSize) {
                $maxSizeMB = $maxSize / 1024 / 1024;
                $field = $index !== null ? "file.{$index}" : "file";
                $fileType = str_starts_with($mimeType, 'image/') ? 'image' : 'document';
                $validator->errors()->add($field, "The {$fileType} file size must not exceed {$maxSizeMB} MB. Current size: " . round($size / 1024 / 1024, 2) . " MB.");
            }
        }
    }

    /**
     * Validate image dimensions.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateImageDimensions($validator): void
    {
        $files = $this->file('file') ? (is_array($this->file('file')) ? $this->file('file') : [$this->file('file')]) : [];

        foreach ($files as $index => $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $mimeType = $file->getMimeType();

            // Only validate dimensions for raster images (not SVG)
            if (!str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
                continue;
            }

            // Get image dimensions
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                continue;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width > self::MAX_IMAGE_WIDTH || $height > self::MAX_IMAGE_HEIGHT) {
                $field = $index !== null ? "file.{$index}" : "file";
                $validator->errors()->add($field, "The image dimensions must not exceed " . self::MAX_IMAGE_WIDTH . "x" . self::MAX_IMAGE_HEIGHT . " pixels. Current size: {$width}x{$height}.");
            }
        }
    }

    /**
     * Validate MIME type matches file extension.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateMimeTypeMatchesExtension($validator): void
    {
        $files = $this->file('file') ? (is_array($this->file('file')) ? $this->file('file') : [$this->file('file')]) : [];

        foreach ($files as $index => $file) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            // Map extensions to expected MIME types
            $extensionToMimeMap = [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'gif' => ['image/gif'],
                'webp' => ['image/webp'],
                'svg' => ['image/svg+xml', 'text/xml'],
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'xls' => ['application/vnd.ms-excel'],
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                'txt' => ['text/plain'],
                'csv' => ['text/plain', 'text/csv'],
            ];

            $expectedMimes = $extensionToMimeMap[$extension] ?? [];

            if (!empty($expectedMimes) && !in_array($mimeType, $expectedMimes)) {
                $field = $index !== null ? "file.{$index}" : "file";
                $validator->errors()->add($field, "The file extension ({$extension}) does not match the file type ({$mimeType}). Please ensure the file extension is correct.");
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Get the validated file.
     *
     * @return \Illuminate\Http\UploadedFile|null
     */
    public function getFile(): ?\Illuminate\Http\UploadedFile
    {
        return $this->file('file');
    }

    /**
     * Get all validated files.
     *
     * @return array
     */
    public function getFiles(): array
    {
        $file = $this->file('file');
        return is_array($file) ? $file : ($file ? [$file] : []);
    }

    /**
     * Get validated metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return [
            'alt_text' => $this->input('alt_text'),
            'title' => $this->input('title'),
            'caption' => $this->input('caption'),
            'description' => $this->input('description'),
            'collection_name' => $this->input('collection_name', 'default'),
            'is_public' => $this->input('is_public', true),
        ];
    }
}
