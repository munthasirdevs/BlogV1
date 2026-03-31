# Masterclass Blog Platform

A complete, production-ready blog platform built with Laravel 12 (API-first) and HTML/Tailwind CSS/Vanilla JavaScript frontend.

## Features

### User Features
- ✅ User registration and authentication
- ✅ Email verification
- ✅ Password reset functionality
- ✅ Create, edit, delete blog posts
- ✅ Rich text content with featured images
- ✅ Categories and tags organization
- ✅ Comments system with nested replies
- ✅ Like and bookmark posts
- ✅ Search functionality
- ✅ User profiles

### Admin Features
- ✅ Admin dashboard with statistics
- ✅ User management
- ✅ Content moderation
- ✅ Category and tag management
- ✅ Analytics

### Technical Features
- ✅ RESTful API with Sanctum authentication
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ SEO-friendly URLs
- ✅ Rate limiting
- ✅ Input validation
- ✅ Error handling

## Tech Stack

**Backend:**
- Laravel 11/12
- PHP 8.2+
- MySQL/PostgreSQL
- Laravel Sanctum (API Auth)

**Frontend:**
- HTML5
- Tailwind CSS
- Vanilla JavaScript (ES6+)

## Project Structure

```
blog/
├── backend/                 # Laravel backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Policies/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── .env
├── frontend/                # Static frontend
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── api.js
│   │   ├── auth.js
│   │   ├── blog.js
│   │   ├── comments.js
│   │   ├── search.js
│   │   ├── taxonomy.js
│   │   └── ui.js
│   ├── pages/
│   │   ├── admin/
│   │   ├── login.html
│   │   ├── register.html
│   │   ├── blog-list.html
│   │   ├── blog-detail.html
│   │   └── create-post.html
│   └── index.html
└── docs/                    # Documentation
    ├── 01-PRODUCT_REQUIREMENTS.md
    ├── 02-SYSTEM_ARCHITECTURE.md
    └── 03-API_CONTRACT.md
```

## Setup Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 14+
- Node.js 18+ (optional, for frontend build)

### Backend Setup

1. **Navigate to backend directory:**
   ```bash
   cd backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=blog_platform
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

6. **Seed database (optional):**
   ```bash
   php artisan db:seed
   ```

7. **Start development server:**
   ```bash
   php artisan serve
   ```
   
   The API will be available at `http://localhost:8000`

### Frontend Setup

The frontend uses static files and can be served directly. For development:

1. **Using PHP built-in server:**
   ```bash
   cd frontend
   php -S localhost:3000
   ```

2. **Or use any static file server:**
   - VS Code Live Server extension
   - Python: `python -m http.server 3000`
   - Node.js: `npx serve`

### Default Credentials (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@blog.com | password123 |
| User | user1@blog.com | password123 |

## API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout (authenticated)
- `GET /api/v1/auth/me` - Get current user (authenticated)
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password

### Posts
- `GET /api/v1/posts` - List all posts
- `GET /api/v1/posts/{slug}` - Get single post
- `POST /api/v1/posts` - Create post (authenticated)
- `PUT /api/v1/posts/{id}` - Update post (authenticated)
- `DELETE /api/v1/posts/{id}` - Delete post (authenticated)

### Categories
- `GET /api/v1/categories` - List categories
- `GET /api/v1/categories/{slug}/posts` - Get posts by category

### Tags
- `GET /api/v1/tags` - List tags
- `GET /api/v1/tags/{slug}/posts` - Get posts by tag

### Comments
- `GET /api/v1/posts/{id}/comments` - Get post comments
- `POST /api/v1/posts/{id}/comments` - Add comment (authenticated)
- `PUT /api/v1/comments/{id}` - Update comment (authenticated)
- `DELETE /api/v1/comments/{id}` - Delete comment (authenticated)

### Likes & Bookmarks
- `POST /api/v1/posts/{id}/like` - Toggle like (authenticated)
- `POST /api/v1/posts/{id}/bookmark` - Toggle bookmark (authenticated)
- `GET /api/v1/user/bookmarks` - Get bookmarks (authenticated)

### Search
- `GET /api/v1/search?q=query` - Search posts
- `GET /api/v1/search/suggest?q=query` - Get suggestions

### Admin (requires admin role)
- `GET /api/v1/admin/dashboard` - Dashboard stats
- `GET /api/v1/admin/users` - List users
- `PUT /api/v1/admin/users/{id}` - Update user
- `DELETE /api/v1/admin/users/{id}` - Delete user
- `GET /api/v1/admin/posts` - List all posts
- `GET /api/v1/admin/comments/pending` - Pending comments
- `POST /api/v1/admin/comments/{id}/approve` - Approve comment
- `POST /api/v1/admin/comments/{id}/reject` - Reject comment

## Security Features

- ✅ Password hashing with bcrypt
- ✅ CSRF protection
- ✅ Rate limiting on API endpoints
- ✅ Input validation on all endpoints
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS prevention (output escaping)
- ✅ Authentication middleware
- ✅ Authorization policies
- ✅ Secure headers

## Responsive Design

The frontend is fully responsive with breakpoints:
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

## Development

### Running Tests (Backend)
```bash
cd backend
php artisan test
```

### Code Style
```bash
cd backend
./vendor/bin/pint  # Format code
```

## Deployment

### Server Requirements
- PHP 8.2+
- MySQL 8.0+ / PostgreSQL 14+
- Web server (Nginx/Apache)
- SSL certificate (for production)

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up SSL/HTTPS
- [ ] Configure caching (Redis recommended)
- [ ] Set up queue worker for jobs
- [ ] Configure log rotation
- [ ] Set up monitoring

## Documentation

Detailed documentation is available in the `docs/` folder:
- [Product Requirements](docs/01-PRODUCT_REQUIREMENTS.md)
- [System Architecture](docs/02-SYSTEM_ARCHITECTURE.md)
- [API Contract](docs/03-API_CONTRACT.md)

## License

This project is open-source and available under the MIT License.

## Support

For issues and questions, please create an issue in the repository.

---

**Version:** 1.0.0  
**Last Updated:** 2026-04-01
# BlogV1
