<?php
/**
 * Shared cron guard. Allows execution from the CLI (Hostinger cron command)
 * or over HTTP when ?key=<CRON_KEY> matches. Prevents public abuse.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    $key = (string) ($_GET['key'] ?? '');
    if (!hash_equals(CRON_KEY, $key)) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain');
}

/** Lightweight stdout helper for cron logs. */
function cron_out(string $line): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n";
}
