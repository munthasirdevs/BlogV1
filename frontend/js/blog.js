/**
 * Blog Module
 * Handles blog post operations
 */

class BlogService {
    /**
     * Get paginated posts
     */
    async getPosts(params = {}) {
        try {
            const response = await api.get('/posts', params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get single post by slug
     */
    async getPost(slug) {
        try {
            const response = await api.get(`/posts/${slug}`);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Create new post
     */
    async createPost(data) {
        try {
            const response = await api.post('/posts', data);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update post
     */
    async updatePost(postId, data) {
        try {
            const response = await api.put(`/posts/${postId}`, data);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Delete post
     */
    async deletePost(postId) {
        try {
            await api.delete(`/posts/${postId}`);
            return { success: true };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get user's posts
     */
    async getUserPosts(params = {}) {
        try {
            const response = await api.get('/user/posts', params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Like/unlike post
     */
    async toggleLike(postId) {
        try {
            const response = await api.post(`/posts/${postId}/like`);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Bookmark/unbookmark post
     */
    async toggleBookmark(postId) {
        try {
            const response = await api.post(`/posts/${postId}/bookmark`);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get user bookmarks
     */
    async getBookmarks(params = {}) {
        try {
            const response = await api.get('/user/bookmarks', params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Remove bookmark
     */
    async removeBookmark(postId) {
        try {
            await api.delete(`/user/bookmarks/${postId}`);
            return { success: true };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }
}

// Create singleton instance
const blogService = new BlogService();
window.blogService = blogService;
