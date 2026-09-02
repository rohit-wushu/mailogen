<?php
/**
 * Shared head + branded aside for all auth pages. Set $authTitle first.
 * Optionally set $authVariant to 'signup' or 'login' for the colour-block
 * illustrated aside (register/login pages); anything else falls back to
 * the default purple-gradient aside (forgot/reset pages).
 */
$__variant = $authVariant ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($authTitle ?? 'Welcome') ?> · <?= e(Site::metaTitle()) ?></title>
<?php if ($__d = Site::metaDescription()): ?><meta name="description" content="<?= e($__d) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
<script>(function(){var t=localStorage.getItem('mg-theme')||'light';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split">
  <?php if ($__variant === 'signup' || $__variant === 'login'): ?>
  <aside class="auth-aside av-<?= e($__variant) ?>">
    <div class="aa-wave" aria-hidden="true">
      <svg viewBox="0 0 40 800" preserveAspectRatio="none"><path d="M40,0 L28,0 C34,60 20,110 30,170 C40,230 22,290 32,350 C42,410 18,470 30,530 C42,590 20,650 30,710 C36,745 30,775 40,800 Z"/></svg>
    </div>
    <div class="aa-brand">
      <?php if (Site::showMark()): $__authLogo = Site::logoUrl(); ?><?php if ($__authLogo): ?><img src="<?= e($__authLogo) ?>" alt="<?= e(Site::name()) ?>" style="height:26px;width:auto;vertical-align:middle"> <?php else: ?><i class="bi bi-send-fill"></i> <?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?>
    </div>

    <div class="aa-illustration">
      <?php if ($__variant === 'signup'): ?>
        <svg viewBox="0 0 360 320" class="aa-svg" aria-hidden="true">
          <circle cx="180" cy="150" r="118" fill="#ffffff" opacity=".25"/>
          <ellipse cx="180" cy="270" rx="150" ry="18" fill="#0a2540" opacity=".06"/>
          <g stroke="#0a2540" stroke-width="1.6" fill="none" opacity=".35" stroke-dasharray="2 6" stroke-linecap="round">
            <ellipse cx="180" cy="150" rx="132" ry="100"/>
          </g>
          <!-- envelope -->
          <g transform="translate(96,110)">
            <rect x="0" y="0" width="168" height="112" rx="10" fill="#ffffff" stroke="#0a2540" stroke-width="3"/>
            <path d="M4 8 L84 72 L164 8" fill="none" stroke="#0a2540" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
          </g>
          <!-- growth bars rising from envelope -->
          <g transform="translate(120,150)">
            <rect x="0" y="34" width="16" height="30" rx="3" fill="#ffb84d"/>
            <rect x="24" y="18" width="16" height="46" rx="3" fill="#ff9f43"/>
            <rect x="48" y="0" width="16" height="64" rx="3" fill="#0a2540"/>
          </g>
          <!-- paper plane -->
          <g transform="translate(250,60) rotate(18)">
            <path d="M0 20 L54 0 L18 44 L14 26 Z" fill="#0a2540"/>
            <path d="M14 26 L54 0 L18 44 Z" fill="#12335c"/>
          </g>
          <path d="M226 96 C244 84 260 70 268 54" stroke="#0a2540" stroke-width="1.6" fill="none" stroke-dasharray="1 7" stroke-linecap="round" opacity=".5"/>
          <!-- coin -->
          <g transform="translate(66,70)">
            <circle r="16" fill="#ffd166" stroke="#0a2540" stroke-width="2.5"/>
            <text x="0" y="6" text-anchor="middle" font-size="16" font-weight="800" fill="#0a2540">$</text>
          </g>
          <g transform="translate(292,190)">
            <circle r="13" fill="#ffd166" stroke="#0a2540" stroke-width="2.5"/>
            <text x="0" y="5" text-anchor="middle" font-size="13" font-weight="800" fill="#0a2540">$</text>
          </g>
          <!-- sparkles -->
          <g fill="#0a2540" opacity=".7">
            <path d="M64 40 l4 10 10 4 -10 4 -4 10 -4-10 -10-4 10-4 z"/>
            <path d="M300 100 l3 7 7 3 -7 3 -3 7 -3-7 -7-3 7-3 z"/>
            <path d="M78 230 l3 7 7 3 -7 3 -3 7 -3-7 -7-3 7-3 z"/>
          </g>
        </svg>
      <?php else: ?>
        <svg viewBox="0 0 360 320" class="aa-svg" aria-hidden="true">
          <ellipse cx="180" cy="270" rx="150" ry="18" fill="#0a2540" opacity=".06"/>
          <!-- browser chrome -->
          <g transform="translate(50,58)">
            <rect x="0" y="0" width="260" height="176" rx="12" fill="#ffffff" stroke="#0a2540" stroke-width="3"/>
            <rect x="0" y="0" width="260" height="30" rx="12" fill="#0a2540"/>
            <circle cx="16" cy="15" r="4" fill="#ff9f43"/>
            <circle cx="30" cy="15" r="4" fill="#ffd166"/>
            <circle cx="44" cy="15" r="4" fill="#8de3c0"/>
            <!-- stat pills -->
            <g transform="translate(14,42)">
              <rect width="108" height="34" rx="8" fill="#fff7db" stroke="#0a2540" stroke-width="1.4"/>
              <text x="10" y="14" font-size="9" fill="#0a2540" opacity=".6">Sent</text>
              <text x="10" y="27" font-size="13" font-weight="800" fill="#0a2540">9.9M</text>
            </g>
            <g transform="translate(132,42)">
              <rect width="112" height="34" rx="8" fill="#eaf7ff" stroke="#0a2540" stroke-width="1.4"/>
              <text x="10" y="14" font-size="9" fill="#0a2540" opacity=".6">Opens</text>
              <text x="10" y="27" font-size="13" font-weight="800" fill="#0a2540">61%</text>
            </g>
            <!-- line chart -->
            <g transform="translate(14,88)">
              <rect width="108" height="72" rx="8" fill="#fbfbfe" stroke="#0a2540" stroke-width="1.4"/>
              <polyline points="8,54 26,40 44,48 62,20 80,30 98,12" fill="none" stroke="#ff9f43" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="98" cy="12" r="4" fill="#ff9f43"/>
            </g>
            <!-- bar chart -->
            <g transform="translate(132,88)">
              <rect width="112" height="72" rx="8" fill="#fbfbfe" stroke="#0a2540" stroke-width="1.4"/>
              <rect x="14" y="40" width="14" height="24" rx="2" fill="#0a2540"/>
              <rect x="38" y="24" width="14" height="40" rx="2" fill="#ffd166"/>
              <rect x="62" y="34" width="14" height="30" rx="2" fill="#0a2540"/>
              <rect x="86" y="14" width="14" height="50" rx="2" fill="#ffd166"/>
            </g>
          </g>
          <!-- envelope flying out -->
          <g transform="translate(268,40) rotate(-10)">
            <rect x="0" y="0" width="46" height="32" rx="5" fill="#ffffff" stroke="#0a2540" stroke-width="2.5"/>
            <path d="M2 4 L23 20 L44 4" fill="none" stroke="#0a2540" stroke-width="2.5" stroke-linejoin="round"/>
          </g>
          <path d="M236 60 C250 54 262 48 270 40" stroke="#0a2540" stroke-width="1.6" fill="none" stroke-dasharray="1 7" stroke-linecap="round" opacity=".5"/>
          <!-- check badge -->
          <g transform="translate(70,240)">
            <circle r="18" fill="#0a2540"/>
            <path d="M-7 0 L-2 6 L8 -7" stroke="#ffd166" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
          </g>
          <g fill="#0a2540" opacity=".7">
            <path d="M40 90 l3 7 7 3 -7 3 -3 7 -3-7 -7-3 7-3 z"/>
            <path d="M310 130 l4 10 10 4 -10 4 -4 10 -4-10 -10-4 10-4 z"/>
          </g>
        </svg>
      <?php endif; ?>
    </div>

    <div class="aa-headline aa-headline--light">
      <?php if ($__variant === 'signup'): ?>
        <h2>Easy, powerful<br>and data-driven tools</h2>
        <p><?= e(Site::name()) ?> is more than just sending email campaigns — it's about making intelligent decisions for growing your business.</p>
      <?php else: ?>
        <h2>One step closer to<br>a <strong>prosperous</strong> business</h2>
        <p>Send engaging campaigns with your own SMTP, get detailed insights on how they perform, and convert your leads into high-value customers.</p>
      <?php endif; ?>
    </div>
  </aside>
  <?php else: ?>
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
  <?php endif; ?>
  <main class="auth-main">
    <button type="button" class="icon-btn auth-theme-toggle" id="authThemeToggle" title="Toggle theme" aria-label="Toggle theme">
      <i class="bi bi-moon-stars-fill"></i>
    </button>
    <div class="auth-card">
      <div class="auth-brand auth-mobile-brand mb-4"><?php if (Site::showMark()): ?><i class="bi bi-send-fill"></i> <?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></div>
