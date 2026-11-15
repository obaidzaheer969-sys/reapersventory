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

function rv_find_npm(): ?string
{
    $candidates = [
        '/usr/local/bin/npm',
        '/usr/bin/npm',
        'npm',
    ];

    foreach ($candidates as $bin) {
        $out = [];
        $code = 0;
        exec(escapeshellarg($bin).' --version 2>&1', $out, $code);

        if ($code === 0 && isset($out[0])) {
            return $bin;
        }
    }

    return null;
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
$env = $base ? rv_load_env($base) : [];
$expected = (string) ($env['DEPLOY_SECRET'] ?? '');
$secret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');
$mode = (string) ($_POST['mode'] ?? 'full-update');

if ($base === null || ! is_file($base.DIRECTORY_SEPARATOR.'artisan')) {
    http_response_code(500);
    echo "Could not find artisan / project root.\n";
    exit;
}

if ($secret === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if ($expected === '') {
        http_response_code(503);
        echo "DEPLOY_SECRET is not configured in the project .env.\n";
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Run pending migrations</title><body style="font:16px sans-serif;max-width:480px;margin:4rem auto;padding:0 1rem">';
    echo '<h1>Run pending migrations</h1><p>This runs Laravel pending migrations only. It does not reset or erase the database.</p>';
    echo '<form method="post"><input type="hidden" name="mode" value="migrate-only">';
    echo '<label for="secret">Deployment secret</label><br>';
    echo '<input id="secret" name="secret" type="password" required autocomplete="off" style="box-sizing:border-box;width:100%;margin:0.5rem 0 1rem;padding:0.75rem">';
    echo '<button type="submit" style="padding:0.75rem 1rem">Run migrations</button></form></body></html>';
    exit;
}

if (! in_array($mode, ['migrate-only', 'full-update'], true)) {
    http_response_code(400);
    echo "Invalid update mode.\n";
    exit;
}

if ($expected === '' || ! rv_secrets_match($expected, $secret)) {
    http_response_code(403);
    echo "Forbidden. Check that DEPLOY_SECRET is configured and matches.\n";
    exit;
}

chdir($base);

$log = [];
$log[] = '[rv-update] started '.gmdate('c');
$log[] = '[rv-update] base '.$base;

// Clear stale caches FIRST so site does not 500 on missing DeployController etc.
if ($mode === 'full-update') {
    rv_clear_bootstrap_cache($base, $log);
}

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

if ($mode === 'migrate-only') {
    $migrationCode = $run(escapeshellarg($php).' artisan migrate --force');

    if ($migrationCode !== 0) {
        http_response_code(500);
        echo implode("\n", $log)."\n";
        exit;
    }

    $log[] = '[rv-update] migrations completed successfully';
    echo implode("\n", $log)."\n";
    exit;
}

$run('git fetch origin main');
$run('git reset --hard origin/main');
$run('git clean -fd -e .env -e .env.* -e storage -e vendor -e node_modules -e public/uploads -e public/rv-update.php');

// Rebuild Vite assets so production loads the scanner module from the manifest.
$npm = rv_find_npm();
if ($npm === null) {
    http_response_code(500);
    echo "npm is required to build frontend assets. Enable Node.js/npm for the hosting account, then retry.\n";
    exit;
}

$npmQ = escapeshellarg($npm);
if ($run($npmQ.' ci') !== 0 || $run($npmQ.' run build') !== 0) {
    http_response_code(500);
    echo "Frontend asset build failed. Check the deployment log and Node.js version.\n";
    exit;
}

// Clear again after pull (new commit may still leave old cache files)
rv_clear_bootstrap_cache($base, $log);

$phpQ = escapeshellarg($php);
$migrateCode = $run($phpQ.' artisan migrate --force');
if ($migrateCode !== 0) {
    $log[] = '[rv-update] migrate failed — retrying once';
    if ($run($phpQ.' artisan migrate --force') !== 0) {
        $log[] = '[rv-update] migration failed; update aborted';
        http_response_code(500);
        echo implode("\n", $log)."\n";
        exit;
    }
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
