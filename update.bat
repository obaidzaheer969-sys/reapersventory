@echo off
setlocal
cd /d "%~dp0"

echo ==> Pulling latest from origin/main
git pull origin main
if errorlevel 1 exit /b 1

echo ==> Building frontend assets
call npm.cmd ci
if errorlevel 1 exit /b 1
call npm.cmd run build
if errorlevel 1 exit /b 1

echo ==> Running migrations
php artisan migrate --force
if errorlevel 1 exit /b 1

echo ==> Clearing Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
if errorlevel 1 exit /b 1

echo ==> Verifying any stuck unverified users
php artisan reapersventory:verify-all-users
if errorlevel 1 exit /b 1

echo ==> Done. App is updated.
