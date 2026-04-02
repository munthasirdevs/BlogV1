import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Users service
 */
export const userService = {
  /**
   * Get all users with pagination
   * @param {Object} params - Query parameters
   * @param {number} params.page - Page number
   * @param {number} params.limit - Items per page
   * @param {string} params.search - Search query
   */
  getAll: async (params = {}) => {
    const response = await api.get(API_ENDPOINTS.USERS, { params });
    return response.data;
  },

  /**
   * Get user by ID
   * @param {number|string} id - User ID
   */
  getById: async (id) => {
    const response = await api.get(API_ENDPOINTS.USER_BY_ID(id));
    return response.data;
  },

  /**
   * Get user profile by username
   * @param {string} username - Username
   */
  getProfile: async (username) => {
    const response = await api.get(API_ENDPOINTS.USER_PROFILE(username));
    return response.data;
  },

  /**
   * Update user profile
   * @param {Object} userData - Updated user data
   * @param {string} userData.name - User name
   * @param {string} userData.username - Username
   * @param {string} userData.bio - User bio
   * @param {File} userData.avatar - Avatar image
   */
  updateProfile: async (userData) => {
    const response = await api.put('/users/profile', userData);
    return response.data;
  },

  /**
   * Update user password
   * @param {Object} passwordData - Password data
   * @param {string} passwordData.current_password - Current password
   * @param {string} passwordData.password - New password
   * @param {string} passwordData.password_confirmation - Password confirmation
   */
  updatePassword: async (passwordData) => {
    const response = await api.put('/users/password', passwordData);
    return response.data;
  },

  /**
   * Delete user account
   */
  deleteAccount: async () => {
    const response = await api.delete('/users/account');
    return response.data;
  },

  /**
   * Get user's posts
   * @param {number|string} userId - User ID
   * @param {Object} params - Query parameters
   */
  getPosts: async (userId, params = {}) => {
    const response = await api.get(`/users/${userId}/posts`, { params });
    return response.data;
  },

  /**
   * Follow a user
   * @param {number|string} userId - User ID to follow
   */
  follow: async (userId) => {
    const response = await api.post(`/users/${userId}/follow`);
    return response.data;
  },

  /**
   * Unfollow a user
   * @param {number|string} userId - User ID to unfollow
   */
  unfollow: async (userId) => {
    const response = await api.post(`/users/${userId}/unfollow`);
    return response.data;
  },

  /**
   * Get user's followers
   * @param {number|string} userId - User ID
   */
  getFollowers: async (userId) => {
    const response = await api.get(`/users/${userId}/followers`);
    return response.data;
  },

  /**
   * Get user's following
   * @param {number|string} userId - User ID
   */
  getFollowing: async (userId) => {
    const response = await api.get(`/users/${userId}/following`);
    return response.data;
  },
};

export default userService;
