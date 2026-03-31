/**
 * Authentication Module
 * Handles user authentication state and actions
 */

class AuthService {
    constructor() {
        this.user = null;
        this.init();
    }

    /**
     * Initialize auth state
     */
    init() {
        const userData = localStorage.getItem('user');
        if (userData) {
            this.user = JSON.parse(userData);
        }
        this.updateUI();
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!api.getToken() && !!this.user;
    }

    /**
     * Check if user is admin
     */
    isAdmin() {
        return this.user && this.user.role === 'admin';
    }

    /**
     * Get current user
     */
    getUser() {
        return this.user;
    }

    /**
     * Login user
     */
    async login(email, password, remember = false) {
        try {
            const response = await api.post('/auth/login', {
                email,
                password,
                remember,
            });

            if (response.success) {
                api.setToken(response.data.token);
                this.user = response.data.user;
                api.setUser(response.data.user);
                this.updateUI();
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Register new user
     */
    async register(name, email, password, passwordConfirmation) {
        try {
            const response = await api.post('/auth/register', {
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });

            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            await api.post('/auth/logout');
        } catch (error) {
            // Ignore errors during logout
        } finally {
            api.clearToken();
            this.user = null;
            this.updateUI();
            window.location.href = '/';
        }
    }

    /**
     * Get current user profile
     */
    async getProfile() {
        try {
            const response = await api.get('/auth/me');
            if (response.success) {
                this.user = response.data;
                api.setUser(response.data);
                return { success: true, user: response.data };
            }
            return { success: false };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update user profile
     */
    async updateProfile(data) {
        try {
            const response = await api.put('/user/profile', data);
            if (response.success) {
                this.user = response.data;
                api.setUser(response.data);
                this.updateUI();
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update password
     */
    async updatePassword(currentPassword, newPassword, newPasswordConfirmation) {
        try {
            const response = await api.put('/user/password', {
                current_password: currentPassword,
                password: newPassword,
                password_confirmation: newPasswordConfirmation,
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update UI based on auth state
     */
    updateUI() {
        const authElements = document.querySelectorAll('[data-auth]');
        const guestElements = document.querySelectorAll('[data-guest]');
        const adminElements = document.querySelectorAll('[data-admin]');
        const userNameElements = document.querySelectorAll('[data-user-name]');
        const userAvatarElements = document.querySelectorAll('[data-user-avatar]');

        if (this.isAuthenticated()) {
            authElements.forEach(el => el.classList.remove('hidden'));
            guestElements.forEach(el => el.classList.add('hidden'));
            
            if (this.isAdmin()) {
                adminElements.forEach(el => el.classList.remove('hidden'));
            } else {
                adminElements.forEach(el => el.classList.add('hidden'));
            }

            userNameElements.forEach(el => {
                el.textContent = this.user?.name || '';
            });

            userAvatarElements.forEach(el => {
                if (this.user?.avatar) {
                    el.src = this.user.avatar;
                }
            });
        } else {
            authElements.forEach(el => el.classList.add('hidden'));
            guestElements.forEach(el => el.classList.remove('hidden'));
            adminElements.forEach(el => el.classList.add('hidden'));
        }
    }

    /**
     * Require authentication - redirect if not authenticated
     */
    requireAuth(redirectUrl = '/pages/login.html') {
        if (!this.isAuthenticated()) {
            window.location.href = redirectUrl;
            return false;
        }
        return true;
    }

    /**
     * Require admin - redirect if not admin
     */
    requireAdmin(redirectUrl = '/') {
        if (!this.isAdmin()) {
            window.location.href = redirectUrl;
            return false;
        }
        return true;
    }

    /**
     * Require guest - redirect if authenticated
     */
    requireGuest(redirectUrl = '/') {
        if (this.isAuthenticated()) {
            window.location.href = redirectUrl;
            return false;
        }
        return true;
    }
}

// Create singleton instance
const authService = new AuthService();
window.authService = authService;
