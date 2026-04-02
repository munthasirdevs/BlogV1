<?php

namespace App\Http\Requests\Comment;

use App\Helpers\MentionParser;
use App\Helpers\ProfanityFilter;
use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateCommentRequest
 *
 * Validates requests for updating existing comments.
 *
 * Features:
 * - Content validation (10-5000 chars)
 * - Edit window validation (30 minutes)
 * - Max edits check (5 edits)
 * - Profanity filter integration
 * - Mention validation
 *
 * @OA\Schema(
 *     schema="UpdateCommentRequest",
 *     @OA\Property(property="content", type="string", minLength=10, maxLength=5000, example="Updated comment content with corrections."),
 *     @OA\Property(property="edit_reason", type="string", nullable=true, maxLength=255, example="Fixed typo and added clarification")
 * )
 */
class UpdateCommentRequest extends FormRequest
{
    /**
     * Maximum number of edits allowed.
     */
    const MAX_EDITS = 5;

    /**
     * Edit window in minutes.
     */
    const EDIT_WINDOW_MINUTES = 30;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        if (!$comment) {
            return false;
        }

        // Admins, editors, and moderators can update any comment
        if ($this->user()->hasRole(['admin', 'moderator', 'editor'])) {
            return true;
        }

        // Users can only update their own comments
        return $this->user()->id === $comment->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'edit_reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please enter your comment.',
            'content.min' => 'Comment must be at least 10 characters.',
            'content.max' => 'Comment cannot exceed 5000 characters.',
            'edit_reason.max' => 'Edit reason cannot exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $comment = $this->route('comment');
            $user = $this->user();

            // Check if comment is soft deleted
            if ($comment->trashed()) {
                $validator->errors()->add('content', 'Cannot update a deleted comment.');
                return;
            }

            // Check edit window for non-staff users
            if (!$user->hasRole(['admin', 'editor', 'moderator'])) {
                $this->checkEditWindow($validator, $comment);
                $this->checkMaxEdits($validator, $comment);
            }

            // Validate mentions
            $this->validateMentions($validator);

            // Profanity check
            $this->checkProfanity($validator, $user);
        });
    }

    /**
     * Check if within edit window.
     */
    protected function checkEditWindow($validator, Comment $comment): void
    {
        if ($comment->created_at->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
            $validator->errors()->add('content', 'Comments can only be edited within ' . self::EDIT_WINDOW_MINUTES . ' minutes of posting.');
        }
    }

    /**
     * Check if max edits reached.
     */
    protected function checkMaxEdits($validator, Comment $comment): void
    {
        if ($comment->edits()->count() >= self::MAX_EDITS) {
            $validator->errors()->add('content', 'Maximum edit limit (' . self::MAX_EDITS . ') reached for this comment.');
        }
    }

    /**
     * Validate mentions in content.
     */
    protected function validateMentions($validator): void
    {
        $content = $this->input('content');

        // Check max mentions
        if (MentionParser::exceedsMaxMentions($content)) {
            $validator->errors()->add('content', 'Maximum ' . MentionParser::MAX_MENTIONS . ' mentions allowed per comment.');
            return;
        }

        // Check for invalid mentions
        $result = MentionParser::parseWithValidation($content);
        if (!empty($result['invalid'])) {
            $invalidList = implode(', ', array_map(fn($u) => "@{$u}", $result['invalid']));
            $validator->errors()->add('content', "Invalid mentions: {$invalidList}");
        }
    }

    /**
     * Check for profanity in content.
     */
    protected function checkProfanity($validator, $user): void
    {
        $content = $this->input('content');

        // Skip for privileged users
        if (ProfanityFilter::shouldBypass($user)) {
            return;
        }

        if (ProfanityFilter::containsProfanity($content)) {
            $validator->errors()->add('content', 'Your comment contains inappropriate language. Please revise.');
        }
    }

    /**
     * Get the validated content with filtered profanity (if allowed).
     */
    public function getFilteredContent(): string
    {
        $content = $this->input('content');
        $user = $this->user();

        $result = ProfanityFilter::validate($content, $user, true);

        return $result['filtered_content'];
    }

    /**
     * Get the comment from the route.
     */
    public function getComment(): ?Comment
    {
        return $this->route('comment');
    }

    /**
     * Check if edit reason was provided.
     */
    public function hasEditReason(): bool
    {
        return !empty($this->input('edit_reason'));
    }

    /**
     * Get the edit reason.
     */
    public function getEditReason(): ?string
    {
        return $this->input('edit_reason');
    }
}
