/**
 * Categories & Tags Module
 */

class TaxonomyService {
    /**
     * Get all categories
     */
    async getCategories(params = {}) {
        try {
            const response = await api.get('/categories', params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get category posts
     */
    async getCategoryPosts(slug, params = {}) {
        try {
            const response = await api.get(`/categories/${slug}/posts`, params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get all tags
     */
    async getTags(params = {}) {
        try {
            const response = await api.get('/tags', params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get tag posts
     */
    async getTagPosts(slug, params = {}) {
        try {
            const response = await api.get(`/tags/${slug}/posts`, params);
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }
}

// Create singleton instance
const taxonomyService = new TaxonomyService();
window.taxonomyService = taxonomyService;
