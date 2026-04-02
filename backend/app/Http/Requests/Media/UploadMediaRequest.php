<?php

namespace App\Http\Requests\Media;

use App\Rules\ImageDimensions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Class UploadMediaRequest
 *
 * Validates requests for uploading media files.
 *
 * @OA\Schema(
 *     schema="UploadMediaRequest",
 *     required={"file"},
 *     @OA\Property(property="file", type="string", format="binary", description="The file to upload"),
 *     @OA\Property(property="collection_name", type="string", example="posts", enum={"posts", "avatars", "featured", "general"}),
 *     @OA\Property(property="alt_text", type="string", maxLength=255, example="Description of the image"),
 *     @OA\Property(property="title", type="string", maxLength=255, example="Image Title"),
 *     @OA\Property(property="model_type", type="string", example="App\\Models\\Post"),
 *     @OA\Property(property="model_id", type="integer", example=1)
 * )
 */
class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get max upload size from config (in KB)
        $maxSize = config('blog.max_upload_size_kb', 5120); // Default 5MB

        // Define file rules based on type
        $fileRules = [
            'required',
            File::types(['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx'])
                ->max($maxSize)
        ];

        // Add image dimension rules for images
        if ($this->file?->getClientMimeType() && str_starts_with($this->file->getClientMimeType(), 'image/')) {
            $fileRules[] = new ImageDimensions(
                minWidth: 100,
                minHeight: 100,
                maxWidth: 4096,
                maxHeight: 4096,
                maxSize: $maxSize
            );
        }

        return [
            'file' => $fileRules,
            'collection_name' => ['nullable', 'string', 'in:posts,avatars,featured,general,thumbnails'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'model_type' => ['nullable', 'string', 'max:255'],
            'model_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxSizeMB = config('blog.max_upload_size_kb', 5120) / 1024;

        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'The file must be an image or document (png, jpg, jpeg, gif, webp, pdf, doc, docx).',
            'file.max' => "The file size must not exceed {$maxSizeMB} MB.",
            'collection_name.in' => 'Invalid collection name.',
            'alt_text.max' => 'Alt text cannot exceed 255 characters.',
            'title.max' => 'Title cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'file' => 'file',
            'collection_name' => 'collection',
            'alt_text' => 'alt text',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check user's storage quota if applicable
            if ($this->user()) {
                $usedStorage = \App\Models\Media::where('uploader_id', $this->user()->id)->sum('file_size');
                $maxStorage = config('blog.max_user_storage_mb', 100) * 1024 * 1024; // Convert to bytes

                if ($usedStorage + $this->file->getSize() > $maxStorage) {
                    $validator->errors()->add('file', 'You have exceeded your storage quota.');
                }
            }
        });
    }
}
