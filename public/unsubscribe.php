<?php
/**
 * One-click unsubscribe landing page.
 *
 * Honours RFC 8058 List-Unsubscribe-Post (one-click) when invoked by the
 * mailbox provider, and shows a friendly confirmation page for humans.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$trackingId = (string) ($_GET['t'] ?? '');
$queue = $trackingId !== '' ? EmailQueue::findByTracking($trackingId) : null;

$done = false;
$email = '';

if ($queue !== null) {
    $email = $queue['email'];
    // One-click POST (mailbox provider) or explicit confirm button.
    $oneClick = $_SERVER['REQUEST_METHOD'] === 'POST';
    if ($oneClick) {
        Unsubscribe::add((int) $queue['user_id'], $email, (int) ($queue['campaign_id'] ?? 0) ?: null, 'one-click');
        Contact::markStatusByEmail((int) $queue['user_id'], $email, 'unsubscribed');
        if (!empty($queue['campaign_id'])) {
            CampaignContact::setStatus((int) $queue['campaign_id'], (int) $queue['contact_id'], 'unsubscribed');
        }
        $done = true;
    }
}

header('Content-Type: text/html; charset=UTF-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Unsubscribe · <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh">
  <div class="card shadow-sm" style="max-width:460px">
    <div class="card-body p-4 text-center">
      <?php if ($queue === null): ?>
        <h4 class="mb-2">Link not recognised</h4>
        <p class="text-muted">This unsubscribe link is invalid or has expired.</p>
      <?php elseif ($done): ?>
        <div class="text-success mb-2" style="font-size:2.5rem">&check;</div>
        <h4 class="mb-2">You're unsubscribed</h4>
        <p class="text-muted"><strong><?= e($email) ?></strong> will no longer receive these emails.</p>
      <?php else: ?>
        <h4 class="mb-2">Unsubscribe</h4>
        <p class="text-muted">Stop receiving emails at <strong><?= e($email) ?></strong>?</p>
        <form method="post" action="unsubscribe.php?t=<?= e($trackingId) ?>">
          <button class="btn btn-danger w-100">Yes, unsubscribe me</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
