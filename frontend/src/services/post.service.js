import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Posts service
 */
export const postService = {
  /**
   * Get all posts with pagination and filters
   * @param {Object} params - Query parameters
   * @param {number} params.page - Page number
   * @param {number} params.limit - Items per page
   * @param {string} params.search - Search query
   * @param {string} params.category - Category filter
   * @param {string} params.tag - Tag filter
   * @param {string} params.sort - Sort order
   */
  getAll: async (params = {}) => {
    const response = await api.get(API_ENDPOINTS.POSTS, { params });
    return response.data;
  },

  /**
   * Get post by ID
   * @param {number|string} id - Post ID
   */
  getById: async (id) => {
    const response = await api.get(API_ENDPOINTS.POST_BY_ID(id));
    return response.data;
  },

  /**
   * Get post by slug
   * @param {string} slug - Post slug
   */
  getBySlug: async (slug) => {
    const response = await api.get(API_ENDPOINTS.POST_BY_SLUG(slug));
    return response.data;
  },

  /**
   * Create new post
   * @param {Object} postData - Post data
   * @param {string} postData.title - Post title
   * @param {string} postData.content - Post content
   * @param {string} postData.excerpt - Post excerpt
   * @param {number} postData.category_id - Category ID
   * @param {Array} postData.tags - Tag IDs
   * @param {File} postData.featured_image - Featured image
   * @param {boolean} postData.published - Published status
   */
  create: async (postData) => {
    const response = await api.post(API_ENDPOINTS.CREATE_POST, postData);
    return response.data;
  },

  /**
   * Update post
   * @param {number|string} id - Post ID
   * @param {Object} postData - Updated post data
   */
  update: async (id, postData) => {
    const response = await api.put(API_ENDPOINTS.UPDATE_POST(id), postData);
    return response.data;
  },

  /**
   * Delete post
   * @param {number|string} id - Post ID
   */
  delete: async (id) => {
    const response = await api.delete(API_ENDPOINTS.DELETE_POST(id));
    return response.data;
  },

  /**
   * Get user's posts
   * @param {number|string} userId - User ID
   * @param {Object} params - Query parameters
   */
  getByUser: async (userId, params = {}) => {
    const response = await api.get(`/users/${userId}/posts`, { params });
    return response.data;
  },

  /**
   * Get posts by category
   * @param {number|string} categoryId - Category ID
   * @param {Object} params - Query parameters
   */
  getByCategory: async (categoryId, params = {}) => {
    const response = await api.get(`/categories/${categoryId}/posts`, { params });
    return response.data;
  },

  /**
   * Get posts by tag
   * @param {number|string} tagId - Tag ID
   * @param {Object} params - Query parameters
   */
  getByTag: async (tagId, params = {}) => {
    const response = await api.get(`/tags/${tagId}/posts`, { params });
    return response.data;
  },

  /**
   * Like a post
   * @param {number|string} id - Post ID
   */
  like: async (id) => {
    const response = await api.post(`/posts/${id}/like`);
    return response.data;
  },

  /**
   * Unlike a post
   * @param {number|string} id - Post ID
   */
  unlike: async (id) => {
    const response = await api.post(`/posts/${id}/unlike`);
    return response.data;
  },

  /**
   * Get related posts
   * @param {number|string} id - Post ID
   * @param {number} limit - Number of posts to return
   */
  getRelated: async (id, limit = 3) => {
    const response = await api.get(`/posts/${id}/related`, { params: { limit } });
    return response.data;
  },

  /**
   * Get featured posts
   * @param {Object} params - Query parameters
   * @param {number} params.limit - Number of posts to return
   */
  getFeatured: async (params = {}) => {
    const response = await api.get('/posts/featured', { params });
    return response.data;
  },

  /**
   * Get trending posts
   * @param {Object} params - Query parameters
   * @param {number} params.limit - Number of posts to return
   */
  getTrending: async (params = {}) => {
    const response = await api.get('/posts/trending', { params });
    return response.data;
  },

  /**
   * Get posts by author
   * @param {number|string} authorId - Author ID
   * @param {Object} params - Query parameters
   */
  getByAuthor: async (authorId, params = {}) => {
    const response = await api.get(`/authors/${authorId}/posts`, { params });
    return response.data;
  },
};

export default postService;
