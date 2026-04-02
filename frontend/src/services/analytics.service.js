import api from './api';

/**
 * Analytics service for dashboard statistics and charts
 */
export const analyticsService = {
  /**
   * Get dashboard statistics
   * @returns {Promise<Object>} Dashboard stats
   */
  getStats: async () => {
    const response = await api.get('/analytics/stats');
    return response.data;
  },

  /**
   * Get views over time data
   * @param {Object} params - Query parameters
   * @param {string} params.period - Time period (7d, 30d, 90d)
   * @param {string} params.startDate - Start date (YYYY-MM-DD)
   * @param {string} params.endDate - End date (YYYY-MM-DD)
   * @returns {Promise<Array>} Views data
   */
  getViewsOverTime: async (params = {}) => {
    const response = await api.get('/analytics/views', { params });
    return response.data;
  },

  /**
   * Get likes and comments over time
   * @param {Object} params - Query parameters
   * @param {string} params.period - Time period
   * @returns {Promise<Array>} Engagement data
   */
  getEngagementOverTime: async (params = {}) => {
    const response = await api.get('/analytics/engagement', { params });
    return response.data;
  },

  /**
   * Get top posts by views
   * @param {Object} params - Query parameters
   * @param {number} params.limit - Number of posts to return
   * @param {string} params.period - Time period
   * @returns {Promise<Array>} Top posts
   */
  getTopPosts: async (params = {}) => {
    const response = await api.get('/analytics/top-posts', { params });
    return response.data;
  },

  /**
   * Get traffic sources
   * @param {Object} params - Query parameters
   * @param {string} params.period - Time period
   * @returns {Promise<Array>} Traffic sources data
   */
  getTrafficSources: async (params = {}) => {
    const response = await api.get('/analytics/traffic-sources', { params });
    return response.data;
  },

  /**
   * Get device breakdown
   * @param {Object} params - Query parameters
   * @param {string} params.period - Time period
   * @returns {Promise<Array>} Device data
   */
  getDeviceBreakdown: async (params = {}) => {
    const response = await api.get('/analytics/devices', { params });
    return response.data;
  },

  /**
   * Get geographic data
   * @param {Object} params - Query parameters
   * @param {string} params.period - Time period
   * @returns {Promise<Array>} Geographic data
   */
  getGeographicData: async (params = {}) => {
    const response = await api.get('/analytics/geo', { params });
    return response.data;
  },

  /**
   * Export analytics data
   * @param {Object} params - Export parameters
   * @param {string} params.type - Export type (posts, users, analytics)
   * @param {string} params.startDate - Start date
   * @param {string} params.endDate - End date
   * @returns {Promise<Blob>} CSV file
   */
  exportData: async (params) => {
    const response = await api.get('/analytics/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },
};

export default analyticsService;
