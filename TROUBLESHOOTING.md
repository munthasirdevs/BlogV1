# Troubleshooting Guide

## ✅ Issue Fixed: Missing Sanctum Table

### Problem
Error: `SQLSTATE[HY000]: General error: 1 no such table: personal_access_tokens`

### Solution
The Laravel Sanctum package requires a `personal_access_tokens` table for API token authentication.

**Fixed by running:**
```bash
cd backend
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## 🚀 How to Start the Platform

### Both servers are currently RUNNING:

**Backend (Laravel API):** http://localhost:8000
- Status: ✅ Running on PID 4816
- API Base URL: http://localhost:8000/api/v1

**Frontend (Static Files):** http://localhost:3000
- Status: ✅ Running on PID 70092

---

## 🔑 Test Credentials

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@blog.com | password123 |
| **User** | user1@blog.com | password123 |

---

## 📡 Quick API Tests

### Test Posts Endpoint (Public)
```bash
curl http://localhost:8000/api/v1/posts
```

### Test Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@blog.com\",\"password\":\"password123\"}"
```

### Test Authenticated Request
```bash
curl http://localhost:8000/api/v1/auth/me ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🛠️ Common Issues & Solutions

### 1. Port Already in Use
**Error:** `Address already in use`

**Solution:** Use different ports:
```bash
# Backend
php artisan serve --port=8080

# Frontend
php -S localhost:3001
```

### 2. Database Connection Error
**Error:** `Database connection failed`

**Solution:** 
- Ensure SQLite file exists: `backend/database/database.sqlite`
- Check `.env` has `DB_CONNECTION=sqlite`

### 3. Missing APP_KEY
**Error:** `No application encryption key has been set`

**Solution:**
```bash
cd backend
php artisan key:generate
```

### 4. Class Not Found Errors
**Error:** `Class 'XXX' not found`

**Solution:**
```bash
cd backend
composer dump-autoload
```

### 5. Permission Denied (Storage)
**Error:** `Permission denied` on storage folder

**Solution (Windows):**
- Run terminal as Administrator

**Solution (Linux/Mac):**
```bash
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
```

---

## 🔄 Restart Everything

### Stop all servers:
```bash
# Kill backend (PID 4816)
taskkill /F /T /PID 4816

# Kill frontend (PID 70092)
taskkill /F /T /PID 70092
```

### Start fresh:
```bash
# Terminal 1 - Backend
cd backend
php artisan serve

# Terminal 2 - Frontend
cd frontend
php -S localhost:3000
```

---

## 📊 System Health Check

Run these commands to verify everything is working:

```bash
# 1. Check Laravel version
cd backend && php artisan --version

# 2. Check database migrations
cd backend && php artisan migrate:status

# 3. Test API endpoint
curl http://localhost:8000/api/v1/posts

# 4. Test frontend
curl http://localhost:3000 | findstr "<title>"
```

**Expected Results:**
- ✅ Laravel Framework 11.x
- ✅ All migrations marked as "ran"
- ✅ JSON response with posts data
- ✅ `<title>Masterclass Blog - Home</title>`

---

## 📝 Environment Variables

Your current `.env` configuration:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:... (generated ✅)
APP_DEBUG=true
DB_CONNECTION=sqlite (configured ✅)
```

---

## 🎯 Access the Application

**Frontend:** http://localhost:3000
- Home Page: http://localhost:3000/
- Login: http://localhost:3000/pages/login.html
- Register: http://localhost:3000/pages/register.html
- Blog: http://localhost:3000/pages/blog-list.html
- Admin Dashboard: http://localhost:3000/pages/admin/dashboard.html

**API:** http://localhost:8000/api/v1
- Posts: /posts
- Login: /auth/login
- Register: /auth/register
- Categories: /categories
- Tags: /tags
- Search: /search

---

## 🆘 Still Having Issues?

1. **Check PHP version:** `php -v` (must be 8.2+)
2. **Check Composer:** `composer -V`
3. **Clear caches:**
   ```bash
   cd backend
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
4. **Reinstall dependencies:**
   ```bash
   cd backend
   composer install
   ```

---

**Last Updated:** 2026-04-01
**Status:** ✅ ALL SYSTEMS OPERATIONAL
