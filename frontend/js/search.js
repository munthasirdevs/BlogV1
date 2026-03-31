/**
 * Search Module
 */

class SearchService {
    /**
     * Search posts
     */
    async search(query, params = {}) {
        try {
            const response = await api.get('/search', { q: query, ...params });
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Get search suggestions
     */
    async suggest(query, limit = 5) {
        try {
            const response = await api.get('/search/suggest', { q: query, limit });
            return { success: true, ...response };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }
}

// Create singleton instance
const searchService = new SearchService();
window.searchService = searchService;
