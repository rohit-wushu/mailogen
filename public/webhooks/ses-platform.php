<?php
/**
 * Amazon SES bounce/complaint webhook for the platform-wide SES connection,
 * delivered via SNS.
 *
 * URL to paste into the SNS topic subscription (HTTPS endpoint):
 *   https://yourdomain.com/webhooks/ses-platform.php?t=<platform webhook token>
 *   (shown on the Admin > Amazon SES page)
 *
 * Unlike public/webhooks/ses.php (one token per tenant's own SMTP account),
 * there is only ONE SES connection for the whole platform, shared by every
 * tenant's Sending Domain. So instead of the token identifying which tenant
 * an event belongs to, each outgoing send's SES MessageId is stored on its
 * email_queue row (see EmailQueue::markSent) and matched back here via
 * `mail.messageId`, which SES always includes on bounce/complaint
 * notifications regardless of tagging/header configuration.
 *
 * Auth: same caveat as ses.php — the `t` token is the only authentication;
 * SNS message-signature verification is not implemented. Treat it as a secret.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$conn = SesConnection::platform();
$token = (string) ($_GET['t'] ?? '');
if (!$conn || $token === '' || !hash_equals((string) $conn['webhook_token'], $token)) {
    http_response_code(404);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit;
}

// SNS subscription handshake: confirm by fetching SubscribeURL once. Only
// ever follow an amazonaws.com SNS URL to avoid SSRF via a forged body.
if (($payload['Type'] ?? '') === 'SubscriptionConfirmation') {
    $subUrl = (string) ($payload['SubscribeURL'] ?? '');
    $host = parse_url($subUrl, PHP_URL_HOST) ?: '';
    if ($subUrl !== '' && preg_match('/(^|\.)sns\.[a-z0-9-]+\.amazonaws\.com$/i', $host)) {
        @file_get_contents($subUrl);
        SystemLog::write('info', 'webhook.ses_platform', 'SNS subscription confirmed for the platform SES connection');
    }
    http_response_code(200);
    exit;
}

if (($payload['Type'] ?? '') === 'Notification') {
    $ses = json_decode((string) ($payload['Message'] ?? ''), true);
    if (is_array($ses)) {
        $type      = $ses['notificationType'] ?? $ses['eventType'] ?? '';
        $messageId = (string) ($ses['mail']['messageId'] ?? '');
        $queue     = $messageId !== '' ? EmailQueue::findBySesMessageId($messageId) : null;

        if ($queue && in_array($type, ['Bounce', 'Complaint'], true)) {
            $uid  = (int) $queue['user_id'];
            $cid  = $queue['campaign_id'] !== null ? (int) $queue['campaign_id'] : null;
            $qid  = (int) $queue['id'];
            $ctid = (int) $queue['contact_id'];

            if ($type === 'Bounce') {
                $hard = ($ses['bounce']['bounceType'] ?? '') === 'Permanent';
                foreach ($ses['bounce']['bouncedRecipients'] ?? [] as $r) {
                    WebhookHelpers::recordEventRaw($uid, null, $cid, $qid, $ctid, (string) ($r['emailAddress'] ?? ''), 'bounced', $hard ? 'hard' : 'soft', (string) ($r['diagnosticCode'] ?? 'SES bounce'));
                }
            } else {
                foreach ($ses['complaint']['complainedRecipients'] ?? [] as $r) {
                    WebhookHelpers::recordEventRaw($uid, null, $cid, $qid, $ctid, (string) ($r['emailAddress'] ?? ''), 'complained', null, 'SES complaint');
                }
            }
            Reputation::checkAndFlagTenant($uid);
        } elseif (in_array($type, ['Bounce', 'Complaint'], true)) {
            SystemLog::write('warning', 'webhook.ses_platform', 'SES ' . $type . ' notification for unmatched MessageId: ' . $messageId);
        }
    }
}

http_response_code(200);
