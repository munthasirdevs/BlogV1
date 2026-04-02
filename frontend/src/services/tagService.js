import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Tags service
 */
export const tagService = {
  /**
   * Get all tags
   * @param {Object} params - Query parameters
   */
  getAll: async (params = {}) => {
    const response = await api.get(API_ENDPOINTS.TAGS, { params });
    return response.data;
  },

  /**
   * Get tag by ID
   * @param {number|string} id - Tag ID
   */
  getById: async (id) => {
    const response = await api.get(API_ENDPOINTS.TAG_BY_ID(id));
    return response.data;
  },

  /**
   * Get tag by slug
   * @param {string} slug - Tag slug
   */
  getBySlug: async (slug) => {
    const response = await api.get(`/tags/slug/${slug}`);
    return response.data;
  },

  /**
   * Create new tag
   * @param {Object} tagData - Tag data
   */
  create: async (tagData) => {
    const response = await api.post(API_ENDPOINTS.TAGS, tagData);
    return response.data;
  },

  /**
   * Update tag
   * @param {number|string} id - Tag ID
   * @param {Object} tagData - Updated tag data
   */
  update: async (id, tagData) => {
    const response = await api.put(API_ENDPOINTS.TAG_BY_ID(id), tagData);
    return response.data;
  },

  /**
   * Delete tag
   * @param {number|string} id - Tag ID
   */
  delete: async (id) => {
    const response = await api.delete(API_ENDPOINTS.TAG_BY_ID(id));
    return response.data;
  },

  /**
   * Get posts by tag
   * @param {number|string} tagId - Tag ID
   * @param {Object} params - Query parameters
   */
  getPosts: async (tagId, params = {}) => {
    const response = await api.get(`/tags/${tagId}/posts`, { params });
    return response.data;
  },
};

export default tagService;
