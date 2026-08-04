<?php /** Shared head + branded aside for all auth pages. Set $authTitle first. */ ?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($authTitle ?? 'Welcome') ?> · <?= e(Site::metaTitle()) ?></title>
<?php if ($__d = Site::metaDescription()): ?><meta name="description" content="<?= e($__d) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split">
  <aside class="auth-aside">
    <div class="aa-brand">
      <?php if (Site::showMark()): $__authLogo = Site::logoUrl(); ?><?php if ($__authLogo): ?><img src="<?= e($__authLogo) ?>" alt="<?= e(Site::name()) ?>" style="height:28px;width:auto;vertical-align:middle"> <?php else: ?><i class="bi bi-send-fill"></i> <?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?>
    </div>
    <div class="aa-headline">
      <h2>Email marketing on<br>your own SMTP.</h2>
      <p>Run campaigns, rotate senders and track every open &amp; click — without per-email pricing.</p>
      <ul class="auth-feats">
        <li><i class="bi bi-hdd-network"></i> Bring your own SMTP &amp; rotate senders</li>
        <li><i class="bi bi-graph-up-arrow"></i> Track opens, clicks, bounces &amp; replies</li>
        <li><i class="bi bi-patch-check"></i> Verify addresses before you send</li>
        <li><i class="bi bi-clock-history"></i> Schedule &amp; automate follow-ups</li>
      </ul>
    </div>
    <div class="auth-foot-note">© <?= date('Y') ?> <?= e(Site::name()) ?>. All rights reserved.</div>
  </aside>
  <main class="auth-main">
    <div class="auth-card">
      <div class="auth-brand auth-mobile-brand mb-4"><?php if (Site::showMark()): ?><i class="bi bi-send-fill"></i> <?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></div>
