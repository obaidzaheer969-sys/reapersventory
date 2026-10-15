<?php

/**
 * Emergency hosting updater (works even when Laravel route:cache is stale).
 *
 * 1) Upload this file into your site web root (usually public_html/) via cPanel File Manager
 *    — same folder as index.php
 * 2) Put in .env: DEPLOY_SECRET=mera-secret-123-change-me
 * 3) Open:
 *    https://reapersventory.com/rv-update.php?secret=mera-secret-123-change-me
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

function rv_env(string $key, string $default = ''): string
{
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $envPath = dirname(__DIR__).'/.env';
        if (! is_file($envPath)) {
            // Some hosts put public_html = project root
            $envPath = __DIR__.'/.env';
        }
        if (is_file($envPath)) {
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
        }
    }

    return $vars[$key] ?? $default;
}

$secret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');
$expected = rv_env('DEPLOY_SECRET');

if ($expected === '' || ! hash_equals($expected, $secret)) {
    http_response_code(403);
    echo "Forbidden\n";
    echo "Add to .env: DEPLOY_SECRET=mera-secret-123-change-me\n";
    echo "Then open: https://reapersventory.com/rv-update.php?secret=mera-secret-123-change-me\n";
    exit;
}

$candidates = [
    dirname(__DIR__), // normal Laravel: public/../
    __DIR__,          // public_html is project root
];

$base = null;
foreach ($candidates as $path) {
    if (is_file($path.'/artisan')) {
        $base = $path;
        break;
    }
}

if ($base === null) {
    http_response_code(500);
    echo "Could not find artisan. Upload Laravel project correctly.\n";
    exit;
}

chdir($base);
$log = [];
$log[] = '[rv-update] started '.gmdate('c');
$log[] = '[rv-update] base '.$base;

$php = PHP_BINARY ?: 'php';

$run = static function (string $cmd) use (&$log): void {
    $output = [];
    $code = 0;
    exec($cmd.' 2>&1', $output, $code);
    $log[] = '[rv-update] $ '.$cmd.' (exit '.$code.')';
    foreach ($output as $line) {
        $log[] = '  '.$line;
    }
};

$run('git pull origin main');
$run(escapeshellarg($php).' artisan config:clear');
$run(escapeshellarg($php).' artisan cache:clear');
$run(escapeshellarg($php).' artisan route:clear');
$run(escapeshellarg($php).' artisan view:clear');
$run(escapeshellarg($php).' artisan reapersventory:verify-all-users');

$log[] = '[rv-update] finished '.gmdate('c');
echo implode("\n", $log)."\n";
