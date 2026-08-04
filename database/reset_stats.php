<?php
/**
 * Reset analytics / demo data to zero WITHOUT touching real config.
 *
 * Clears the event tables (sent logs, opens, clicks, queue) and resets per-
 * campaign counters so the dashboard reflects only real future activity.
 * KEEPS: users, contacts, lists, templates, SMTP accounts, campaigns,
 *        automations, suppression list.
 *
 *   php database/reset_stats.php          # all users
 *   php database/reset_stats.php 1        # only user id 1
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$uid = isset($argv[1]) ? (int) $argv[1] : 0;
$scope = $uid > 0 ? ' WHERE user_id = ' . $uid : '';
$db = db();

$before = [
    'email_logs' => (int) $db->query('SELECT COUNT(*) FROM email_logs' . $scope)->fetchColumn(),
    'opens'      => (int) $db->query('SELECT COUNT(*) FROM opens' . $scope)->fetchColumn(),
    'clicks'     => (int) $db->query('SELECT COUNT(*) FROM clicks' . $scope)->fetchColumn(),
];

// Wipe tracking/event tables.
foreach (['email_logs', 'opens', 'clicks', 'email_queue', 'campaign_contacts'] as $t) {
    if ($uid > 0) {
        // campaign_contacts has no user_id — scope via its campaign.
        if ($t === 'campaign_contacts') {
            $db->prepare("DELETE cc FROM campaign_contacts cc JOIN campaigns c ON c.id = cc.campaign_id WHERE c.user_id = ?")->execute([$uid]);
        } else {
            $db->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$uid]);
        }
    } else {
        $db->exec("DELETE FROM `$t`");
    }
}

// Reset per-campaign counters.
if ($uid > 0) {
    $db->prepare("UPDATE campaigns SET sent_count = 0, total_recipients = 0 WHERE user_id = ?")->execute([$uid]);
} else {
    $db->exec("UPDATE campaigns SET sent_count = 0, total_recipients = 0");
}

// Reset per-SMTP send counters.
$db->exec('UPDATE smtp_accounts SET sent_today = 0, sent_total = 0, fail_total = 0' . $scope);

echo "Cleared demo analytics" . ($uid > 0 ? " for user #$uid" : " (all users)") . ":\n";
echo "  email_logs: {$before['email_logs']} → 0\n";
echo "  opens:      {$before['opens']} → 0\n";
echo "  clicks:     {$before['clicks']} → 0\n";
echo "  campaign + SMTP counters reset.\n";
echo "Contacts, lists, templates, campaigns and SMTP accounts were kept.\n";
