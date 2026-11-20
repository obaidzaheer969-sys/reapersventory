<?php

// Clear stale bytecode before loading the updater, so a freshly pulled
// rv-update.php is used even when OPcache timestamp validation is disabled.
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require __DIR__.'/rv-update.php';
