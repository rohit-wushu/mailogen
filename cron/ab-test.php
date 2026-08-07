<?php
/**
 * Decides subject-line A/B tests whose test window has elapsed and releases
 * the holdout batch with the winning subject. Run every 15 minutes.
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$decided = AbTestEngine::run();
cron_out("A/B tests decided: {$decided}.");
