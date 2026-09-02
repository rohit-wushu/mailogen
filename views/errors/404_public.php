<?php /** Standalone 404 for logged-out visitors (brand styled, no app chrome). */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page not found · <?= e(Site::name()) ?></title>
  <meta name="robots" content="noindex">
  <link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
  <script>(function(){var t=localStorage.getItem('mg-theme')||'light';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{ --brand:#6d5efc; --brand2:#8b5cf6; --brand-d:#5b4ee8; --grad:linear-gradient(135deg,#6d5efc,#8b5cf6);
           --ink:#0a2540; --muted:#51647a; --line:#e6ebf0; --soft:#f0eefe; --ring:rgba(109,94,252,.35); --card-bg:#fff; }
    html[data-bs-theme="dark"]{ --ink:#e6e9f5; --muted:#97a3c4; --line:rgba(255,255,255,.1); --soft:rgba(109,94,252,.16); --card-bg:#131a36; }
    html[data-bs-theme="dark"] body{ background:radial-gradient(60% 60% at 88% -5%, #241d47, transparent 60%),
                 radial-gradient(50% 50% at 0% 25%, #241a12, transparent 55%), #0a0f24; }
    *{box-sizing:border-box}
    body{ margin:0; min-height:100vh; display:flex; flex-direction:column; color:var(--ink);
      font-family:"Montserrat",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
      background:radial-gradient(60% 60% at 88% -5%, #e7e3fd, transparent 60%),
                 radial-gradient(50% 50% at 0% 25%, #fff6df, transparent 55%), #fff;
      transition:background .2s, color .2s; }
    a{text-decoration:none;color:inherit}
    .wrap{ max-width:760px; margin:0 auto; padding:0 22px; }
    .topbar{ height:74px; display:flex; align-items:center; gap:12px; }
    .brand{ display:flex; align-items:center; gap:11px; font-weight:700; font-size:1.25rem; letter-spacing:-.02em; }
    .theme-toggle{ margin-left:auto; width:42px; height:42px; border-radius:12px; border:1px solid var(--line);
      background:var(--card-bg); color:var(--muted); display:inline-flex; align-items:center; justify-content:center;
      font-size:1.05rem; cursor:pointer; }
    .theme-toggle:hover{ color:var(--brand); }
    .brand .logo{ width:40px;height:40px;border-radius:12px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:1.15rem; }
    main{ flex:1; display:flex; align-items:center; padding:30px 0 60px; }
    .card-404{ width:100%; text-align:center; }
    .code{ display:inline-flex; align-items:center; gap:12px; line-height:1; font-weight:800;
      font-size:clamp(4.5rem,16vw,9rem); letter-spacing:-.04em;
      background:var(--grad); -webkit-background-clip:text; background-clip:text; color:transparent; }
    .zero{ width:clamp(3.6rem,12vw,7rem); height:clamp(3.6rem,12vw,7rem); border-radius:50%;
      display:grid; place-items:center; color:#fff; -webkit-text-fill-color:#fff; background:var(--grad);
      font-size:.46em; box-shadow:0 22px 50px -18px var(--ring); animation:spin 9s linear infinite; }
    @keyframes spin{ to{ transform:rotate(360deg); } }
    h1{ font-weight:700; font-size:clamp(1.5rem,3vw,2rem); letter-spacing:-.015em; margin:18px 0 8px; }
    .sub{ color:var(--muted); font-size:1.05rem; max-width:46ch; margin:0 auto 26px; line-height:1.6; }
    .actions{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:34px; }
    .btn{ display:inline-flex; align-items:center; gap:8px; font-weight:700; border-radius:11px;
      border:1.6px solid transparent; padding:13px 24px; cursor:pointer; font-family:inherit; font-size:1rem;
      transition:transform .15s, box-shadow .15s, background .15s, border-color .15s, color .15s; }
    .btn-grad{ background:var(--grad); color:#fff; box-shadow:0 14px 28px -12px rgba(109,94,252,.8); }
    .btn-grad:hover{ transform:translateY(-2px); box-shadow:0 18px 34px -12px rgba(109,94,252,.9); }
    .btn-outline{ background:var(--card-bg); color:var(--ink); border-color:var(--line); }
    .btn-outline:hover{ border-color:var(--brand); color:var(--brand); transform:translateY(-2px); }

    .links-head{ text-transform:uppercase; letter-spacing:.12em; font-size:.72rem; font-weight:700; color:#9aa3bd; margin-bottom:14px; }
    .links{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
    .lcard{ display:flex; align-items:center; gap:11px; padding:14px 16px; border-radius:14px; background:var(--card-bg);
      border:1px solid var(--line); color:var(--ink); transition:transform .15s, box-shadow .15s, border-color .15s; text-align:left; }
    .lcard:hover{ transform:translateY(-3px); border-color:rgba(109,94,252,.4); box-shadow:0 16px 30px -18px rgba(10,37,64,.4); }
    .lcard .ic{ width:38px;height:38px;border-radius:11px;background:var(--soft);color:var(--brand-d);display:grid;place-items:center;font-size:1.05rem;flex:0 0 38px; }
    .lcard .t{ font-weight:600; font-size:.92rem; }
    .lcard .arr{ margin-left:auto; color:#c4cad8; transition:transform .15s, color .15s; }
    .lcard:hover .arr{ color:var(--brand); transform:translateX(3px); }

    footer{ padding:22px 0; border-top:1px solid var(--line); color:var(--muted); font-size:.86rem; }
    footer .frow{ display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    footer a{ color:var(--muted); margin-left:16px; } footer a:hover{ color:var(--ink); }

    @media (max-width:620px){ .links{ grid-template-columns:1fr; } }
    @media (prefers-reduced-motion:reduce){ *{animation:none !important; transition:none !important;} }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="wrap" style="display:flex;align-items:center">
      <a class="brand" href="<?= url('') ?>"><?php if (Site::showMark()): $__el = Site::logoUrl(); ?><?php if ($__el): ?><img class="logo" src="<?= e($__el) ?>" alt="<?= e(Site::name()) ?>" style="object-fit:contain;background:transparent"><?php else: ?><span class="logo"><i class="bi bi-send-fill"></i></span><?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></a>
      <button type="button" class="theme-toggle" id="notFoundThemeToggle" title="Toggle theme" aria-label="Toggle theme"><i class="bi bi-moon-stars-fill"></i></button>
    </div>
  </header>

  <main>
    <div class="wrap card-404">
      <div class="code">4<span class="zero"><i class="bi bi-compass"></i></span>4</div>
      <h1>This page wandered off</h1>
      <p class="sub">The page you’re looking for doesn’t exist, was moved, or the link is broken. Here’s how to get back on track.</p>
      <div class="actions">
        <a class="btn btn-grad" href="<?= url('') ?>"><i class="bi bi-house-door"></i> Go home</a>
        <a class="btn btn-outline" href="<?= url('login') ?>"><i class="bi bi-box-arrow-in-right"></i> Log in</a>
      </div>

      <div class="links-head">Popular pages</div>
      <div class="links">
        <a class="lcard" href="<?= url('') ?>"><span class="ic"><i class="bi bi-house-door-fill"></i></span><span class="t">Home</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('') ?>#features"><span class="ic"><i class="bi bi-grid-1x2-fill"></i></span><span class="t">Features</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('') ?>#pricing"><span class="ic"><i class="bi bi-tag-fill"></i></span><span class="t">Pricing</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('') ?>#how"><span class="ic"><i class="bi bi-magic"></i></span><span class="t">How it works</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('') ?>#faq"><span class="ic"><i class="bi bi-question-circle-fill"></i></span><span class="t">FAQ</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('register') ?>"><span class="ic"><i class="bi bi-rocket-takeoff-fill"></i></span><span class="t">Sign up free</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('login') ?>"><span class="ic"><i class="bi bi-box-arrow-in-right"></i></span><span class="t">Log in</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('legal/terms') ?>"><span class="ic"><i class="bi bi-file-text-fill"></i></span><span class="t">Terms</span><i class="bi bi-arrow-right arr"></i></a>
        <a class="lcard" href="<?= url('legal/privacy') ?>"><span class="ic"><i class="bi bi-shield-lock-fill"></i></span><span class="t">Privacy</span><i class="bi bi-arrow-right arr"></i></a>
      </div>
    </div>
  </main>

  <footer>
    <div class="wrap frow">
      <span>© <?= date('Y') ?> <?= e(Site::name()) ?></span>
      <span>
        <a href="<?= url('legal/terms') ?>">Terms</a>
        <a href="<?= url('legal/privacy') ?>">Privacy</a>
        <a href="<?= url('legal/acceptable-use') ?>">Acceptable use</a>
      </span>
    </div>
  </footer>
  <script>
    (function () {
      var btn = document.getElementById('notFoundThemeToggle');
      var icon = btn.querySelector('i');
      function paint(t) { icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'; }
      paint(document.documentElement.getAttribute('data-bs-theme'));
      btn.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('mg-theme', next);
        paint(next);
      });
    })();
  </script>
</body>
</html>
