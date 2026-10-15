# ReapersVentory

Multi-tenant Laravel SaaS for product management, inventory, sales billing, and monthly subscriptions.

**Brand:** ReapersVentory (navy `#0a1628` + orange `#f15a24`)  
**Stack:** Laravel 13 · PHP 8.3+ · MySQL · Blade · Livewire · Tailwind · Filament (Milestone 2)

## Current status

| Milestone | Scope | Status |
|-----------|--------|--------|
| **1** | Auth, businesses, DB foundation, tenant isolation | **Implemented** |
| **2** | Plans, subscriptions, payments, Filament Super Admin | **Implemented** |
| **3** | Products, inventory, suppliers, purchases | **Implemented** |
| **4** | Sales, invoices, payments, reports | **Implemented** |
| **5** | Notifications, hardening, deployment | **Implemented** |

## Milestone 1 setup

### Requirements

- PHP 8.3+ (tested with PHP 8.5)
- Composer 2
- SQLite (local/tests) or MySQL (production)
- Node/npm optional for now (Tailwind CDN used until Vite assets are built)

### Environment variables

Copy `.env.example` to `.env` (already done on scaffold) and set:

| Variable | Purpose |
|----------|---------|
| `APP_NAME` | `ReapersVentory` |
| `APP_URL` | App URL |
| `DB_CONNECTION` | `sqlite` or `mysql` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | MySQL settings |
| `MAIL_*` | Mailer for verification & password reset |
| `QUEUE_CONNECTION` | `database` (recommended) |
| `FILESYSTEM_DISK` | `local` (private payment proofs in later milestones) |
| `SUPER_ADMIN_EMAIL` | Seeded Super Admin email |
| `SUPER_ADMIN_PASSWORD` | Seeded Super Admin password |
| `SUPER_ADMIN_NAME` | Seeded Super Admin display name |

### Commands

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Run tests:

```bash
php artisan test
```

### Default Super Admin (after seeding)

- Email: `admin@reapersventory.test` (or `SUPER_ADMIN_EMAIL`)
- Password: value of `SUPER_ADMIN_PASSWORD`

Public registration **never** creates Super Admin users.

## Milestone 5 highlights

- In-app + mail notification system: welcome, payment submitted/approved/rejected, subscription status, low stock, sales, purchases
- Business **Alerts** page + unread badge; Filament admin database notifications
- Scheduler: `subscriptions:expire` (hourly), `subscriptions:notify-expiring` (daily), `inventory:notify-low-stock` (daily)
- Security headers middleware, auth rate limits, HTTPS forced in production, private disk `serve => false`
- `DEPLOYMENT.md` + `php artisan reapersventory:health` + HTTP `/up`

See [DEPLOYMENT.md](DEPLOYMENT.md) for production cron, queue worker, and env checklist.

### Extra env vars (M5)

| Variable | Purpose |
|----------|---------|
| `SUBSCRIPTION_EXPIRING_SOON_DAYS` | Days before `ends_at` to warn (default 3) |
| `SESSION_SECURE_COOKIE` | `true` in production HTTPS |

## Milestone 4 highlights

- Customers, sales, sale items with historical unit cost/price snapshots
- Invoices (unique per business), partial `sales_payments`, PDF invoice/receipt via DomPDF
- Returns restore stock transactionally; expenses + financial reports (sales, COGS, profit)
- Monthly invoice plan limits enforced server-side; sales routes require `subscribed`

## Milestone 3 highlights

- Categories, products (SKU/barcode unique per business), warehouses (plan-limited)
- Inventory balances + immutable stock movements; purchase receive updates stock transactionally
- Suppliers and purchases with historical line totals that stay stable after price changes
- Low-stock alerts on dashboard/inventory; manual stock adjustments
- Inventory routes protected by `subscribed` + policies + product/warehouse plan limits

## Milestone 2 highlights

- Filament Super Admin at `/admin` (Super Admin only via `canAccessPanel`)
- Plans + feature limits, payment methods (Bank / Easypaisa / JazzCash)
- Owner subscription page: choose plan, submit private payment proofs
- Proof upload **does not** activate access; Super Admin approve/reject is transactional + idempotent
- Renewal blocked from duplicating an approved billing period
- `subscribed` middleware + `PlanLimitService` for server-side limit checks
- Scheduler: `subscriptions:expire` hourly

### Extra env vars

| Variable | Purpose |
|----------|---------|
| `SUBSCRIPTION_GRACE_PERIOD_DAYS` | Grace days after period end (default 7) |
| `PAYMENT_PROOF_DISK` | Private disk for proofs (default `local`) |
| `PAYMENT_PROOF_MAX_KB` | Max upload size in KB |

**Note:** Filament requires PHP `ext-intl`. This environment installed Filament with `--ignore-platform-req=ext-intl`; enable `intl` for production.

## Security notes

- Roles enforced via `UserRole` enum + middleware (`business.owner`, `super.admin`, `active`, `subscribed`)
- Tenant isolation via `business_id`, policies, and `BelongsToBusiness` / `BusinessScope`
- Registration strips injected `role` / `business_id` and always assigns `business_owner`
- Email verification required before business dashboard/profile access
- Payment proof files stored on the private `local` disk under `storage/app/private`
