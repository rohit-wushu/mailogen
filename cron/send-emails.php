<?php
/**
 * Primary send worker — run frequently (e.g. every 5 minutes).
 *
 *   1. Roll over SMTP daily counters at midnight.
 *   2. Launch any scheduled campaigns whose time has arrived.
 *   3. Drain a batch from the email queue (rotation + failover + tracking).
 *
 * Hostinger cron (CLI):
 *   php /home/USER/domains/yourdomain.com/emailer-tool/cron/send-emails.php
 * Or URL-based:
 *   https://mailer.yourdomain.com/cron/send-emails.php?key=YOUR_CRON_KEY
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

// 1. Reset per-account daily quotas if the date changed.
SmtpAccount::resetDailyIfNeeded();

// 2. Promote due scheduled campaigns into the queue.
$launched = 0;
foreach (Campaign::dueForSending() as $campaign) {
    $count = CampaignBuilder::enqueue($campaign);
    $launched += $count;
    SystemLog::write('info', 'cron.schedule', "Launched campaign #{$campaign['id']} ({$count} recipients)", (int) $campaign['user_id']);
}
if ($launched > 0) {
    cron_out("Scheduled campaigns launched: {$launched} emails queued.");
}

// 3. Process a batch of the queue.
$result = QueueProcessor::run(BATCH_SIZE);
cron_out("Queue processed: {$result['processed']} (sent {$result['sent']}, failed {$result['failed']}).");
