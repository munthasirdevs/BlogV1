import { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import { authService } from '@/services';
import { STORAGE_KEYS, ROUTES } from '@/constants';

/**
 * Auth context for managing authentication state
 */
const AuthContext = createContext(undefined);

/**
 * Auth provider component
 */
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  
  // Track mounted state to prevent state updates on unmounted components
  const isMounted = useRef(true);

  // Track mounted state
  useEffect(() => {
    isMounted.current = true;
    return () => {
      isMounted.current = false;
    };
  }, []);

  // Check auth status on mount
  useEffect(() => {
    checkAuth();
  }, []);

  /**
   * Check if user is authenticated
   */
  const checkAuth = useCallback(async () => {
    try {
      const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
      if (!token) {
        if (isMounted.current) {
          setIsLoading(false);
        }
        return;
      }

      const userData = await authService.getMe();
      if (isMounted.current) {
        setUser(userData);
        setIsAuthenticated(true);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
      localStorage.removeItem(STORAGE_KEYS.USER);
      if (isMounted.current) {
        setUser(null);
        setIsAuthenticated(false);
      }
    } finally {
      if (isMounted.current) {
        setIsLoading(false);
      }
    }
  }, []);

  /**
   * Login user
   * @param {Object} credentials - Login credentials
   * @param {string} credentials.email - User email
   * @param {string} credentials.password - User password
   * @param {boolean} credentials.remember - Remember me option
   */
  const login = useCallback(async (credentials) => {
    try {
      const { remember, ...loginData } = credentials;
      const response = await authService.login(loginData);
      const { user, access_token, refresh_token } = response;

      // Store tokens
      localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, access_token);
      localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refresh_token);
      localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user));

      // Handle remember me - set expiry if needed
      if (remember) {
        localStorage.setItem(STORAGE_KEYS.REMEMBER_ME, 'true');
        // Set token expiry to 30 days
        const expiry = new Date();
        expiry.setDate(expiry.getDate() + 30);
        localStorage.setItem(STORAGE_KEYS.TOKEN_EXPIRY, expiry.getTime().toString());
      } else {
        localStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
        localStorage.removeItem(STORAGE_KEYS.TOKEN_EXPIRY);
      }

      setUser(user);
      setIsAuthenticated(true);

      return { success: true };
    } catch (error) {
      console.error('Login failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Login failed',
      };
    }
  }, []);

  /**
   * Register user
   */
  const register = useCallback(async (userData) => {
    try {
      const response = await authService.register(userData);
      const { user, access_token, refresh_token, requires_verification } = response;

      // Store tokens
      localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, access_token);
      localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refresh_token);
      localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user));

      setUser(user);
      setIsAuthenticated(true);

      return { 
        success: true,
        requiresVerification: requires_verification || false,
      };
    } catch (error) {
      console.error('Registration failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Registration failed',
      };
    }
  }, []);

  /**
   * Logout user
   */
  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } catch (error) {
      console.error('Logout failed:', error);
    } finally {
      // Clear local storage
      localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
      localStorage.removeItem(STORAGE_KEYS.USER);
      localStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
      localStorage.removeItem(STORAGE_KEYS.TOKEN_EXPIRY);

      if (isMounted.current) {
        setUser(null);
        setIsAuthenticated(false);
      }
    }
  }, []);

  /**
   * Verify email with token
   */
  const verifyEmail = useCallback(async (token) => {
    try {
      const response = await authService.verifyEmail(token);
      return { success: true, data: response.data };
    } catch (error) {
      console.error('Email verification failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Email verification failed',
      };
    }
  }, []);

  /**
   * Resend verification email
   */
  const resendVerificationEmail = useCallback(async (email) => {
    try {
      const response = await authService.resendVerificationEmail(email);
      return { success: true, data: response.data };
    } catch (error) {
      console.error('Resend verification failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to resend verification email',
      };
    }
  }, []);

  /**
   * Request password reset
   */
  const forgotPassword = useCallback(async (email) => {
    try {
      const response = await authService.forgotPassword(email);
      return { success: true, data: response.data };
    } catch (error) {
      console.error('Password reset request failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to request password reset',
      };
    }
  }, []);

  /**
   * Reset password with token
   */
  const resetPassword = useCallback(async (data) => {
    try {
      const response = await authService.resetPassword(data);
      return { success: true, data: response.data };
    } catch (error) {
      console.error('Password reset failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Failed to reset password',
      };
    }
  }, []);

  /**
   * Update user data
   */
  const updateUser = useCallback((userData) => {
    setUser((prev) => ({ ...prev, ...userData }));
    const currentUser = JSON.parse(localStorage.getItem(STORAGE_KEYS.USER) || '{}');
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify({ ...currentUser, ...userData }));
  }, []);

  /**
   * Check if token is expired
   */
  const isTokenExpired = useCallback(() => {
    const expiry = localStorage.getItem(STORAGE_KEYS.TOKEN_EXPIRY);
    if (!expiry) return false;
    return Date.now() > parseInt(expiry, 10);
  }, []);

  const value = {
    user,
    isLoading,
    isRefreshing,
    isAuthenticated,
    login,
    register,
    logout,
    updateUser,
    checkAuth,
    verifyEmail,
    resendVerificationEmail,
    forgotPassword,
    resetPassword,
    isTokenExpired,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

/**
 * Hook to use auth context
 */
export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;
