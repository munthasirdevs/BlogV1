@echo off
REM Quick Start Script for Masterclass Blog Platform
REM Run this script to set up and start the development environment

echo ============================================
echo Masterclass Blog Platform - Quick Start
echo ============================================
echo.

REM Check if we're in the right directory
if not exist "backend\artisan" (
    echo Error: Please run this script from the blog root directory
    pause
    exit /b 1
)

echo [1/5] Setting up backend...
cd backend

echo [2/5] Installing Composer dependencies...
call composer install --no-interaction --prefer-dist

echo [3/5] Generating application key...
if not exist ".env" (
    copy .env.example .env
)
call php artisan key:generate

echo.
echo ============================================
echo DATABASE CONFIGURATION REQUIRED
echo ============================================
echo Please configure your database in backend\.env:
echo   DB_CONNECTION=mysql
echo   DB_HOST=127.0.0.1
echo   DB_PORT=3306
echo   DB_DATABASE=blog_platform
echo   DB_USERNAME=root
echo   DB_PASSWORD=your_password
echo ============================================
echo.

set /p continue="Have you configured the database? (y/n): "
if /i not "%continue%"=="y" (
    echo Please configure the database and run this script again.
    cd ..
    pause
    exit /b 1
)

echo [4/5] Running database migrations...
call php artisan migrate

echo [5/5] Seeding database with sample data...
call php artisan db:seed

cd ..

echo.
echo ============================================
echo Setup Complete!
echo ============================================
echo.
echo To start the development servers:
echo.
echo   Backend (API):
echo     cd backend
echo     php artisan serve
echo.
echo   Frontend:
echo     cd frontend
echo     php -S localhost:3000
echo.
echo Default credentials:
echo   Admin: admin@blog.com / password123
echo   User:  user1@blog.com / password123
echo.
echo ============================================

pause
