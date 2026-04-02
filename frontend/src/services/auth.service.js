import api from './api';
import { API_ENDPOINTS } from '@/constants';

/**
 * Authentication service
 */
export const authService = {
  /**
   * Login user
   * @param {Object} credentials - Login credentials
   * @param {string} credentials.email - User email
   * @param {string} credentials.password - User password
   */
  login: async (credentials) => {
    const response = await api.post(API_ENDPOINTS.LOGIN, credentials);
    return response.data;
  },

  /**
   * Register new user
   * @param {Object} userData - User registration data
   * @param {string} userData.name - User name
   * @param {string} userData.email - User email
   * @param {string} userData.password - User password
   * @param {string} userData.password_confirmation - Password confirmation
   */
  register: async (userData) => {
    const response = await api.post(API_ENDPOINTS.REGISTER, userData);
    return response.data;
  },

  /**
   * Logout user
   */
  logout: async () => {
    const response = await api.post(API_ENDPOINTS.LOGOUT);
    return response.data;
  },

  /**
   * Get current user
   */
  getMe: async () => {
    const response = await api.get(API_ENDPOINTS.ME);
    return response.data;
  },

  /**
   * Refresh token
   * @param {string} refreshToken - Refresh token
   */
  refreshToken: async (refreshToken) => {
    const response = await api.post(API_ENDPOINTS.REFRESH_TOKEN, { refresh_token: refreshToken });
    return response.data;
  },

  /**
   * Verify email
   * @param {string} token - Verification token
   */
  verifyEmail: async (token) => {
    const response = await api.post('/auth/verify-email', { token });
    return response;
  },

  /**
   * Resend verification email
   * @param {string} email - User email
   */
  resendVerificationEmail: async (email) => {
    const response = await api.post('/auth/resend-verification', { email });
    return response.data;
  },

  /**
   * Request password reset
   * @param {string} email - User email
   */
  forgotPassword: async (email) => {
    const response = await api.post('/auth/forgot-password', { email });
    return response.data;
  },

  /**
   * Reset password
   * @param {Object} data - Reset data
   * @param {string} data.token - Reset token
   * @param {string} data.email - User email
   * @param {string} data.password - New password
   * @param {string} data.password_confirmation - Password confirmation
   */
  resetPassword: async (data) => {
    const response = await api.post('/auth/reset-password', data);
    return response.data;
  },
};

export default authService;
