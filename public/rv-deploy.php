<?php

// Invalidate the updater explicitly before loading it. Some hosting accounts
// disable OPcache timestamp validation, so a plain require() can keep running
// an older copy of rv-update.php even after git reset has replaced the file.
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__DIR__.'/rv-update.php', true);
}

if (function_exists('opcache_reset')) {
    opcache_reset();
}

clearstatcache(true, __DIR__.'/rv-update.php');

require __DIR__.'/rv-update.php';
