#!/usr/bin/env bash
# cPanel → Git™ Version Control → Manage → Deployment Script
# Paste: bash deploy.sh
# (Runs automatically AFTER hosting pulls the latest git commit.)

set -euo pipefail

cd "$(dirname "$0")"

PHP_BIN="${PHP_BIN:-php}"

echo "[deploy] $(date -u '+%Y-%m-%dT%H:%M:%SZ') starting in $(pwd)"

if [ -f artisan ]; then
  "$PHP_BIN" artisan migrate --force || true
  "$PHP_BIN" artisan config:clear
  "$PHP_BIN" artisan cache:clear
  "$PHP_BIN" artisan route:clear
  "$PHP_BIN" artisan view:clear
  "$PHP_BIN" artisan reapersventory:verify-all-users || true
  echo "[deploy] artisan tasks finished"
else
  echo "[deploy] artisan not found — skipped Laravel commands"
fi

echo "[deploy] done"
