# Phase 3: Authentication System - API Documentation

## Overview

This document describes the authentication API endpoints for the Blog Platform. All authentication endpoints use Laravel Sanctum for token-based authentication.

## Base URL

```
/api/v1
```

## Authentication

Protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## Token Expiration

- **Default tokens**: 24 hours (1440 minutes)
- **Remember me tokens**: 30 days (43200 minutes)

---

## Endpoints

### 1. Register User

**POST** `/auth/register`

Register a new user account.

**Rate Limit**: 3 requests per minute

#### Request Body

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123"
}
```

#### Validation Rules

| Field | Rules |
|-------|-------|
| name | required, string, min:2, max:255 |
| email | required, string, email, max:255, unique:users |
| password | required, string, min:8, max:255, confirmed, must contain uppercase, lowercase, numbers, and letters |

#### Success Response (201)

```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "avatar": null,
      "bio": null,
      "role": "user",
      "status": "active",
      "location": null,
      "timezone": "UTC",
      "preferences": {
        "notifications": true,
        "newsletter": false,
        "theme": "light"
      },
      "social_links": {
        "website": null,
        "twitter": null,
        "github": null,
        "linkedin": null,
        "facebook": null
      },
      "stats": {
        "posts_count": 0,
        "comments_count": 0
      },
      "created_at": "2024-01-15T10:30:00+00:00",
      "updated_at": "2024-01-15T10:30:00+00:00"
    }
  }
}
```

#### Error Responses

**422 Validation Error**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["This email is already registered."]
  }
}
```

---

### 2. Login

**POST** `/auth/login`

Authenticate user and receive access token.

**Rate Limit**: 5 requests per minute

#### Request Body

```json
{
  "email": "john@example.com",
  "password": "SecurePass123",
  "remember": false
}
```

#### Validation Rules

| Field | Rules |
|-------|-------|
| email | required, string, email |
| password | required, string |
| remember | nullable, boolean |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|abc123xyz...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": "2024-01-15T10:35:00+00:00",
      "role": "user",
      "status": "active"
    }
  }
}
```

#### Error Responses

**401 Unauthorized**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**401 Account Banned**
```json
{
  "success": false,
  "message": "Your account has been banned. Please contact support."
}
```

---

### 3. Logout

**POST** `/auth/logout`

Logout user by revoking current token.

**Authentication**: Required

#### Success Response (200)

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 4. Logout All Devices

**POST** `/auth/logout-all`

Logout user from all devices by revoking all tokens.

**Authentication**: Required

#### Success Response (200)

```json
{
  "success": true,
  "message": "Logged out from all devices successfully"
}
```

---

### 5. Refresh Token

**POST** `/auth/refresh`

Refresh the current authentication token.

**Authentication**: Required

#### Request Body (Optional)

```json
{
  "remember": false
}
```

#### Success Response (200)

```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "2|def456uvw...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

---

### 6. Get Current User

**GET** `/auth/me`

Get the currently authenticated user's profile.

**Authentication**: Required

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-15T10:35:00+00:00",
    "avatar": "https://example.com/avatar.jpg",
    "bio": "Software developer",
    "role": "user",
    "status": "active",
    "location": "New York",
    "timezone": "America/New_York",
    "preferences": {
      "notifications": true,
      "newsletter": false,
      "theme": "light"
    },
    "social_links": {
      "website": "https://johndoe.com",
      "twitter": "johndoe",
      "github": "johndoe",
      "linkedin": "johndoe",
      "facebook": null
    },
    "stats": {
      "posts_count": 5,
      "comments_count": 12
    },
    "created_at": "2024-01-15T10:30:00+00:00",
    "updated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

---

### 7. Request Password Reset

**POST** `/auth/forgot-password`

Send password reset link to user's email.

**Rate Limit**: 5 requests per minute

#### Request Body

```json
{
  "email": "john@example.com"
}
```

#### Validation Rules

| Field | Rules |
|-------|-------|
| email | required, string, email, max:255 |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Password reset link has been sent to your email."
}
```

**Note**: For security, the same response is returned even if the email doesn't exist.

---

### 8. Reset Password

**POST** `/auth/reset-password`

Reset password using the token from email.

**Rate Limit**: 5 requests per minute

#### Request Body

```json
{
  "token": "reset-token-from-email",
  "email": "john@example.com",
  "password": "NewSecurePass123",
  "password_confirmation": "NewSecurePass123"
}
```

#### Validation Rules

| Field | Rules |
|-------|-------|
| token | required, string |
| email | required, string, email, exists:users |
| password | required, string, min:8, confirmed, must contain uppercase, lowercase, numbers, and letters |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

#### Error Responses

**400 Invalid Token**
```json
{
  "success": false,
  "message": "This password reset token is invalid."
}
```

---

### 9. Verify Email (Signed URL)

**GET** `/auth/verify/{id}/{hash}`

Verify email address using signed URL from email notification.

**Rate Limit**: 10 requests per minute

#### URL Parameters

| Parameter | Description |
|-----------|-------------|
| id | User ID |
| hash | SHA1 hash of user's email |

The URL is pre-signed and includes an expiration timestamp.

#### Success Response (200)

```json
{
  "success": true,
  "message": "Email verified successfully"
}
```

#### Error Responses

**403 Invalid/Expired Link**
```json
{
  "success": false,
  "message": "Invalid or expired verification link"
}
```

**404 User Not Found**
```json
{
  "success": false,
  "message": "User not found"
}
```

---

### 10. Verify Email (Token)

**POST** `/auth/verify-email`

Alternative email verification using token.

**Rate Limit**: 5 requests per minute

#### Request Body

```json
{
  "id": 1,
  "token": "sha1-hash-of-email"
}
```

#### Success Response (200)

```json
{
  "success": true,
  "message": "Email verified successfully."
}
```

---

### 11. Resend Verification Email

**POST** `/auth/resend-verification`

Resend email verification notification.

**Rate Limit**: 3 requests per minute

#### Request Body

```json
{
  "email": "john@example.com"
}
```

#### Validation Rules

| Field | Rules |
|-------|-------|
| email | required, string, email, exists:users |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Verification email has been sent."
}
```

#### Error Responses

**400 Already Verified**
```json
{
  "success": false,
  "message": "Email is already verified."
}
```

---

### 12. Resend Verification (Authenticated)

**POST** `/auth/resend-verification`

Resend verification email for authenticated user.

**Authentication**: Required

**Rate Limit**: 3 requests per minute

#### Success Response (200)

```json
{
  "success": true,
  "message": "Verification email has been sent."
}
```

---

### 13. Get Active Sessions

**GET** `/auth/sessions`

Get list of all active sessions/tokens for the user.

**Authentication**: Required

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "sessions": [
      {
        "id": 1,
        "name": "auth_token_1234567890_abcd",
        "created_at": "2024-01-15T10:30:00+00:00",
        "expires_at": "2024-01-16T10:30:00+00:00",
        "is_expired": false
      },
      {
        "id": 2,
        "name": "auth_token_1234567891_efgh",
        "created_at": "2024-01-14T08:00:00+00:00",
        "expires_at": "2024-01-15T08:00:00+00:00",
        "is_expired": true
      }
    ]
  }
}
```

---

### 14. Revoke Session

**DELETE** `/auth/sessions/{tokenId}`

Revoke a specific session/token.

**Authentication**: Required

#### URL Parameters

| Parameter | Description |
|-----------|-------------|
| tokenId | ID of the token to revoke |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Session revoked successfully"
}
```

#### Error Responses

**400 Cannot Revoke Current Session**
```json
{
  "success": false,
  "message": "Cannot revoke the current session"
}
```

**404 Session Not Found**
```json
{
  "success": false,
  "message": "Session not found"
}
```

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created (registration) |
| 400 | Bad Request |
| 401 | Unauthorized (invalid credentials) |
| 403 | Forbidden (invalid token/expired link) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limited) |
| 500 | Internal Server Error |

---

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| POST /auth/register | 3 requests/minute |
| POST /auth/login | 5 requests/minute |
| POST /auth/forgot-password | 5 requests/minute |
| POST /auth/reset-password | 5 requests/minute |
| GET /auth/verify/{id}/{hash} | 10 requests/minute |
| POST /auth/verify-email | 5 requests/minute |
| POST /auth/resend-verification | 3 requests/minute |

When rate limited, the response includes a `Retry-After` header indicating when to retry.

---

## Security Considerations

1. **Password Requirements**:
   - Minimum 8 characters
   - Must contain uppercase and lowercase letters
   - Must contain at least one number
   - Must contain at least one letter

2. **Token Security**:
   - Tokens are hashed before storage
   - Old tokens are automatically cleaned up (max 5 per user)
   - Password reset revokes all existing tokens

3. **Email Verification**:
   - Signed URLs expire after 24 hours
   - Password reset tokens expire after 60 minutes

4. **Account Protection**:
   - Failed login attempts are rate limited
   - Banned accounts cannot login
   - Email existence is not revealed in password reset responses

---

## Testing

Run the authentication test suite:

```bash
php artisan test --testsuite=Feature --filter=Auth
```

Or run specific test files:

```bash
php artisan test tests/Feature/Auth/AuthenticationTest.php
php artisan test tests/Feature/Auth/PasswordResetTest.php
php artisan test tests/Feature/Auth/EmailVerificationTest.php
php artisan test tests/Feature/Auth/SessionManagementTest.php
php artisan test tests/Feature/Auth/RateLimitingTest.php
```

---

## Environment Variables

```env
# Sanctum Token Configuration
SANCTUM_TOKEN_EXPIRATION=1440

# Stateful domains for Sanctum SPA authentication
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000

# Frontend URL for password reset links
FRONTEND_URL=http://localhost:3000

# Bcrypt rounds for password hashing
BCRYPT_ROUNDS=12
```
