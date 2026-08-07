<?php
/**
 * Advances every warm-up-enabled SMTP account's daily_limit one step.
 * Run once daily (a step is a day, not a cron tick — running more often
 * would just no-op since each account only needs one step per calendar day,
 * but scheduling it more than once daily wastes nothing either way).
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$advanced = WarmupEngine::run();
cron_out("SMTP warm-up: {$advanced} account(s) advanced.");
