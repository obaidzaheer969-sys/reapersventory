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
        $line = trim($line, " \t\n\r\0\x0B");

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\0\x0B");

        $quoted = (str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (str_starts_with($v, "'") && str_ends_with($v, "'"));

        if ($quoted) {
            $v = substr($v, 1, -1);
        } elseif (($hash = strpos($v, ' #')) !== false) {
            $v = rtrim(substr($v, 0, $hash));
        }

        $vars[trim($k, " \t\n\r\0\x0B")] = $v;
    }

    return $vars;
}

function rv_find_php(?string $preferredBinary = null): ?string
{
    $candidates = [
        $preferredBinary,
        '/opt/cpanel/ea-php85/root/usr/bin/php',
        '/opt/cpanel/ea-php84/root/usr/bin/php',
        '/opt/alt/php85/usr/bin/php',
        '/opt/alt/php84/usr/bin/php',
        '/usr/local/bin/ea-php85',
        '/usr/bin/ea-php85',
        '/usr/local/bin/ea-php84',
        '/usr/bin/ea-php84',
        'ea-php85',
        'ea-php84',
        '/usr/local/bin/php8.5',
        '/usr/bin/php8.5',
        'php8.5',
        '/usr/local/bin/php85',
        '/usr/bin/php85',
        'php85',
        '/usr/local/bin/php8.4',
        '/usr/bin/php8.4',
        'php8.4',
        '/usr/local/bin/php84',
        '/usr/bin/php84',
        'php84',
        '/usr/local/bin/php',
        '/usr/bin/php',
        'php',
    ];

    if (
        defined('PHP_BINARY') &&
        PHP_BINARY &&
        ! str_contains(strtolower(PHP_BINARY), 'fpm') &&
        ! str_contains(strtolower(PHP_BINARY), 'cgi')
    ) {
        array_unshift($candidates, PHP_BINARY);
    }

    foreach (array_unique(array_filter($candidates)) as $bin) {
        $out = [];
        $code = 0;
        exec(escapeshellarg($bin).' -r '.escapeshellarg('echo PHP_VERSION;').' 2>&1', $out, $code);
        $version = trim(implode("\n", $out));

        if (
            $code === 0 &&
            preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1 &&
            version_compare($matches[1], '8.4.1', '>=')
        ) {
            return $bin;
        }
    }

    return null;
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
$expected = trim((string) ($env['DEPLOY_SECRET'] ?? ''));

if ($expected === '') {
    $expected = trim((string) (getenv('DEPLOY_SECRET') ?: ($_SERVER['DEPLOY_SECRET'] ?? ($_ENV['DEPLOY_SECRET'] ?? ''))));
}

$secret = trim((string) ($_GET['secret'] ?? $_POST['secret'] ?? ''));
$mode = (string) ($_POST['mode'] ?? $_GET['mode'] ?? 'full-update');

if ($base === null || ! is_file($base.DIRECTORY_SEPARATOR.'artisan')) {
    http_response_code(500);
    echo "Could not find artisan / project root.\n";
    exit;
}

if ($secret === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (strlen($expected) < 32) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Deployment secret required</title>';
        echo '<body style="font:16px/1.6 system-ui,sans-serif;max-width:560px;margin:4rem auto;padding:0 1rem">';
        echo '<h1>Set a deployment secret first</h1>';
        echo '<p>Set <code>DEPLOY_SECRET</code> to a new random value of at least 32 characters in the project <code>.env</code> file (the folder that contains <code>artisan</code>), then reload this page.</p>';
        echo '<p>Generate one locally, for example:</p>';
        echo '<pre style="background:#f3f4f6;padding:1rem;border-radius:8px;overflow:auto">php -r "echo bin2hex(random_bytes(24));"</pre>';
        echo '<p>After saving the file, open <code>/rv-update.php</code> again and submit that exact secret.</p>';
        echo '</body></html>';
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Run pending migrations</title><body style="font:16px sans-serif;max-width:480px;margin:4rem auto;padding:0 1rem">';
    echo '<h1>Run pending migrations</h1><p>This runs Laravel pending migrations only. It does not reset or erase the database.</p>';
    echo '<form method="post"><input type="hidden" name="mode" value="migrate-only">';
    echo '<label for="secret">Deployment secret</label><br>';
    echo '<input id="secret" name="secret" type="password" required autocomplete="off" style="box-sizing:border-box;width:100%;margin:0.5rem 0 1rem;padding:0.75rem">';
    echo '<button type="submit" style="padding:0.75rem 1rem">Run migrations</button></form>';
    echo '<p style="margin-top:1.5rem;font-size:14px;color:#4b5563">Tip: you can also call <code>/rv-update.php?secret=YOUR_SECRET&amp;mode=migrate-only</code> directly.</p>';
    echo '</body></html>';
    exit;
}

if (! in_array($mode, ['migrate-only', 'full-update', 'logs'], true)) {
    http_response_code(400);
    echo "Invalid update mode.\n";
    exit;
}

if (strlen($expected) < 32 || ! rv_secrets_match($expected, $secret)) {
    http_response_code(403);
    echo "Forbidden. Check that DEPLOY_SECRET is configured and matches.\n";
    exit;
}

chdir($base);

if ($mode === 'logs') {
    $logDirectory = $base.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'logs';
    $logFiles = glob($logDirectory.DIRECTORY_SEPARATOR.'laravel*.log') ?: [];

    usort($logFiles, static function (string $left, string $right): int {
        return (int) filemtime($right) <=> (int) filemtime($left);
    });

    if ($logFiles === []) {
        echo "No Laravel log files found.\n";
        exit;
    }

    foreach (array_slice($logFiles, 0, 2) as $logFile) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];

        echo '[rv-logs] '.basename($logFile)."\n";
        echo implode("\n", array_slice($lines, -200))."\n";
    }

    exit;
}

if (! function_exists('exec')) {
    http_response_code(500);
    echo "The PHP exec() function is disabled on this hosting account, so the updater cannot run Artisan.\n";
    echo "Ask the host to enable exec(), or run this from a terminal instead: php artisan migrate --force\n";
    exit;
}

$log = [];
$log[] = '[rv-update] started '.gmdate('c');
$log[] = '[rv-update] base '.$base;

// Clear stale caches FIRST so site does not 500 on missing DeployController etc.
if ($mode === 'full-update') {
    rv_clear_bootstrap_cache($base, $log);
}

$php = rv_find_php((string) ($env['PHP_BIN'] ?? ''));
if ($php === null) {
    http_response_code(500);
    echo "PHP CLI 8.4.1 or later was not found. Set PHP_BIN in the project .env to the hosting PHP CLI binary.\n";
    exit;
}

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

$fetchCode = $run('git fetch origin main');
if ($fetchCode !== 0) {
    $log[] = '[rv-update] git fetch failed; update aborted';
    http_response_code(500);
    echo implode("\n", $log)."\n";
    exit;
}

if ($run('git reset --hard origin/main') !== 0) {
    $log[] = '[rv-update] git reset failed; update aborted';
    http_response_code(500);
    echo implode("\n", $log)."\n";
    exit;
}

$run('git clean -fd -e .env -e .env.* -e storage -e vendor -e node_modules -e public/uploads -e public/rv-update.php');

// Clear again after pull (new commit may still leave old cache files)
rv_clear_bootstrap_cache($base, $log);

// Migrate BEFORE the asset build: the database fix must not depend on npm.
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

// Rebuild Vite assets so production loads the scanner module from the manifest.
$npm = rv_find_npm();
if ($npm === null) {
    $log[] = '[rv-update] npm not found — skipped asset build; code and database are already updated';
} else {
    $npmQ = escapeshellarg($npm);
    if ($run($npmQ.' ci') !== 0 || $run($npmQ.' run build') !== 0) {
        $log[] = '[rv-update] asset build failed — code and database are already updated';
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
