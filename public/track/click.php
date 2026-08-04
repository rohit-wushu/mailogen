<?php
/**
 * Click-tracking redirect. Records the click then 302s to the real URL.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$trackingId = (string) ($_GET['t'] ?? '');
$encoded    = (string) ($_GET['u'] ?? '');
$target     = Mailer::b64urlDecode($encoded);

// Only allow http(s) redirects (open-redirect protection).
if (!preg_match('#^https?://#i', $target)) {
    $target = rtrim(APP_URL, '/');
}

if ($trackingId !== '') {
    try {
        $queue = EmailQueue::findByTracking($trackingId);
        if ($queue !== null) {
            Tracking::recordClick($queue, $target, UserAgent::meta());
        }
    } catch (\Throwable $e) {
        SystemLog::write('error', 'track.click', $e->getMessage());
    }
}

header('Location: ' . $target, true, 302);
exit;
