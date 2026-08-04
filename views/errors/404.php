<?php
/** 404 shown inside the dashboard chrome (authenticated users). */
$__u     = Auth::user();
$__admin = ($__u['role'] ?? '') === 'admin';

$links = [
    ['dashboard',      'Dashboard',      'house-door-fill',          'violet'],
    ['contacts',       'Contacts',       'people-fill',              'blue'],
    ['campaigns',      'Campaigns',      'send-fill',                'pink'],
    ['templates',      'Templates',      'file-richtext-fill',       'orange'],
    ['smtp',           'SMTP Accounts',  'hdd-network-fill',         'sky'],
    ['email-verifier', 'Email Verifier', 'patch-check-fill',         'green'],
    ['automations',    'Automations',    'diagram-3-fill',           'violet'],
    ['reports',        'Reports',        'bar-chart-fill',           'blue'],
    ['emails',         'Sent Emails',    'envelope-check-fill',      'pink'],
    ['suppression',    'Suppression',    'slash-circle-fill',        'orange'],
    ['billing',        'Billing & Plans','credit-card-2-front-fill', 'sky'],
    ['settings',       'Settings',       'gear-fill',                'green'],
];
if ($__admin) {
    $links[] = ['admin', 'Admin Overview', 'pie-chart-fill', 'red'];
}
?>
<div class="nf-wrap">
  <div class="nf-hero">
    <div class="nf-code">4<span class="nf-zero"><i class="bi bi-compass"></i></span>4</div>
    <h2 class="nf-title">This page wandered off</h2>
    <p class="nf-sub">The page you’re looking for doesn’t exist, was moved, or the link is broken. Let’s get you back on track.</p>
    <div class="nf-actions">
      <a href="<?= url('dashboard') ?>" class="btn btn-primary"><i class="bi bi-house-door me-1"></i> Back to dashboard</a>
      <button type="button" class="btn btn-light" onclick="history.back()"><i class="bi bi-arrow-left me-1"></i> Go back</button>
      <button type="button" class="btn btn-light" id="nf-search"><i class="bi bi-search me-1"></i> Search <span class="kbd ms-1">Ctrl K</span></button>
    </div>
  </div>

  <div class="nf-links-head">Or jump to a section</div>
  <div class="nf-grid">
    <?php foreach ($links as [$route, $label, $icon, $grad]): ?>
      <a class="nf-card" href="<?= url($route) ?>">
        <span class="stat-icon grad-<?= $grad ?>"><i class="bi bi-<?= $icon ?>"></i></span>
        <span class="nf-card-label"><?= e($label) ?></span>
        <i class="bi bi-arrow-right nf-arrow"></i>
      </a>
    <?php endforeach; ?>
  </div>

  <p class="nf-help">Still stuck? <a href="<?= url('settings') ?>">Visit settings</a> or contact your administrator.</p>
</div>

<style>
  .nf-wrap{ max-width:880px; margin:8px auto; }
  .nf-hero{ text-align:center; padding:34px 20px 26px; background:
      radial-gradient(60% 80% at 50% 0%, rgba(109,94,252,.10), transparent 70%);
      border-radius:20px; }
  .nf-code{ display:inline-flex; align-items:center; gap:10px; font-weight:800; line-height:1;
      font-size:clamp(4rem,12vw,7rem); letter-spacing:-.04em;
      background:var(--brand-grad); -webkit-background-clip:text; background-clip:text; color:transparent; }
  .nf-zero{ width:clamp(3.4rem,10vw,6rem); height:clamp(3.4rem,10vw,6rem); border-radius:50%;
      display:grid; place-items:center; color:#fff; -webkit-text-fill-color:#fff;
      background:var(--brand-grad); font-size:.5em; box-shadow:0 18px 40px -16px var(--ring);
      animation:nf-spin 9s linear infinite; }
  @keyframes nf-spin{ to{ transform:rotate(360deg); } }
  .nf-title{ font-weight:700; margin:14px 0 6px; letter-spacing:-.01em; }
  .nf-sub{ color:#8a93b2; max-width:48ch; margin:0 auto 20px; }
  .nf-actions{ display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
  .nf-actions .kbd{ font-size:.7rem; border:1px solid rgba(20,24,60,.15); border-radius:5px; padding:1px 6px; }

  .nf-links-head{ text-align:center; text-transform:uppercase; letter-spacing:.12em;
      font-size:.72rem; font-weight:700; color:#98a0bd; margin:34px 0 16px; }
  .nf-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
  .nf-card{ display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:14px;
      background:var(--bs-body-bg); border:1px solid rgba(20,24,60,.08); color:var(--bs-body-color);
      transition:transform .15s, box-shadow .15s, border-color .15s; }
  [data-bs-theme="dark"] .nf-card{ border-color:rgba(255,255,255,.07); }
  .nf-card:hover{ transform:translateY(-3px); border-color:rgba(109,94,252,.4);
      box-shadow:0 16px 30px -18px rgba(20,24,60,.5); }
  .nf-card .stat-icon{ width:40px; height:40px; font-size:1.05rem; flex:0 0 40px; }
  .nf-card-label{ font-weight:600; font-size:.92rem; }
  .nf-arrow{ margin-left:auto; color:#b9c0d6; transition:transform .15s, color .15s; }
  .nf-card:hover .nf-arrow{ color:var(--brand); transform:translateX(3px); }
  .nf-help{ text-align:center; color:#98a0bd; font-size:.88rem; margin-top:24px; }
  .nf-help a{ color:var(--brand); font-weight:600; }

  @media (max-width:720px){ .nf-grid{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width:460px){ .nf-grid{ grid-template-columns:1fr; } }
</style>
<script>
  // Wire the "Search" button to the existing Ctrl/Cmd+K command palette if present.
  document.getElementById('nf-search')?.addEventListener('click', function(){
    var t = document.getElementById('searchTrigger');
    if (t) { t.click(); return; }
    var ev = new KeyboardEvent('keydown', {key:'k', ctrlKey:true, metaKey:true, bubbles:true});
    document.dispatchEvent(ev);
  });
</script>
