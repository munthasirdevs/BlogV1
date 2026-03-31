# Quick Start Guide

## For Windows

1. **Run the quick start script:**
   ```
   double-click quick-start.bat
   ```

2. **Configure your database** when prompted

3. **Start the servers:**
   
   **Terminal 1 (Backend):**
   ```cmd
   cd backend
   php artisan serve
   ```
   
   **Terminal 2 (Frontend):**
   ```cmd
   cd frontend
   php -S localhost:3000
   ```

4. **Access the application:**
   - Frontend: http://localhost:3000
   - API: http://localhost:8000/api/v1

## For macOS/Linux

1. **Set up backend:**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure database** in `backend/.env`

3. **Run migrations:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Start servers:**
   ```bash
   # Terminal 1 - Backend
   cd backend && php artisan serve
   
   # Terminal 2 - Frontend
   cd frontend && php -S localhost:3000
   ```

## Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@blog.com | password123 |
| User | user1@blog.com | password123 |

## Troubleshooting

### Database Connection Error
Make sure your database server is running and credentials in `.env` are correct.

### Port Already in Use
Change the port in the serve command:
```bash
php artisan serve --port=8080
```

### Composer Issues
```bash
composer clear-cache
composer update
```

### Permission Issues (Linux/Mac)
```bash
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
```
