import axios from 'axios';
import { STORAGE_KEYS, ROUTES } from '@/constants';

/**
 * Create axios instance with base configuration
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 30000, // 30 seconds
});

/**
 * Request interceptor - Add auth token to requests
 */
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Log request in development
    if (import.meta.env.DEV) {
      console.log('[API Request]', config.method?.toUpperCase(), config.url);
    }

    return config;
  },
  (error) => {
    console.error('[API Request Error]', error);
    return Promise.reject(error);
  }
);

/**
 * Response interceptor - Handle errors and token refresh
 */
api.interceptors.response.use(
  (response) => {
    // Log response in development
    if (import.meta.env.DEV) {
      console.log('[API Response]', response.status, response.config.url);
    }
    return response;
  },
  async (error) => {
    const originalRequest = error.config;

    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN);

        if (!refreshToken) {
          // No refresh token, redirect to login
          handleLogout();
          return Promise.reject(error);
        }

        // Try to refresh the token
        const response = await axios.post(
          `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'}/auth/refresh`,
          { refresh_token: refreshToken }
        );

        const { access_token, refresh_token } = response.data;

        // Store new tokens
        localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, access_token);
        if (refresh_token) {
          localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refresh_token);
        }

        // Retry original request with new token
        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        return api(originalRequest);
      } catch (refreshError) {
        console.error('[Token Refresh Failed]', refreshError);
        handleLogout();
        return Promise.reject(refreshError);
      }
    }

    // Handle other errors
    const errorMessage = error.response?.data?.message || error.message || 'An error occurred';
    console.error('[API Error]', error.response?.status, errorMessage);

    return Promise.reject(error);
  }
);

/**
 * Handle logout and redirect
 */
function handleLogout() {
  localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
  localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
  localStorage.removeItem(STORAGE_KEYS.USER);

  // Redirect to login if not already there
  if (window.location.pathname !== ROUTES.LOGIN) {
    window.location.href = ROUTES.LOGIN;
  }
}

export default api;
