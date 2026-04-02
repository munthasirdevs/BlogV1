# Authentication Flow Guide

## Quick Reference for Phase 17

---

## 🔐 Authentication Flows

### 1. Registration Flow

```
User visits /register
    ↓
Fill form (name, email, password, confirm password, accept terms)
    ↓
Password strength indicator updates in real-time
    ↓
Click "Create Account"
    ↓
Form validates (zod schema)
    ↓
API: POST /api/auth/register
    ↓
Success: Store tokens, redirect to /verify-email/pending
Error: Show toast + inline errors
    ↓
User clicks verification link in email
    ↓
API: POST /api/auth/verify-email
    ↓
Success: Show success page → Redirect to home/login
Failure: Show error page → Option to request new link
```

### 2. Login Flow

```
User visits /login
    ↓
Fill form (email, password, remember me)
    ↓
Click "Sign In"
    ↓
Form validates
    ↓
API: POST /api/auth/login
    ↓
Success: Store tokens + user data
    - If remember me: Set 30-day expiry
    ↓
Redirect to intended page (or home)
Show success toast
    ↓
Error: Show toast + inline errors
```

### 3. Password Reset Flow

```
User visits /forgot-password
    ↓
Enter email address
    ↓
API: POST /api/auth/forgot-password
    ↓
Success: Show success message + resend option
    ↓
User clicks link in email
    ↓
Navigate to /reset-password?token=xxx&email=xxx
    ↓
Validate token (client-side check)
    ↓
Enter new password + confirm
    ↓
API: POST /api/auth/reset-password
    ↓
Success: Show success toast → Redirect to /login (2s delay)
Error: Show toast + inline errors
```

### 4. Email Verification Flow

```
After registration → /verify-email/pending
    ↓
Display: "Check your email" message
Show user's email address
Provide "Resend" button
    ↓
User clicks "Resend Verification Email"
    ↓
API: POST /api/auth/resend-verification
    ↓
Success: Show toast notification
Error: Show toast notification
    ↓
User clicks verification link in email
    ↓
Navigate to /verify-email?token=xxx
    ↓
API: POST /api/auth/verify-email
    ↓
Success: Show success page → Go to home/login
Failure: Show error page → Request new link
```

### 5. Protected Route Flow

```
User tries to access /settings (protected)
    ↓
ProtectedRoute checks isAuthenticated
    ↓
If false: Redirect to /login?redirect=/settings
Save intended destination in location.state
    ↓
User logs in successfully
    ↓
Navigate to location.state.from.pathname (/settings)
    ↓
User accesses protected page
```

### 6. Token Refresh Flow

```
API request fails with 401 Unauthorized
    ↓
Axios interceptor catches 401
    ↓
Get refresh_token from localStorage
    ↓
API: POST /api/auth/refresh
    ↓
Success: Store new access_token + refresh_token
Retry original request with new token
    ↓
Failure: Clear all auth data
Redirect to /login
```

### 7. Logout Flow

```
User clicks user menu → Logout
    ↓
API: POST /api/auth/logout
    ↓
Clear localStorage:
- auth_token
- refresh_token
- user
- remember_me
- token_expiry
    ↓
Clear AuthContext state
    ↓
Show success toast: "Logged Out"
    ↓
Redirect to /home
```

---

## 📊 Component Hierarchy

```
App.jsx
├── ToastProvider
│   └── AuthProvider
│       └── BrowserRouter
│           └── AppRoutes
│               ├── PublicRoute
│               │   ├── LoginPage
│               │   ├── RegisterPage
│               │   ├── ForgotPasswordPage
│               │   └── ResetPasswordPage
│               ├── ProtectedRoute
│               │   └── SettingsPage
│               └── AdminRoute
│                   └── Admin pages
```

---

## 🔑 LocalStorage Keys

| Key | Purpose | Duration |
|-----|---------|----------|
| `auth_token` | JWT access token | Session or 30 days |
| `refresh_token` | JWT refresh token | Session or 30 days |
| `user` | User data (JSON) | Session or 30 days |
| `remember_me` | Remember me flag | 30 days |
| `token_expiry` | Token expiry timestamp | 30 days |
| `theme` | Dark/light mode preference | Persistent |

---

## ✅ Validation Rules

### Login
- **Email**: Required, valid email format
- **Password**: Required

### Registration
- **Name**: Required, min 2 chars, max 50 chars
- **Email**: Required, valid email format
- **Password**: Required, min 8 chars, uppercase, lowercase, number, special char
- **Password Confirmation**: Required, must match password
- **Terms**: Required, must be accepted

### Password Reset
- **Password**: Same as registration
- **Password Confirmation**: Required, must match password

---

## 🎨 UI States

### Loading States
- Button shows spinner
- Button text changes to "Loading..."
- Form inputs disabled
- Submit prevented

### Error States
- Inline field errors with XCircle icon
- Root error in Alert component (dismissible)
- Toast notification for API errors
- Red border on invalid fields

### Success States
- Toast notification (green)
- Auto-redirect after delay (if applicable)
- Success icon/illustration

---

## 🌐 API Endpoints

```
POST   /api/auth/login
Body: { email, password, remember? }
Response: { user, access_token, refresh_token, requires_verification? }

POST   /api/auth/register
Body: { name, email, password, password_confirmation }
Response: { user, access_token, refresh_token, requires_verification? }

POST   /api/auth/logout
Headers: { Authorization: Bearer <token> }
Response: { message }

GET    /api/auth/me
Headers: { Authorization: Bearer <token> }
Response: { user }

POST   /api/auth/refresh
Body: { refresh_token }
Response: { access_token, refresh_token }

POST   /api/auth/verify-email
Body: { token }
Response: { message, user? }

POST   /api/auth/resend-verification
Body: { email }
Response: { message }

POST   /api/auth/forgot-password
Body: { email }
Response: { message }

POST   /api/auth/reset-password
Body: { token, email, password, password_confirmation }
Response: { message }
```

---

## 🧪 Testing Commands

```bash
# Development mode
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint code
npm run lint

# Format code
npm run format
```

---

## 🐛 Common Issues & Solutions

### Issue: Token expires immediately
**Solution**: Check backend token configuration. Ensure access token expiry is set correctly (e.g., 15 minutes to 1 hour).

### Issue: Remember me doesn't work
**Solution**: Verify `REMEMBER_ME` and `TOKEN_EXPIRY` are being set in localStorage. Check backend for refresh token expiry.

### Issue: Email verification link doesn't work
**Solution**: Ensure backend generates valid tokens with proper expiry (e.g., 24 hours). Check token format in URL.

### Issue: 401 on every request
**Solution**: Check if auth token is being added to requests. Verify axios interceptor is working.

### Issue: Toast doesn't show
**Solution**: Ensure ToastProvider wraps AppRoutes. Check if useToast is called within provider.

---

## 📝 Environment Variables

Create `.env` file in frontend directory:

```env
VITE_APP_NAME=Blog Platform
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_VERSION=1.0.0
```

---

## 🔒 Security Considerations

1. **Never store passwords** - Only store tokens
2. **Use HTTPS** in production
3. **Set secure cookie flags** if using cookies
4. **Implement rate limiting** on backend
5. **Validate all inputs** on backend (not just frontend)
6. **Use CSRF protection** for sensitive operations
7. **Implement account lockout** after failed attempts
8. **Log authentication events** for security auditing

---

## 📞 Support

For issues or questions:
1. Check the PHASE_17_COMPLETION_REPORT.md
2. Review the code comments in each file
3. Check browser console for errors
4. Verify backend API is running and accessible
