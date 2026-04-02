<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AdminCommentRequest
 *
 * Validates requests for admin comment moderation operations.
 *
 * @OA\Schema(
 *     schema="BulkModerateRequest",
 *     required={"comment_ids", "action"},
 *     @OA\Property(property="comment_ids", type="array", @OA\Items(type="integer"), example=[1, 2, 3]),
 *     @OA\Property(property="action", type="string", enum={"approve", "reject", "spam", "delete"}, example="approve"),
 *     @OA\Property(property="reason", type="string", nullable=true, example="Spam content")
 * )
 *
 * @OA\Schema(
 *     schema="SearchCommentsRequest",
 *     @OA\Property(property="search", type="string", nullable=true, example="spam"),
 *     @OA\Property(property="status", type="string", nullable=true, enum={"pending", "approved", "rejected", "spam"}, example="pending"),
 *     @OA\Property(property="post_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="user_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="from_date", type="string", format="date", nullable=true, example="2024-01-01"),
 *     @OA\Property(property="to_date", type="string", format="date", nullable=true, example="2024-12-31"),
 *     @OA\Property(property="sort", type="string", nullable=true, example="created_at"),
 *     @OA\Property(property="order", type="string", nullable=true, enum={"asc", "desc"}, example="desc"),
 *     @OA\Property(property="per_page", type="integer", nullable=true, example=20)
 * )
 */
class AdminCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'moderator']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->input('action');

        return [
            'comment_ids' => ['required', 'array', 'min:1'],
            'comment_ids.*' => ['required', 'integer', 'exists:comments,id'],
            'action' => ['required', 'string', 'in:approve,reject,spam,delete'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'comment_ids.required' => 'Please select at least one comment.',
            'comment_ids.array' => 'Comment IDs must be an array.',
            'comment_ids.min' => 'Please select at least one comment.',
            'comment_ids.*.exists' => 'One or more comment IDs are invalid.',
            'action.required' => 'Please select an action.',
            'action.in' => 'Invalid action. Must be one of: approve, reject, spam, delete.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get the comment IDs to moderate.
     *
     * @return array<int>
     */
    public function getCommentIds(): array
    {
        return $this->input('comment_ids', []);
    }

    /**
     * Get the moderation action.
     */
    public function getAction(): string
    {
        return $this->input('action');
    }

    /**
     * Get the optional reason.
     */
    public function getReason(): ?string
    {
        return $this->input('reason');
    }
}
