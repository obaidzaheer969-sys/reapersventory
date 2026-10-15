<?php

/**
 * Emergency hosting updater.
 * https://reapersventory.com/rv-update.php?secret=YOUR_SECRET
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

function rv_env(string $key, string $default = ''): string
{
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        foreach ([dirname(__DIR__).'/.env', __DIR__.'/.env'] as $envPath) {
            if (! is_file($envPath)) {
                continue;
            }
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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
            break;
        }
    }

    return $vars[$key] ?? $default;
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

    // PHP_BINARY during a web request is often php-fpm — skip that.
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

$secret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');
$expected = rv_env('DEPLOY_SECRET');

if ($expected === '' || ! hash_equals($expected, $secret)) {
    http_response_code(403);
    echo "Forbidden\nAdd DEPLOY_SECRET to .env then retry.\n";
    exit;
}

$candidates = [dirname(__DIR__), __DIR__];
$base = null;
foreach ($candidates as $path) {
    if (is_file($path.'/artisan')) {
        $base = $path;
        break;
    }
}

if ($base === null) {
    http_response_code(500);
    echo "Could not find artisan.\n";
    exit;
}

chdir($base);
$log = [];
$log[] = '[rv-update] started '.gmdate('c');
$log[] = '[rv-update] base '.$base;

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

// Force match GitHub main (keeps .env / storage / vendor)
$run('git fetch origin main');
$run('git reset --hard origin/main');
$run('git clean -fd -e .env -e .env.* -e storage -e vendor -e node_modules -e public/rv-update.php');

$phpQ = escapeshellarg($php);
$run($phpQ.' artisan migrate --force');
$run($phpQ.' artisan storage:link');
$uploads = $base.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads';
if (! is_dir($uploads)) {
    mkdir($uploads, 0755, true);
    $log[] = '[rv-update] created public/uploads';
}
$run($phpQ.' artisan config:clear');
$run($phpQ.' artisan cache:clear');
$run($phpQ.' artisan route:clear');
$run($phpQ.' artisan view:clear');
$run($phpQ.' artisan reapersventory:verify-all-users');

$log[] = '[rv-update] finished '.gmdate('c');
$log[] = '[rv-update] Register should now go to dashboard (no email verify).';
echo implode("\n", $log)."\n";
