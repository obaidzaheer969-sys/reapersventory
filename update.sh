#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

echo "==> Pulling latest from origin/main"
git pull origin main

echo "==> Clearing Laravel caches"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "==> Done. App is updated."
