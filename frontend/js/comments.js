/**
 * Comments Module
 * Handles comment operations
 */

class CommentService {
    /**
     * Get post comments
     */
    async getComments(postId, params = {}) {
        try {
            const response = await api.get(`/posts/${postId}/comments`, params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Create comment
     */
    async createComment(postId, content, parentId = null) {
        try {
            const response = await api.post(`/posts/${postId}/comments`, {
                content,
                parent_id: parentId,
            });
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update comment
     */
    async updateComment(commentId, content) {
        try {
            const response = await api.put(`/comments/${commentId}`, { content });
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Delete comment
     */
    async deleteComment(commentId) {
        try {
            await api.delete(`/comments/${commentId}`);
            return { success: true };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }
}

// Create singleton instance
const commentService = new CommentService();
window.commentService = commentService;
