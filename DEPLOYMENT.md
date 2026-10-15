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
| `APP_URL` | `https://reapersventory.com` |
| `APP_KEY` | `php artisan key:generate` |
| `DB_CONNECTION` | `mysql` |
| `DB_*` | MySQL credentials |
| `QUEUE_CONNECTION` | `database` (or `redis`) |
| `CACHE_STORE` | `database` (or `redis`) |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `MAIL_*` | Real SMTP (see below) — **`log` does not send inbox mail** |
| `PAYMENT_PROOF_DISK` | `local` (private) or encrypted S3 |
| `SUPER_ADMIN_*` | Change seeded admin password immediately |

### Email verification on hosting

If the UI says “link sent” but nothing arrives, `.env` is usually still on `MAIL_MAILER=log` (local default).

1. Create an email mailbox in cPanel (e.g. `noreply@yourdomain.com`).
2. Set in hosting `.env`:

```env
APP_URL=https://yourdomain.com
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-mailbox-password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

3. Clear cached config:

```bash
php artisan config:clear
php artisan config:cache
```

4. Click **Resend verification email** again. Check spam folder.

If SMTP is blocked by the host, try `MAIL_MAILER=sendmail` instead.

2. HTTPS is forced when `APP_ENV=production`. Sit behind a reverse proxy that terminates TLS; proxies are trusted (`TrustProxies`).

## Auto deploy after git push (recommended)

### Option A — cPanel Git deploy script (best)

1. cPanel → **Git™ Version Control** → your repo → **Manage**
2. Turn on **Pull** / deploy from `main`
3. In **Deployment Script** paste:

```bash
bash deploy.sh
```

4. Save. After every deploy/pull, hosting will run `config:clear` + related artisan commands automatically.

### Option B — browser / GitHub webhook

1. In hosting `.env` set a secret:

```env
DEPLOY_SECRET=put-a-long-random-string-here
```

2. Then either open once after push:

`https://reapersventory.com/deploy?secret=put-a-long-random-string-here`

> If you see **404**, pull/deploy the latest `main` once from cPanel Git first (so the new `/deploy` route exists), then open the link again.

Or GitHub → repo → **Settings → Webhooks → Add webhook**
- Payload URL: same URL as above
- Content type: `application/json`
- Events: **Just the push event**

This runs `git pull origin main` + cache clears + verify stuck users.

### Manual update

```bash
chmod +x update.sh   # first time only
./update.sh
```

Windows locally: `update.bat`

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
