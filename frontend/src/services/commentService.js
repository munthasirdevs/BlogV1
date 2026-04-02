import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Comments service
 */
export const commentService = {
  /**
   * Get comments for a post
   * @param {number|string} postId - Post ID
   * @param {Object} params - Query parameters
   */
  getByPost: async (postId, params = {}) => {
    const response = await api.get(`/posts/${postId}/comments`, { params });
    return response.data;
  },

  /**
   * Create new comment
   * @param {Object} commentData - Comment data
   * @param {number} commentData.post_id - Post ID
   * @param {string} commentData.content - Comment content
   * @param {number} commentData.parent_id - Parent comment ID (for replies)
   */
  create: async (commentData) => {
    const response = await api.post(API_ENDPOINTS.COMMENTS, commentData);
    return response.data;
  },

  /**
   * Update comment
   * @param {number|string} id - Comment ID
   * @param {Object} commentData - Updated comment data
   */
  update: async (id, commentData) => {
    const response = await api.put(API_ENDPOINTS.COMMENT_BY_ID(id), commentData);
    return response.data;
  },

  /**
   * Delete comment
   * @param {number|string} id - Comment ID
   */
  delete: async (id) => {
    const response = await api.delete(API_ENDPOINTS.COMMENT_BY_ID(id));
    return response.data;
  },

  /**
   * Like a comment
   * @param {number|string} id - Comment ID
   */
  like: async (id) => {
    const response = await api.post(`/comments/${id}/like`);
    return response.data;
  },

  /**
   * Unlike a comment
   * @param {number|string} id - Comment ID
   */
  unlike: async (id) => {
    const response = await api.post(`/comments/${id}/unlike`);
    return response.data;
  },
};

export default commentService;
