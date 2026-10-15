<?php

/**
 * Compatibility entrypoint.
 * Prefer Laravel route: https://reapersventory.com/deploy?secret=YOUR_SECRET
 */
$secret = isset($_GET['secret']) ? (string) $_GET['secret'] : '';
$query = http_build_query(array_filter([
    'secret' => $secret !== '' ? $secret : null,
    'pull' => $_GET['pull'] ?? null,
]));

header('Location: /deploy'.($query !== '' ? '?'.$query : ''), true, 302);
exit;
