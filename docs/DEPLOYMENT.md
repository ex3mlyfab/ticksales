# TickSales POS — Deployment Guide

**Stack:** Laravel 13 (PHP ^8.3) · Inertia.js v3 + React 19 · Vite · SQLite/MySQL/PostgreSQL  
**Scope:** production deployment of the multi-tenant POS platform.

---

## 1. Prerequisites

- **PHP 8.3+** with extensions: `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`,
  `openssl`, `fileinfo`, `curl`, `zip`.
- **Node 20+ / npm** (frontend build only).
- **Composer 2**.
- A database: SQLite (default), MySQL 8+, or PostgreSQL 15+.
- A web server: nginx or Apache, or PHP 8.3+ built-in server for local smoke tests.
- A queue worker (database driver by default) if the app ever dispatches queued jobs.

---

## 2. Environment Configuration

Copy `.env.example` → `.env` and set the production values:

| Variable                                                              | Production value          | Notes                                  |
| --------------------------------------------------------------------- | ------------------------- | -------------------------------------- |
| `APP_ENV`                                                             | `production`              |                                        |
| `APP_DEBUG`                                                           | `false`                   | Must be false in production            |
| `APP_URL`                                                             | `https://pos.example.com` | Scheme + domain the app is served on   |
| `APP_KEY`                                                             | generated                 | `php artisan key:generate --force`     |
| `APP_LOCALE`                                                          | `en`                      |                                        |
| `DB_CONNECTION`                                                       | `mysql` (or `pgsql`)      | SQLite works for small deployments     |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | per host                  |                                        |
| `SESSION_DRIVER`                                                      | `database`                |                                        |
| `SESSION_DOMAIN`                                                      | `.example.com`            | Leading dot to share across subdomains |
| `CACHE_STORE`                                                         | `database` (or `redis`)   |                                        |
| `QUEUE_CONNECTION`                                                    | `database` (or `redis`)   |                                        |
| `MAIL_MAILER`                                                         | `smtp`                    | Transactional/notification email       |
| `LOG_CHANNEL`                                                         | `stack`                   |                                        |
| `LOG_LEVEL`                                                           | `error`                   | Keep production logs at error+         |
| `VITE_APP_NAME`                                                       | `TickSales`               |                                        |

> Never set `APP_DEBUG=true` in production.

---

## 3. Installation & Build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Database

```bash
php artisan migrate --force
php artisan db:seed --force            # roles, permissions, plans, admin user
php artisan db:seed --class=DemoSeeder --force   # optional: sample data
```

`DatabaseSeeder` creates the platform admin `test@example.com` (update immediately) and
`DemoSeeder` creates a full `demo-store` tenant with users `owner@ticksales.test`,
`manager@ticksales.test`, `cashier@ticksales.test` (all password `password`).

---

## 4. Post-Deployment Optimization

Run these after every deploy (or add to the deploy script):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

> Use `php artisan optimize:clear` during development to avoid stale cached config/routes.

---

## 5. Queue Worker (if using queued jobs)

```bash
php artisan queue:work --daemon --tries=3 --timeout=90
```

With systemd or a process manager (e.g. Supervisor):

```ini
[program:ticksales-worker]
command=php /var/www/ticksales/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=2
```

---

## 6. Web Server (nginx example)

```nginx
server {
    listen 443 ssl;
    server_name pos.example.com;
    root /var/www/ticksales/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets (Vite hashes filenames, so they are immutable)
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # PWA service worker must not be aggressively cached
    location = /sw.js {
        add_header Cache-Control "no-cache";
    }
}
```

---

## 7. TLS & Security

- Serve over **HTTPS** only. Add `--force` to `App\Providers\AppServiceProvider::boot()` if you
  rely on `APP_URL` redirects.
- Behind a load balancer, trust the proxy so `HTTPS` is detected (Laravel 11+ defaults handle
  this; configure `TrustProxies` if needed).
- Baseline headers are set by `App\Http\Middleware\SecurityHeaders` (nosniff, X-Frame-Options,
  Referrer-Policy, Permissions-Policy).
- **CSP is intentionally deferred** — the Inertia/Vite shell uses inline scripts for dark-mode
  detection and HMR. Enable a Content-Security-Policy only after adding nonces.

---

## 8. Storage & Files

```bash
php artisan storage:link
```

If using cloud storage for attachments (e.g. S3), set `FILESYSTEM_DISK=s3` and the
`AWS_*` variables. Attachments (expense files, product images) default to the `local` disk.

---

## 9. Health Check & Monitoring

- The app exposes `/up` (Laravel health route) — point uptime checks at it.
- Monitor `storage/logs/laravel.log` and the configured `LOG_CHANNEL`.
- Run `php artisan test --compact` in CI (the repo ships `composer test`).

---

## 10. Rollback

```bash
php artisan migrate:rollback --step=1 --force
php artisan optimize:clear
```

Frontend changes are versioned by hashed filenames, so roll back the deployed bundle to the
previous release (keep prior `public/build` snapshots).
