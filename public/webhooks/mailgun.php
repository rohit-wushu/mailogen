<?php
/**
 * Mailgun bounce/complaint webhook (modern JSON event format).
 *
 * URL to paste into Mailgun -> Sending -> Webhooks (for "permanent failure",
 * "temporary failure" and "complained"):
 *   https://yourdomain.com/webhooks/mailgun.php?t=<account webhook token>
 *
 * Auth: the `t` token identifies the SMTP account. Mailgun also signs each
 * request (timestamp/token/signature via the account's HTTP webhook signing
 * key); that key isn't collected from the user yet, so signature
 * verification is NOT implemented here — a hardening TODO. Treat the token
 * as a secret.
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
$data = is_array($payload) ? ($payload['event-data'] ?? null) : null;

if (is_array($data)) {
    $event = (string) ($data['event'] ?? '');
    $recipient = (string) ($data['recipient'] ?? '');
    $severity = (string) ($data['severity'] ?? '');
    $reason = (string) ($data['delivery-status']['description'] ?? $data['reason'] ?? $event);

    if ($event === 'failed') {
        WebhookHelpers::recordEvent($account, $recipient, 'bounced', $severity === 'permanent' ? 'hard' : 'soft', $reason);
    } elseif ($event === 'complained') {
        WebhookHelpers::recordEvent($account, $recipient, 'complained', null, 'Mailgun complaint');
    }
}

http_response_code(200);
