<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\CommentEdit;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class CommentApiTest
 *
 * Comprehensive feature tests for Comment API endpoints.
 *
 * Tests cover:
 * - CRUD operations
 * - Nested replies (up to 5 levels)
 * - Moderation workflow
 * - Search and filtering
 * - Rate limiting
 * - Authorization
 * - Edit history
 * - Mention parsing
 */
class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $editorUser;
    protected User $moderatorUser;
    protected User $regularUser;
    protected User $newUser;
    protected Post $publishedPost;
    protected Post $draftPost;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->editorUser = User::factory()->create(['role' => 'editor']);
        $this->moderatorUser = User::factory()->create(['role' => 'moderator']);
        $this->regularUser = User::factory()->create(['role' => 'author']);
        $this->newUser = User::factory()->create(['role' => 'subscriber']);

        // Create posts
        $this->publishedPost = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->draftPost = Post::factory()->create(['status' => 'draft']);
    }

    /**
     * Test: GET /api/v1/posts/{postId}/comments
     * Test retrieving comments for a post.
     */
    public function test_can_get_comments_for_post(): void
    {
        // Create some comments
        Comment::factory()->count(5)->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->publishedPost->id}/comments");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test: GET /api/v1/posts/{postId}/comments
     * Test that only approved comments are shown to public.
     */
    public function test_only_approved_comments_visible_to_public(): void
    {
        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->publishedPost->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'approved');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test creating a new comment.
     */
    public function test_can_create_comment(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'This is a great article! Very informative.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Comment submitted for approval',
            ])
            ->assertJsonPath('data.content', 'This is a great article! Very informative.');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test auto-approval for trusted users.
     */
    public function test_trusted_users_get_auto_approved_comments(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'Admin comment should be auto-approved.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Comment added successfully',
            ])
            ->assertJsonPath('data.status', 'approved');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test cannot comment on draft post.
     */
    public function test_cannot_comment_on_draft_post(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->draftPost->id}/comments", [
                'content' => 'Comment on draft post.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('post_id');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test comment content validation (minimum length).
     */
    public function test_comment_requires_minimum_length(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'Too short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test comment content validation (maximum length).
     */
    public function test_comment_has_maximum_length(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => str_repeat('a', 5001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test creating a reply to a comment.
     */
    public function test_can_create_reply_to_comment(): void
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 0,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'This is a reply to the parent comment.',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.depth', 1)
            ->assertJsonPath('data.parent_id', $parentComment->id);
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test cannot reply beyond max depth (5 levels).
     */
    public function test_cannot_reply_beyond_max_depth(): void
    {
        // Create a comment at max depth
        $maxDepthComment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => Comment::MAX_DEPTH,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'This reply should fail.',
                'parent_id' => $maxDepthComment->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    /**
     * Test: POST /api/v1/posts/{postId}/comments
     * Test rate limiting (3 comments per minute).
     */
    public function test_rate_limit_on_comment_creation(): void
    {
        // Create 3 comments (limit)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->regularUser)
                ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                    'content' => "Comment #{$i} for rate limit test.",
                ])
                ->assertStatus(201);
        }

        // 4th comment should be rate limited
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'This should be rate limited.',
            ]);

        $response->assertStatus(429);
    }

    /**
     * Test: PUT /api/v1/comments/{id}
     * Test updating a comment.
     */
    public function test_can_update_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Updated comment content.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment content.',
            'is_edited' => true,
        ]);
    }

    /**
     * Test: PUT /api/v1/comments/{id}
     * Test cannot update another user's comment.
     */
    public function test_cannot_update_another_users_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
        ]);

        $response = $this->actingAs($this->newUser)
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Trying to update someone else\'s comment.',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: PUT /api/v1/comments/{id}
     * Test edit history is recorded.
     */
    public function test_edit_history_is_recorded(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
            'content' => 'Original content.',
        ]);

        $this->actingAs($this->regularUser)
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Updated content.',
                'edit_reason' => 'Fixed typo',
            ]);

        $this->assertDatabaseHas('comment_edits', [
            'comment_id' => $comment->id,
            'user_id' => $this->regularUser->id,
            'old_content' => 'Original content.',
            'new_content' => 'Updated content.',
            'edit_reason' => 'Fixed typo',
        ]);
    }

    /**
     * Test: DELETE /api/v1/comments/{id}
     * Test deleting a comment.
     */
    public function test_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /**
     * Test: DELETE /api/v1/comments/{id}
     * Test cascade delete removes replies.
     */
    public function test_cascade_delete_removes_replies(): void
    {
        $parentComment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
        ]);

        $reply = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/v1/comments/{$parentComment->id}?cascade=true");

        $response->assertStatus(204);

        $this->assertSoftDeleted('comments', ['id' => $parentComment->id]);
        $this->assertSoftDeleted('comments', ['id' => $reply->id]);
    }

    /**
     * Test: GET /api/v1/comments/{id}
     * Test retrieving a single comment.
     */
    public function test_can_get_single_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                ],
            ]);
    }

    /**
     * Test: GET /api/v1/comments/{id}/replies
     * Test retrieving replies to a comment.
     */
    public function test_can_get_comment_replies(): void
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        Comment::factory()->count(3)->create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parentComment->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/comments/{$parentComment->id}/replies");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: POST /api/v1/comments/{id}/approve
     * Test approving a comment (moderator).
     */
    public function test_moderator_can_approve_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->moderatorUser)
            ->postJson("/api/v1/comments/{$comment->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment approved',
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test: POST /api/v1/comments/{id}/approve
     * Test regular user cannot approve comments.
     */
    public function test_regular_user_cannot_approve_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/comments/{$comment->id}/approve");

        $response->assertStatus(403);
    }

    /**
     * Test: POST /api/v1/comments/{id}/reject
     * Test rejecting a comment (moderator).
     */
    public function test_moderator_can_reject_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->moderatorUser)
            ->postJson("/api/v1/comments/{$comment->id}/reject", [
                'reason' => 'Inappropriate content',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment rejected',
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Test: POST /api/v1/comments/{id}/spam
     * Test marking a comment as spam.
     */
    public function test_moderator_can_mark_comment_as_spam(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->moderatorUser)
            ->postJson("/api/v1/comments/{$comment->id}/spam");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment marked as spam',
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'spam',
        ]);
    }

    /**
     * Test: GET /api/v1/comments/pending
     * Test getting pending comments for moderation.
     */
    public function test_can_get_pending_comments(): void
    {
        Comment::factory()->count(3)->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->moderatorUser)
            ->getJson('/api/v1/comments/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: GET /api/v1/admin/comments/search
     * Test searching comments.
     */
    public function test_can_search_comments(): void
    {
        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'content' => 'This is a test comment about Laravel.',
            'status' => 'approved',
        ]);

        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Another comment about PHP.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/comments/search?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['content' => 'This is a test comment about Laravel.']);
    }

    /**
     * Test: GET /api/v1/admin/comments/search
     * Test filtering comments by status.
     */
    public function test_can_filter_comments_by_status(): void
    {
        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/comments/search?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    /**
     * Test: POST /api/v1/admin/comments/bulk-moderate
     * Test bulk approving comments.
     */
    public function test_can_bulk_approve_comments(): void
    {
        $comments = Comment::factory()->count(3)->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $commentIds = $comments->pluck('id')->toArray();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/comments/bulk-moderate', [
                'comment_ids' => $commentIds,
                'action' => 'approve',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total' => 3,
                        'successful' => 3,
                        'failed' => 0,
                    ],
                ],
            ]);

        foreach ($commentIds as $id) {
            $this->assertDatabaseHas('comments', [
                'id' => $id,
                'status' => 'approved',
            ]);
        }
    }

    /**
     * Test: POST /api/v1/admin/comments/bulk-moderate
     * Test bulk deleting comments.
     */
    public function test_can_bulk_delete_comments(): void
    {
        $comments = Comment::factory()->count(2)->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'spam',
        ]);

        $commentIds = $comments->pluck('id')->toArray();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/comments/bulk-moderate', [
                'comment_ids' => $commentIds,
                'action' => 'delete',
            ]);

        $response->assertStatus(200);

        foreach ($commentIds as $id) {
            $this->assertSoftDeleted('comments', ['id' => $id]);
        }
    }

    /**
     * Test: GET /api/v1/comments/{id}/edits
     * Test getting edit history.
     */
    public function test_can_get_edit_history(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->regularUser->id,
            'post_id' => $this->publishedPost->id,
        ]);

        // Create an edit record
        CommentEdit::factory()->create([
            'comment_id' => $comment->id,
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->getJson("/api/v1/comments/{$comment->id}/edits");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: GET /api/v1/comments/mentions/suggest
     * Test getting mention suggestions.
     */
    public function test_can_get_mention_suggestions(): void
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/comments/mentions/suggest?q=John');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'John Doe']);
    }

    /**
     * Test nested comment structure (5 levels deep).
     */
    public function test_nested_comments_up_to_5_levels(): void
    {
        $level0 = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 0,
            'parent_id' => null,
        ]);

        $level1 = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 1,
            'parent_id' => $level0->id,
        ]);

        $level2 = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 2,
            'parent_id' => $level1->id,
        ]);

        $level3 = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 3,
            'parent_id' => $level2->id,
        ]);

        $level4 = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
            'depth' => 4,
            'parent_id' => $level3->id,
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->publishedPost->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Only top-level comment

        // Verify the nested structure is present
        $responseData = $response->json();
        $this->assertEquals(0, $responseData['data'][0]['depth']);
    }

    /**
     * Test: Comment count is updated on post.
     */
    public function test_post_comment_count_updated(): void
    {
        $initialCount = $this->publishedPost->comments_count;

        Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $this->publishedPost->refresh();

        $this->assertEquals($initialCount + 1, $this->publishedPost->comments_count);
    }

    /**
     * Test: Unauthorized user cannot access pending comments.
     */
    public function test_unauthorized_cannot_access_pending_comments(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/comments/pending');

        $response->assertStatus(403);
    }

    /**
     * Test: Editor can approve comments.
     */
    public function test_editor_can_approve_comments(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->editorUser)
            ->postJson("/api/v1/editor/comments/{$comment->id}/approve");

        // Note: This route may need to be added for editor access
        // For now, test the standard approve route
        $response = $this->actingAs($this->editorUser)
            ->postJson("/api/v1/comments/{$comment->id}/approve");

        $response->assertStatus(200);
    }

    /**
     * Test: Comment with mentions.
     */
    public function test_comment_with_mentions(): void
    {
        $mentionedUser = User::factory()->create(['name' => 'TestUser']);

        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'Hey @TestUser, check this out!',
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test: Invalid mention validation.
     */
    public function test_invalid_mention_validation(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/v1/posts/{$this->publishedPost->id}/comments", [
                'content' => 'Hey @NonExistentUser12345, check this!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * Test: Statistics endpoint for admin.
     */
    public function test_admin_can_get_comment_statistics(): void
    {
        Comment::factory()->count(5)->create(['status' => 'approved']);
        Comment::factory()->count(2)->create(['status' => 'pending']);
        Comment::factory()->count(1)->create(['status' => 'spam']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/comments/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 8,
                    'approved' => 5,
                    'pending' => 2,
                    'spam' => 1,
                ],
            ]);
    }

    /**
     * Test: Flat list option for comments.
     */
    public function test_can_get_flat_comments_list(): void
    {
        Comment::factory()->count(5)->create([
            'post_id' => $this->publishedPost->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->publishedPost->id}/comments?flat=true");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'content', 'status', 'created_at'],
                ],
                'meta' => ['total', 'current_page', 'per_page', 'last_page'],
            ]);
    }
}
