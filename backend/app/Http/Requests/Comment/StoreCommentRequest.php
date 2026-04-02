<?php

namespace App\Http\Requests\Comment;

use App\Helpers\MentionParser;
use App\Helpers\ProfanityFilter;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreCommentRequest
 *
 * Validates requests for creating new comments.
 *
 * Features:
 * - Content validation (10-5000 chars)
 * - Spam prevention (URL limits for new users)
 * - Profanity filter integration
 * - Rate limiting hints
 * - Post existence and published status validation
 * - Parent comment validation for replies
 * - Mention validation
 *
 * @OA\Schema(
 *     schema="StoreCommentRequest",
 *     required={"content"},
 *     @OA\Property(property="content", type="string", minLength=10, maxLength=5000, example="Great article! Very helpful and informative."),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="Parent comment ID for replies"),
 *     @OA\Property(property="edit_reason", type="string", nullable=true, example="Fixed typo")
 * )
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Rate limit: 3 comments per minute.
     */
    const RATE_LIMIT_PER_MINUTE = 3;

    /**
     * Rate limit: 10 comments per hour.
     */
    const RATE_LIMIT_PER_HOUR = 10;

    /**
     * Maximum URLs allowed for new users.
     */
    const MAX_URLS_FOR_NEW_USERS = 1;

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
        return [
            'content' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:comments,id',
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
            'parent_id.exists' => 'The parent comment does not exist.',
            'edit_reason.max' => 'Edit reason cannot exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            // Validate post exists and is published
            $this->validatePost($validator);

            // Validate parent comment for replies
            $this->validateParentComment($validator);

            // Check rate limiting
            $this->checkRateLimit($validator);

            // Spam prevention for new users
            $this->checkSpamPrevention($validator, $user);

            // Validate mentions
            $this->validateMentions($validator);

            // Profanity check (optional - can filter instead of reject)
            $this->checkProfanity($validator);
        });
    }

    /**
     * Validate the post exists and is published.
     */
    protected function validatePost($validator): void
    {
        $postId = $this->route('post')?->id ?? $this->input('post_id');

        if (!$postId) {
            $validator->errors()->add('post_id', 'Please select a post to comment on.');
            return;
        }

        $post = Post::find($postId);

        if (!$post) {
            $validator->errors()->add('post_id', 'The selected post does not exist.');
            return;
        }

        if (!$post->isPublished()) {
            $validator->errors()->add('post_id', 'Cannot comment on an unpublished post.');
        }
    }

    /**
     * Validate parent comment for replies.
     */
    protected function validateParentComment($validator): void
    {
        $parentId = $this->input('parent_id');

        if (!$parentId) {
            return;
        }

        $parentComment = Comment::find($parentId);

        if (!$parentComment) {
            $validator->errors()->add('parent_id', 'The parent comment does not exist.');
            return;
        }

        // Check if parent is soft deleted
        if ($parentComment->trashed()) {
            $validator->errors()->add('parent_id', 'Cannot reply to a deleted comment.');
            return;
        }

        // Check max depth
        if ($parentComment->depth >= Comment::MAX_DEPTH) {
            $validator->errors()->add('parent_id', 'Maximum reply depth reached.');
            return;
        }

        // Verify parent belongs to same post
        $postId = $this->route('post')?->id ?? $this->input('post_id');
        if ($parentComment->post_id !== $postId) {
            $validator->errors()->add('parent_id', 'Cannot reply to a comment from a different post.');
        }

        // Prevent self-reply
        if ($parentId === $this->route('comment')?->id) {
            $validator->errors()->add('parent_id', 'A comment cannot be a reply to itself.');
        }
    }

    /**
     * Check rate limiting for comment creation.
     */
    protected function checkRateLimit($validator): void
    {
        $user = $this->user();
        $userId = $user->id;

        // Check comments in last minute
        $minuteCount = Comment::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE) {
            $validator->errors()->add('content', 'You are commenting too quickly. Please wait a moment.');
        }

        // Check comments in last hour
        $hourCount = Comment::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourCount >= self::RATE_LIMIT_PER_HOUR) {
            $validator->errors()->add('content', 'You have reached the hourly comment limit. Please try again later.');
        }
    }

    /**
     * Check for spam indicators (URLs for new users).
     */
    protected function checkSpamPrevention($validator, $user): void
    {
        $content = $this->input('content');

        // Count URLs in content
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);

        // Check if user is new (less than 5 approved comments)
        $approvedCommentCount = Comment::where('user_id', $user->id)
            ->approved()
            ->count();

        if ($approvedCommentCount < 5 && $urlCount > self::MAX_URLS_FOR_NEW_USERS) {
            $validator->errors()->add('content', 'New users cannot include multiple links. Please remove some URLs.');
        }

        // Check for spammy patterns
        if (ProfanityFilter::isSpammy($content)) {
            $validator->errors()->add('content', 'Your comment appears to be spam. Please remove excessive links or capitalization.');
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
    protected function checkProfanity($validator): void
    {
        $content = $this->input('content');
        $user = $this->user();

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
     * Get the post from the route.
     */
    public function getPost(): ?Post
    {
        return $this->route('post');
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function getParentComment(): ?Comment
    {
        $parentId = $this->input('parent_id');

        if (!$parentId) {
            return null;
        }

        return Comment::find($parentId);
    }
}
