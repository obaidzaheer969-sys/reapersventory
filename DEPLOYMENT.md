# ReapersVentory deployment

Production checklist for Milestone 5 (notifications, hardening, scheduler/queue).

## Requirements

- PHP 8.3+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd` or `imagick`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- Composer 2
- MySQL 8+
- Redis optional (cache/queue); database queue works out of the box
- Process supervisor (Supervisor, systemd) for `queue:work`
- Cron for Laravel scheduler

## Environment

1. Copy `.env.example` → `.env` and set:

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain` |
| `APP_KEY` | `php artisan key:generate` |
| `DB_CONNECTION` | `mysql` |
| `DB_*` | MySQL credentials |
| `QUEUE_CONNECTION` | `database` (or `redis`) |
| `CACHE_STORE` | `database` (or `redis`) |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `MAIL_*` | Real SMTP / API mailer |
| `PAYMENT_PROOF_DISK` | `local` (private) or encrypted S3 |
| `SUPER_ADMIN_*` | Change seeded admin password immediately |

2. HTTPS is forced when `APP_ENV=production`. Sit behind a reverse proxy that terminates TLS; proxies are trusted (`TrustProxies`).

## Deploy steps

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate          # first deploy only
php artisan migrate --force
php artisan db:seed --force       # first deploy / controlled reseeds only
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link          # public disk only; proofs stay private
php artisan reapersventory:health
```

Do **not** symlink or expose `storage/app/private`. Payment proofs download only via authenticated Super Admin route.

## Scheduler (cron)

```cron
* * * * * cd /path/to/reapersventory && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs:

| Command | Cadence | Purpose |
|---------|---------|---------|
| `subscriptions:expire` | hourly | Trial/active → grace → expired + emails |
| `subscriptions:notify-expiring` | daily 09:00 | Warning before `ends_at` |
| `inventory:notify-low-stock` | daily 08:00 | Reorder-level alerts |

## Queue worker

Notifications implement `ShouldQueue`. Run at least one worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Example Supervisor program:

```ini
[program:reapersventory-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/reapersventory/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/reapersventory-worker.log
stopwaitsecs=3600
```

## Health checks

- HTTP: `GET /up` (Laravel built-in)
- CLI: `php artisan reapersventory:health` (DB + private storage write + APP_KEY)

## Security summary

- Security headers on every response (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS in production)
- Auth endpoints rate-limited (`login`, `register`, `password-reset`)
- Tenant isolation via scopes + policies; Super Admin panel gated by Filament `canAccessPanel`
- Private payment proofs on non-served `local` disk
- Public registration never creates Super Admin
