<?php
/**
 * Brevo (Sendinblue) transactional webhook (hard_bounce/soft_bounce/spam events).
 *
 * URL to paste into Brevo -> Transactional -> Settings -> Webhooks:
 *   https://yourdomain.com/webhooks/brevo.php?t=<account webhook token>
 *
 * Auth: the `t` token identifies the SMTP account. Brevo does not sign
 * webhook requests, so the token is the only verification available.
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
$events = is_array($payload) && array_is_list($payload) ? $payload : [$payload];

foreach ($events as $e) {
    if (!is_array($e)) {
        continue;
    }
    $event = (string) ($e['event'] ?? '');
    $email = (string) ($e['email'] ?? '');
    $reason = (string) ($e['reason'] ?? $event);

    if ($event === 'hard_bounce') {
        WebhookHelpers::recordEvent($account, $email, 'bounced', 'hard', $reason);
    } elseif ($event === 'soft_bounce') {
        WebhookHelpers::recordEvent($account, $email, 'bounced', 'soft', $reason);
    } elseif ($event === 'spam' || $event === 'complaint') {
        WebhookHelpers::recordEvent($account, $email, 'complained', null, 'Brevo complaint');
    }
}

http_response_code(200);
