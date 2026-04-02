import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Categories service
 */
export const categoryService = {
  /**
   * Get all categories
   * @param {Object} params - Query parameters
   */
  getAll: async (params = {}) => {
    const response = await api.get(API_ENDPOINTS.CATEGORIES, { params });
    return response.data;
  },

  /**
   * Get category by ID
   * @param {number|string} id - Category ID
   */
  getById: async (id) => {
    const response = await api.get(API_ENDPOINTS.CATEGORY_BY_ID(id));
    return response.data;
  },

  /**
   * Get category by slug
   * @param {string} slug - Category slug
   */
  getBySlug: async (slug) => {
    const response = await api.get(`/categories/slug/${slug}`);
    return response.data;
  },

  /**
   * Create new category
   * @param {Object} categoryData - Category data
   */
  create: async (categoryData) => {
    const response = await api.post(API_ENDPOINTS.CATEGORIES, categoryData);
    return response.data;
  },

  /**
   * Update category
   * @param {number|string} id - Category ID
   * @param {Object} categoryData - Updated category data
   */
  update: async (id, categoryData) => {
    const response = await api.put(API_ENDPOINTS.CATEGORY_BY_ID(id), categoryData);
    return response.data;
  },

  /**
   * Delete category
   * @param {number|string} id - Category ID
   */
  delete: async (id) => {
    const response = await api.delete(API_ENDPOINTS.CATEGORY_BY_ID(id));
    return response.data;
  },

  /**
   * Get posts by category
   * @param {number|string} categoryId - Category ID
   * @param {Object} params - Query parameters
   */
  getPosts: async (categoryId, params = {}) => {
    const response = await api.get(`/categories/${categoryId}/posts`, { params });
    return response.data;
  },

  /**
   * Get category by slug with posts
   * @param {string} slug - Category slug
   * @param {Object} params - Query parameters for posts
   */
  getBySlugWithPosts: async (slug, params = {}) => {
    const categoryResponse = await api.get(`/categories/slug/${slug}`);
    const categoryId = categoryResponse.data.data.id;
    const postsResponse = await api.get(`/categories/${categoryId}/posts`, { params });
    return {
      category: categoryResponse.data.data,
      posts: postsResponse.data.data,
      meta: postsResponse.data.meta,
    };
  },
};

export default categoryService;
