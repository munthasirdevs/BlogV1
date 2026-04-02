import api from './api';

/**
 * Newsletter service for subscription management
 */
export const newsletterService = {
  /**
   * Subscribe to newsletter
   * @param {string} email - Email address to subscribe
   * @returns {Promise<Object>} Subscription result
   */
  subscribe: async (email) => {
    const response = await api.post('/newsletter/subscribe', { email });
    return response.data;
  },

  /**
   * Unsubscribe from newsletter
   * @param {string} email - Email address to unsubscribe
   * @param {string} token - Unsubscribe token (from email)
   * @returns {Promise<Object>} Unsubscription result
   */
  unsubscribe: async (email, token) => {
    const response = await api.post('/newsletter/unsubscribe', { email, token });
    return response.data;
  },

  /**
   * Confirm newsletter subscription
   * @param {string} token - Confirmation token (from email)
   * @returns {Promise<Object>} Confirmation result
   */
  confirm: async (token) => {
    const response = await api.post('/newsletter/confirm', { token });
    return response.data;
  },

  /**
   * Get newsletter subscription status
   * @param {string} email - Email address to check
   * @returns {Promise<Object>} Subscription status
   */
  getStatus: async (email) => {
    const response = await api.get('/newsletter/status', {
      params: { email },
    });
    return response.data;
  },
};

export default newsletterService;
