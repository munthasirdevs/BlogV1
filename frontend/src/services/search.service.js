import api from './api';

/**
 * Search service for posts, categories, tags, and users
 */
export const searchService = {
  /**
   * Search across all content types
   * @param {string} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise<Object>} Search results
   */
  all: async (query, params = {}) => {
    const response = await api.get('/search', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  /**
   * Search posts only
   * @param {string} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise<Object>} Search results
   */
  posts: async (query, params = {}) => {
    const response = await api.get('/search/posts', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  /**
   * Search categories
   * @param {string} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise<Object>} Search results
   */
  categories: async (query, params = {}) => {
    const response = await api.get('/search/categories', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  /**
   * Search tags
   * @param {string} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise<Object>} Search results
   */
  tags: async (query, params = {}) => {
    const response = await api.get('/search/tags', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  /**
   * Search users
   * @param {string} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise<Object>} Search results
   */
  users: async (query, params = {}) => {
    const response = await api.get('/search/users', {
      params: {
        q: query,
        ...params,
      },
    });
    return response.data;
  },

  /**
   * Get search suggestions/autocomplete
   * @param {string} query - Search query
   * @returns {Promise<string[]>} Search suggestions
   */
  suggestions: async (query) => {
    const response = await api.get('/search/suggestions', {
      params: { q: query },
    });
    return response.data;
  },
};

export default searchService;
