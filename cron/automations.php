<?php
/**
 * Automation + follow-up worker — run periodically (e.g. every 15 minutes).
 *
 *   - Queues campaign follow-up steps for eligible recipients.
 *   - Advances automation-workflow enrolments that are due.
 *
 * Queued emails are then delivered by send-emails.php.
 *
 * Hostinger cron (CLI):
 *   php /home/USER/.../emailer-tool/cron/automations.php
 * URL-based:
 *   https://mailer.yourdomain.com/cron/automations.php?key=YOUR_CRON_KEY
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$followups = FollowupEngine::run();
cron_out("Follow-up steps queued: {$followups}.");

$workflows = AutomationEngine::run();
cron_out("Automation enrolments advanced: {$workflows}.");
