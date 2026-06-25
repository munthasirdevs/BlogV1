# Deployment Guide

## Requirements
- PHP 8.2+
- Composer 2.x
- MySQL 8.0+
- Redis 7+ (optional but recommended)
- Node.js 20+ (for building assets)

## Steps
1. Clone repository
2. Install dependencies: `composer install --no-dev`
3. Install frontend: `npm install && npm run build`
4. Copy .env: `cp .env.example .env`
5. Generate key: `php artisan key:generate`
6. Create database and configure .env
7. Run migrations: `php artisan migrate --force`
8. Seed roles: `php artisan db:seed --class=RolePermissionSeeder --force`
9. Storage link: `php artisan storage:link`
10. Cache: `php artisan route:cache && php artisan config:cache && php artisan view:cache`
11. Queue worker: `php artisan queue:work --daemon`
12. Scheduler: Add `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` to crontab

## Nginx Config

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/blogv1/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|avif|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location /storage {
        try_files $uri $uri/ =404;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 256;
}

server {
    listen 443 ssl http2;
    server_name example.com;
    root /var/www/blogv1/public;

    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers on;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    include /etc/nginx/ssl-common.conf;
}
```

## Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
FILESYSTEM_DISK=s3

LOG_LEVEL=warning
```

## Post-Deployment

- Verify site loads over HTTPS
- Check `/health` endpoint returns 200
- Monitor `storage/logs/` for errors
- Verify queue worker is processing jobs
- Confirm scheduler is running
- Test SSL certificate renewal
- Configure backup system
