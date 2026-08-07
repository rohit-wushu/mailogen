<?php
/**
 * Amazon SES bounce/complaint webhook, delivered via SNS.
 *
 * URL to paste into the SNS topic subscription (HTTPS endpoint):
 *   https://yourdomain.com/webhooks/ses.php?t=<account webhook token>
 *
 * Auth: the `t` token identifies the SMTP account (see WebhookHelpers doc
 * comment on why that's the primary protection here). SNS message-signature
 * verification (X.509 cert fetch + RSA verify) is NOT implemented — a
 * hardening TODO — so treat the token as a secret and don't publish it.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$account = WebhookHelpers::resolveAccount((string) ($_GET['t'] ?? ''));
if (!$account) {
    http_response_code(404);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit;
}

// SNS subscription handshake: confirm by fetching SubscribeURL once.
// Only ever follow an amazonaws.com SNS URL to avoid SSRF via a forged body.
if (($payload['Type'] ?? '') === 'SubscriptionConfirmation') {
    $subUrl = (string) ($payload['SubscribeURL'] ?? '');
    $host = parse_url($subUrl, PHP_URL_HOST) ?: '';
    if ($subUrl !== '' && preg_match('/(^|\.)sns\.[a-z0-9-]+\.amazonaws\.com$/i', $host)) {
        @file_get_contents($subUrl);
        SystemLog::write('info', 'webhook.ses', 'SNS subscription confirmed for smtp #' . $account['id'], (int) $account['user_id']);
    }
    http_response_code(200);
    exit;
}

if (($payload['Type'] ?? '') === 'Notification') {
    $ses = json_decode((string) ($payload['Message'] ?? ''), true);
    if (is_array($ses)) {
        $type = $ses['notificationType'] ?? $ses['eventType'] ?? '';
        if ($type === 'Bounce') {
            $hard = ($ses['bounce']['bounceType'] ?? '') === 'Permanent';
            foreach ($ses['bounce']['bouncedRecipients'] ?? [] as $r) {
                WebhookHelpers::recordEvent($account, (string) ($r['emailAddress'] ?? ''), 'bounced', $hard ? 'hard' : 'soft', (string) ($r['diagnosticCode'] ?? 'SES bounce'));
            }
        } elseif ($type === 'Complaint') {
            foreach ($ses['complaint']['complainedRecipients'] ?? [] as $r) {
                WebhookHelpers::recordEvent($account, (string) ($r['emailAddress'] ?? ''), 'complained', null, 'SES complaint');
            }
        }
    }
}

http_response_code(200);
