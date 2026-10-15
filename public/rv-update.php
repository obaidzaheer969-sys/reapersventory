<?php

/**
 * One-click hosting updater (reads DEPLOY_SECRET from project .env).
 *
 * https://reapersventory.com/rv-update.php?secret=YOUR_DEPLOY_SECRET
 */

declare(strict_types=1);

set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=UTF-8');

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    echo "rv-update EXCEPTION\n";
    echo $e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function rv_project_root(): ?string
{
    $candidates = [
        dirname(__DIR__),
        __DIR__,
        dirname(dirname(__DIR__)),
    ];

    foreach ($candidates as $path) {
        if (is_file($path.DIRECTORY_SEPARATOR.'artisan') && is_file($path.DIRECTORY_SEPARATOR.'.env')) {
            return $path;
        }
    }

    foreach ($candidates as $path) {
        if (is_file($path.DIRECTORY_SEPARATOR.'artisan')) {
            return $path;
        }
    }

    return null;
}

function rv_load_env(string $base): array
{
    $vars = [];
    $envPath = $base.DIRECTORY_SEPARATOR.'.env';

    if (! is_file($envPath)) {
        return $vars;
    }

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);

        if (
            (str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            $v = substr($v, 1, -1);
        }

        $vars[trim($k)] = $v;
    }

    return $vars;
}

function rv_find_php(): string
{
    $candidates = [
        '/usr/local/bin/php',
        '/usr/bin/php',
        'php',
        'php8.5',
        'php85',
        'php8.4',
        'php8.3',
    ];

    if (defined('PHP_BINARY') && PHP_BINARY && ! str_contains(strtolower(PHP_BINARY), 'fpm')) {
        array_unshift($candidates, PHP_BINARY);
    }

    foreach ($candidates as $bin) {
        $out = [];
        $code = 0;
        exec(escapeshellarg($bin).' -v 2>&1', $out, $code);

        if ($code === 0 && isset($out[0]) && str_contains(strtolower($out[0]), 'php')) {
            return $bin;
        }
    }

    return 'php';
}

function rv_secrets_match(string $expected, string $provided): bool
{
    if ($expected === '' || $provided === '') {
        return false;
    }

    if (strlen($expected) !== strlen($provided)) {
        return false;
    }

    return hash_equals($expected, $provided);
}

/** Clear Laravel bootstrap caches without booting the app (fixes 500 after deleted routes/controllers). */
function rv_clear_bootstrap_cache(string $base, array &$log): void
{
    $dir = $base.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache';

    if (! is_dir($dir)) {
        $log[] = '[rv-update] bootstrap/cache missing';

        return;
    }

    foreach (glob($dir.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
        if (@unlink($file)) {
            $log[] = '[rv-update] deleted '.basename($file);
        }
    }
}

$base = rv_project_root();
$envPath = $base ? $base.DIRECTORY_SEPARATOR.'.env' : null;
$env = $base ? rv_load_env($base) : [];
$expected = (string) ($env['DEPLOY_SECRET'] ?? '');
$secret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');

if (! rv_secrets_match($expected, $secret)) {
    http_response_code(403);
    echo "Forbidden\n\n";
    echo "Hosting .env mein DEPLOY_SECRET set nahi hai, ya URL ka secret match nahi karta.\n\n";
    echo "Fix (cPanel → File Manager):\n";
    echo "1) Laravel project root mein .env file kholo (artisan ke saath wali folder)\n";
    echo "2) Ye line add/update karo:\n";
    echo "   DEPLOY_SECRET=mera-secret-123-change-me\n";
    echo "3) Save karo, phir dubara ye URL kholo:\n";
    echo "   https://reapersventory.com/rv-update.php?secret=mera-secret-123-change-me\n\n";
    echo "Diagnostics:\n";
    echo '- project root: '.($base ?? 'NOT FOUND')."\n";
    echo '- .env path: '.($envPath ?? 'n/a')."\n";
    echo '- .env readable: '.(($envPath && is_file($envPath)) ? 'yes' : 'no')."\n";
    echo '- DEPLOY_SECRET in .env: '.($expected !== '' ? 'yes' : 'NO — add it')."\n";
    echo '- secret in URL: '.($secret !== '' ? 'yes' : 'NO — add ?secret=...')."\n";
    exit;
}

if ($base === null || ! is_file($base.DIRECTORY_SEPARATOR.'artisan')) {
    http_response_code(500);
    echo "Could not find artisan / project root.\n";
    exit;
}

chdir($base);

$log = [];
$log[] = '[rv-update] started '.gmdate('c');
$log[] = '[rv-update] base '.$base;

// Clear stale caches FIRST so site does not 500 on missing DeployController etc.
rv_clear_bootstrap_cache($base, $log);

$php = rv_find_php();
$log[] = '[rv-update] php '.$php;

$run = static function (string $cmd) use (&$log): int {
    $output = [];
    $code = 0;
    exec($cmd.' 2>&1', $output, $code);
    $log[] = '[rv-update] $ '.$cmd.' (exit '.$code.')';

    foreach ($output as $line) {
        $log[] = '  '.$line;
    }

    return $code;
};

$run('git fetch origin main');
$run('git reset --hard origin/main');
$run('git clean -fd -e .env -e .env.* -e storage -e vendor -e node_modules -e public/uploads -e public/rv-update.php');

// Clear again after pull (new commit may still leave old cache files)
rv_clear_bootstrap_cache($base, $log);

$phpQ = escapeshellarg($php);
$migrateCode = $run($phpQ.' artisan migrate --force');
if ($migrateCode !== 0) {
    $log[] = '[rv-update] migrate failed — retrying once';
    $run($phpQ.' artisan migrate --force');
}
$run($phpQ.' artisan optimize:clear');
$run($phpQ.' artisan db:seed --class=PlanSeeder --force');
$run($phpQ.' artisan reapersventory:ensure-notifications');
$run($phpQ.' artisan reapersventory:ensure-pending-plan');
$run($phpQ.' artisan inventory:notify-low-stock');
$run($phpQ.' artisan storage:link');

$uploads = $base.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads';
if (! is_dir($uploads)) {
    mkdir($uploads, 0755, true);
    $log[] = '[rv-update] created public/uploads';
}

$run($phpQ.' artisan reapersventory:verify-all-users');

$log[] = '[rv-update] finished '.gmdate('c');
$log[] = '[rv-update] Site should load now. Open https://reapersventory.com/ then Dashboard or Alerts.';
echo implode("\n", $log)."\n";
