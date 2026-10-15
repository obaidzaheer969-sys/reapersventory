<?php

/**
 * One-click / webhook deploy for hosting.
 *
 * After git push, either:
 * 1) Open: https://reapersventory.com/deploy.php?secret=YOUR_DEPLOY_SECRET
 * 2) Or add a GitHub webhook (Push events) to that same URL.
 *
 * Set DEPLOY_SECRET in hosting .env (long random string).
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

$basePath = dirname(__DIR__);

require $basePath.'/vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require $basePath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=UTF-8');

$provided = (string) ($_GET['secret'] ?? $_POST['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '');
$expected = (string) config('reapersventory.deploy_secret', '');

if ($expected === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    echo "Forbidden. Set DEPLOY_SECRET in .env and pass ?secret=...\n";
    exit;
}

$log = [];
$log[] = '[deploy] started '.gmdate('c');
$log[] = '[deploy] path '.$basePath;

chdir($basePath);

$pull = (string) ($_GET['pull'] ?? $_POST['pull'] ?? '1');
if ($pull === '1') {
    $output = [];
    $code = 0;
    exec('git pull origin main 2>&1', $output, $code);
    $log[] = '[deploy] git pull exit='.$code;
    foreach ($output as $line) {
        $log[] = '  '.$line;
    }
}

$commands = [
    'config:clear',
    'cache:clear',
    'route:clear',
    'view:clear',
    'reapersventory:verify-all-users',
];

foreach ($commands as $command) {
    try {
        Artisan::call($command);
        $log[] = '[deploy] OK '.$command;
        $out = trim(Artisan::output());
        if ($out !== '') {
            $log[] = '  '.$out;
        }
    } catch (Throwable $e) {
        $log[] = '[deploy] FAIL '.$command.' — '.$e->getMessage();
    }
}

$log[] = '[deploy] finished '.gmdate('c');

echo implode("\n", $log)."\n";
