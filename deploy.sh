#!/usr/bin/env bash
# cPanel → Git™ Version Control → Manage → Deployment Script
# Paste: bash deploy.sh
# (Runs automatically AFTER hosting pulls the latest git commit.)
#
# Migrations run FIRST and the asset build is non-fatal, so a Node/npm problem
# can never leave the site on new code with an old database (which causes 500s).

set -uo pipefail

cd "$(dirname "$0")"

PHP_BIN="${PHP_BIN:-php}"

echo "[deploy] $(date -u '+%Y-%m-%dT%H:%M:%SZ') starting in $(pwd)"

if [ -f artisan ]; then
  echo "[deploy] running database migrations"
  if ! "$PHP_BIN" artisan migrate --force; then
    echo "[deploy] migration failed — aborting so the database is not left half-updated"
    exit 1
  fi

  "$PHP_BIN" artisan db:seed --class=PlanSeeder --force || true
  "$PHP_BIN" artisan reapersventory:ensure-notifications || true
  "$PHP_BIN" artisan reapersventory:ensure-pending-plan || true
  "$PHP_BIN" artisan inventory:notify-low-stock || true
  "$PHP_BIN" artisan reapersventory:verify-all-users || true
  echo "[deploy] artisan tasks finished"
else
  echo "[deploy] artisan not found — skipped Laravel commands"
fi

if command -v npm >/dev/null 2>&1; then
  echo "[deploy] installing frontend dependencies and building assets"
  if ! npm ci || ! npm run build; then
    echo "[deploy] asset build failed — code and database are already updated; run 'npm ci && npm run build' manually"
  fi
else
  echo "[deploy] npm not found — skipping asset build (code and database are already updated)"
fi

if [ -f artisan ]; then
  "$PHP_BIN" artisan config:clear || true
  "$PHP_BIN" artisan cache:clear || true
  "$PHP_BIN" artisan route:clear || true
  "$PHP_BIN" artisan view:clear || true
fi

echo "[deploy] done"
