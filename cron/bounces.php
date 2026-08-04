<?php
/**
 * Inbound mail reader — bounce + reply processing (optional).
 *
 * For each user with IMAP configured, scans unseen messages:
 *   - Bounce / non-delivery reports  -> suppress the address + mark bounced.
 *   - Replies from a known contact   -> record in `replies` (powers the
 *     "stop sequence if replied" automation/follow-up condition).
 *
 * Requires the PHP `imap` extension. If it's not installed the job exits
 * cleanly so it can be added later on the host without code changes.
 *
 * Hostinger cron (every 15-30 min):
 *   php /home/USER/.../emailer-tool/cron/bounces.php
 *   or https://yourdomain.com/cron/bounces.php?key=YOUR_CRON_KEY
 */

declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

if (!extension_loaded('imap')) {
    cron_out('IMAP extension not available — skipping. Enable it in hPanel to process bounces/replies.');
    exit;
}

$users = db()->query("SELECT * FROM users WHERE imap_enabled = 1 AND imap_host IS NOT NULL")->fetchAll();
if ($users === []) {
    cron_out('No mailboxes configured.');
    exit;
}

$totalBounces = 0;
$totalReplies = 0;

foreach ($users as $user) {
    $uid  = (int) $user['id'];
    $host = $user['imap_host'];
    $port = (int) ($user['imap_port'] ?: 993);
    $pass = Crypto::decrypt((string) $user['imap_pass']);
    $mailbox = '{' . $host . ':' . $port . '/imap/ssl}INBOX';

    $imap = @imap_open($mailbox, (string) $user['imap_user'], $pass, 0, 1);
    if (!$imap) {
        SystemLog::write('warning', 'cron.bounces', "IMAP login failed for user #{$uid}: " . imap_last_error(), $uid);
        continue;
    }

    $unseen = imap_search($imap, 'UNSEEN') ?: [];
    foreach ($unseen as $num) {
        $header = imap_headerinfo($imap, $num);
        $from   = strtolower($header->from[0]->mailbox . '@' . $header->from[0]->host);
        $subject = isset($header->subject) ? imap_utf8($header->subject) : '';
        $body    = (string) @imap_fetchbody($imap, $num, '1');

        if (InboundHelpers::looksLikeBounce($from, $subject, $body)) {
            $bounced = InboundHelpers::extractBouncedAddress($body) ?: $from;
            if (filter_var($bounced, FILTER_VALIDATE_EMAIL)) {
                Unsubscribe::add($uid, $bounced, null, 'bounce');
                Contact::markStatusByEmail($uid, $bounced, 'bounced');
                $totalBounces++;
            }
        } elseif ($contact = Contact::findByEmail($uid, $from)) {
            db()->prepare('INSERT INTO replies (user_id, contact_id, email) VALUES (?, ?, ?)')
                ->execute([$uid, (int) $contact['id'], $from]);
            $totalReplies++;
        }
        imap_setflag_full($imap, (string) $num, '\\Seen');
    }
    imap_close($imap);
}

cron_out("Inbound processed: {$totalBounces} bounce(s), {$totalReplies} reply(ies).");

/**
 * Helpers (declared as a small anonymous-class-free static holder so the
 * cron stays a single self-contained file).
 */
final class InboundHelpers
{
    public static function looksLikeBounce(string $from, string $subject, string $body): bool
    {
        $needles = ['mailer-daemon', 'postmaster'];
        foreach ($needles as $n) {
            if (str_contains($from, $n)) {
                return true;
            }
        }
        $subjectHit = preg_match('/(undeliverable|delivery (status|has failed)|failure notice|returned mail|mail delivery failed)/i', $subject);
        $bodyHit    = preg_match('/(550|5\.\d\.\d|user unknown|mailbox (unavailable|full)|does not exist)/i', $body);
        return (bool) ($subjectHit || $bodyHit);
    }

    public static function extractBouncedAddress(string $body): ?string
    {
        if (preg_match('/(?:Final-Recipient|Original-Recipient|To)[^\n]*?([\w.+-]+@[\w.-]+\.[A-Za-z]{2,})/', $body, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }
}
