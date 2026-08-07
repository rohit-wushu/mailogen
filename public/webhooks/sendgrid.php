<?php
/**
 * SendGrid Event Webhook (bounce/dropped/spamreport events).
 *
 * URL to paste into SendGrid -> Settings -> Mail Settings -> Event Webhook:
 *   https://yourdomain.com/webhooks/sendgrid.php?t=<account webhook token>
 *
 * Auth: the `t` token identifies the SMTP account. SendGrid also supports
 * ECDSA-signed events (X-Twilio-Email-Event-Webhook-Signature); NOT verified
 * here — a hardening TODO. Treat the token as a secret.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$account = WebhookHelpers::resolveAccount((string) ($_GET['t'] ?? ''));
if (!$account) {
    http_response_code(404);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$events = json_decode($raw, true);

if (is_array($events)) {
    foreach ($events as $e) {
        if (!is_array($e)) {
            continue;
        }
        $type = (string) ($e['event'] ?? '');
        $email = (string) ($e['email'] ?? '');
        $reason = (string) ($e['reason'] ?? $type);

        if ($type === 'bounce' || $type === 'dropped') {
            $soft = ($e['type'] ?? '') === 'blocked' || $type === 'dropped';
            WebhookHelpers::recordEvent($account, $email, 'bounced', $soft ? 'soft' : 'hard', $reason);
        } elseif ($type === 'spamreport') {
            WebhookHelpers::recordEvent($account, $email, 'complained', null, 'SendGrid spam report');
        }
    }
}

http_response_code(200);
