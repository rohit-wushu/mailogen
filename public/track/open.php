<?php
/**
 * Open-tracking pixel. Records the open and returns a 1x1 transparent GIF.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$trackingId = (string) ($_GET['t'] ?? '');
if ($trackingId !== '') {
    try {
        $queue = EmailQueue::findByTracking($trackingId);
        if ($queue !== null) {
            Tracking::recordOpen($queue, UserAgent::meta());
        }
    } catch (\Throwable $e) {
        // Never let tracking errors surface to the recipient.
        SystemLog::write('error', 'track.open', $e->getMessage());
    }
}

// 1x1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
