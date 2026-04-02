/**
 * Application constants
 */

export const APP_NAME = import.meta.env.VITE_APP_NAME || 'Blog Platform';
export const APP_VERSION = import.meta.env.VITE_APP_VERSION || '1.0.0';

/**
 * API endpoints
 */
export const API_ENDPOINTS = {
  // Auth
  LOGIN: '/auth/login',
  REGISTER: '/auth/register',
  LOGOUT: '/auth/logout',
  REFRESH_TOKEN: '/auth/refresh',
  ME: '/auth/me',
  
  // Posts
  POSTS: '/posts',
  POST_BY_ID: (id) => `/posts/${id}`,
  POST_BY_SLUG: (slug) => `/posts/slug/${slug}`,
  CREATE_POST: '/posts',
  UPDATE_POST: (id) => `/posts/${id}`,
  DELETE_POST: (id) => `/posts/${id}`,
  
  // Comments
  COMMENTS: '/comments',
  COMMENT_BY_ID: (id) => `/comments/${id}`,
  
  // Users
  USERS: '/users',
  USER_BY_ID: (id) => `/users/${id}`,
  USER_PROFILE: (username) => `/users/profile/${username}`,
  
  // Categories
  CATEGORIES: '/categories',
  CATEGORY_BY_ID: (id) => `/categories/${id}`,
  
  // Tags
  TAGS: '/tags',
  TAG_BY_ID: (id) => `/tags/${id}`,
  
  // Upload
  UPLOAD: '/upload',
};

/**
 * Route paths
 */
export const ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  FORGOT_PASSWORD: '/forgot-password',
  RESET_PASSWORD: '/reset-password',
  VERIFY_EMAIL_PENDING: '/verify-email/pending',
  VERIFY_EMAIL_SUCCESS: '/verify-email/success',
  VERIFY_EMAIL_FAILURE: '/verify-email/failure',
  TERMS: '/terms',
  PRIVACY: '/privacy',

  // Posts
  POSTS: '/posts',
  POST_DETAIL: (slug) => `/posts/${slug}`,
  CREATE_POST: '/posts/create',
  EDIT_POST: (id) => `/posts/${id}/edit`,

  // Categories
  CATEGORIES: '/categories',
  CATEGORY_DETAIL: (slug) => `/categories/${slug}`,

  // Tags
  TAGS: '/tags',
  TAG_DETAIL: (slug) => `/tags/${slug}`,

  // Search
  SEARCH: '/search',

  // Pages
  ABOUT: '/about',
  CONTACT: '/contact',

  // User
  PROFILE: (username) => `/profile/${username}`,
  AUTHOR: (username) => `/author/${username}`,
  SETTINGS: '/settings',
  BOOKMARKS: '/bookmarks',

  // Admin
  ADMIN: '/admin',
  ADMIN_DASHBOARD: '/admin/dashboard',
  ADMIN_POSTS: '/admin/posts',
  ADMIN_USERS: '/admin/users',
  ADMIN_CATEGORIES: '/admin/categories',
  ADMIN_SETTINGS: '/admin/settings',
};

/**
 * Local storage keys
 */
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  REFRESH_TOKEN: 'refresh_token',
  USER: 'user',
  THEME: 'theme',
  REMEMBER_ME: 'remember_me',
  TOKEN_EXPIRY: 'token_expiry',
};

/**
 * Query keys for React Query
 */
export const QUERY_KEYS = {
  AUTH: {
    ME: 'auth-me',
  },
  POSTS: {
    ALL: 'posts',
    DETAIL: 'post-detail',
    SLUG: 'post-slug',
  },
  USERS: {
    ALL: 'users',
    DETAIL: 'user-detail',
    PROFILE: 'user-profile',
  },
  CATEGORIES: {
    ALL: 'categories',
    DETAIL: 'category-detail',
  },
  TAGS: {
    ALL: 'tags',
    DETAIL: 'tag-detail',
  },
  COMMENTS: {
    ALL: 'comments',
  },
  ANALYTICS: {
    STATS: 'analytics-stats',
    VIEWS: 'analytics-views',
    ENGAGEMENT: 'analytics-engagement',
    TOP_POSTS: 'analytics-top-posts',
    TRAFFIC: 'analytics-traffic',
    DEVICES: 'analytics-devices',
    GEO: 'analytics-geo',
  },
};

/**
 * HTTP status codes
 */
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  CONFLICT: 409,
  INTERNAL_SERVER_ERROR: 500,
};

/**
 * Error messages
 */
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Network error. Please check your connection.',
  UNAUTHORIZED: 'Please log in to continue.',
  FORBIDDEN: 'You do not have permission to perform this action.',
  NOT_FOUND: 'The requested resource was not found.',
  SERVER_ERROR: 'Something went wrong. Please try again later.',
  VALIDATION_ERROR: 'Please check your input and try again.',
};

/**
 * Pagination defaults
 */
export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_LIMIT: 10,
  LIMIT_OPTIONS: [5, 10, 20, 50],
};

/**
 * Date formats
 */
export const DATE_FORMATS = {
  SHORT: 'MMM d, yyyy',
  LONG: 'MMMM d, yyyy',
  WITH_TIME: 'MMM d, yyyy h:mm a',
  RELATIVE: 'relative',
};
