<?php
/** @var array $plans  Public landing page (no auth chrome) — Brevo-style. */
$pillars = [
    ['bi' => 'send-fill',                     'tint' => 'green',  'title' => 'Email Campaigns',   'text' => 'Design beautiful emails with a click-to-add builder, merge tags and live preview — then send or schedule to any list.'],
    ['bi' => 'hdd-network-fill',              'tint' => 'blue',   'title' => 'Your own SMTP',     'text' => 'Connect Gmail, Workspace, SES, Brevo, Mailgun, SendGrid or any SMTP. Rotate across accounts with automatic failover.'],
    ['bi' => 'file-earmark-spreadsheet-fill', 'tint' => 'yellow', 'title' => 'Google Sheets sync','text' => 'Mail-merge straight from a Google Sheet or CSV. Column headers become {{merge tags}} automatically.'],
    ['bi' => 'diagram-3-fill',                'tint' => 'pink',   'title' => 'Automation',        'text' => 'Visual workflows and drip follow-ups with stop-if-opened / clicked / replied conditions.'],
];
$capabilities = [
    'CSV & Google Sheet import with dedupe', 'Lists, tags, sectors & segments', 'Open, click, device & location tracking',
    'One-click unsubscribe (RFC 8058)', 'Per-hour throttling & scheduling', 'Reusable templates & merge tags',
    'Multi-SMTP rotation & failover', 'Bounce & suppression handling', 'Per-campaign & per-SMTP reports',
];
$steps = [
    ['n' => '1', 'title' => 'Connect your SMTP', 'text' => 'Add one or more sending accounts in seconds with provider presets.'],
    ['n' => '2', 'title' => 'Add your contacts', 'text' => 'Import a CSV, paste a Google Sheet link, or build segmented lists.'],
    ['n' => '3', 'title' => 'Design & send',     'text' => 'Pick a template, personalise with merge tags, then send now or schedule.'],
];
$providers = ['Gmail', 'Google Workspace', 'Amazon SES', 'Brevo', 'Mailgun', 'SendGrid', 'Custom SMTP'];
$faqs = [
    ['q' => 'Do you send emails for me?',          'a' => 'No — every email goes out through <em>your</em> connected SMTP account. We handle campaigns, scheduling, rotation, tracking and automation; your provider does the actual delivery.'],
    ['q' => 'Can I use Gmail or Google Workspace?', 'a' => 'Yes. Use a Gmail/Workspace app password (or any provider preset). You can connect multiple accounts and rotate between them.'],
    ['q' => 'Is there really a free plan?',         'a' => 'Yes — start free and upgrade when you need more contacts or monthly sends. No credit card required to begin.'],
    ['q' => 'Where does my data live?',             'a' => 'This is a self-hosted platform running on plain PHP + MySQL. Your contacts and campaigns stay in your own database.'],
];
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(Site::name()) ?> — Email marketing & automation on your own SMTP</title>
  <meta name="description" content="<?= e(Site::metaDescription() ?: (Site::name() . ' is an approachable, self-hosted email marketing & automation platform that sends through your own SMTP accounts — campaigns, Google Sheets mail-merge, rotation, tracking and automation.')) ?>">
  <?php if ($__k = Site::metaKeywords()): ?><meta name="keywords" content="<?= e($__k) ?>"><?php endif; ?>
  <link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{
      --green:#6d5efc; --green-d:#5b4ee8; --green-l:#8b5cf6;
      --mint:#f0eefe; --mint2:#e7e3fd;
      --ink:#0a2540; --muted:#51647a; --line:#e6ebf0;
      --yellow:#ffd34e; --pink:#ffe3ec; --blue:#e6f0ff; --sun:#fff6df;
      --dark:#0b1224; --dark-2:#141d39; --dark-line:rgba(255,255,255,.10); --dark-fg:#e8ecf7; --dark-muted:#9aa6c4;
    }
    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{ margin:0; font-family:"Montserrat",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; color:var(--ink); background:#fff; overflow-x:hidden; -webkit-font-smoothing:antialiased; }
    a{text-decoration:none; color:inherit}
    ul{margin:0;padding:0;list-style:none}
    .wrap{max-width:1180px;margin:0 auto;padding:0 22px}

    .reveal{opacity:0; transform:translateY(24px); transition:opacity .7s cubic-bezier(.2,.7,.2,1), transform .7s cubic-bezier(.2,.7,.2,1);}
    .reveal.in{opacity:1; transform:none}
    .reveal.d1{transition-delay:.07s}.reveal.d2{transition-delay:.14s}.reveal.d3{transition-delay:.21s}.reveal.d4{transition-delay:.28s}

    /* buttons */
    .btn{ display:inline-flex; align-items:center; gap:8px; font-weight:700; border-radius:10px; border:1.6px solid transparent; cursor:pointer; transition:transform .14s, box-shadow .14s, background .14s, color .14s, border-color .14s; }
    .btn-green{ background:var(--green); color:#fff; padding:12px 22px; box-shadow:0 12px 24px -12px rgba(109,94,252,.8); }
    .btn-green:hover{ background:var(--green-d); color:#fff; transform:translateY(-2px); box-shadow:0 18px 30px -12px rgba(109,94,252,.9); }
    .btn-ghost{ color:var(--ink); padding:11px 18px; }
    .btn-ghost:hover{ color:var(--green); }
    .btn-outline{ background:#fff; color:var(--ink); border-color:var(--line); padding:12px 22px; }
    .btn-outline:hover{ border-color:var(--green); color:var(--green); transform:translateY(-2px); }
    .btn-lg{ padding:15px 28px; font-size:1.04rem; }

    /* ===== Navbar ===== */
    .lnav{ position:sticky; top:0; z-index:1030; background:rgba(255,255,255,.9); backdrop-filter:saturate(180%) blur(12px); border-bottom:1px solid transparent; transition:border-color .3s, box-shadow .3s; }
    .lnav.scrolled{ border-bottom-color:var(--line); box-shadow:0 6px 22px -18px rgba(10,37,64,.5); }
    .lnav .inner{ display:flex; align-items:center; gap:18px; height:74px; }
    .lbrand{ display:flex; align-items:center; gap:11px; font-weight:900; font-size:1.25rem; letter-spacing:-.02em; }
    .lbrand .logo{ width:40px;height:40px;border-radius:12px;background:var(--green);display:grid;place-items:center;color:#fff;font-size:1.15rem; }
    .lmenu{ display:flex; align-items:center; gap:2px; margin-left:16px; }
    .lmenu a{ color:var(--ink); font-weight:600; font-size:.95rem; padding:10px 15px; border-radius:9px; transition:color .15s,background .15s; }
    .lmenu a:hover{ color:var(--green); background:var(--mint); }
    .lnav .actions{ margin-left:auto; display:flex; align-items:center; gap:8px; }
    .lburger{ display:none; margin-left:auto; border:1px solid var(--line); background:#fff; width:44px;height:44px;border-radius:12px; font-size:1.25rem; }

    /* ===== Hero ===== */
    .hero{ position:relative; padding:70px 0 24px; background:
        radial-gradient(60% 60% at 88% 0%, var(--mint2), transparent 60%),
        radial-gradient(50% 50% at 0% 30%, var(--sun), transparent 55%); }
    .hero-grid{ display:grid; grid-template-columns:1.05fr .95fr; gap:44px; align-items:center; }
    .badge-soft{ display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); color:var(--green-d); font-weight:700; font-size:.8rem; padding:7px 14px; border-radius:999px; box-shadow:0 8px 22px -16px rgba(10,37,64,.5); }
    .badge-soft .dot{ width:8px;height:8px;border-radius:50%;background:var(--green-l); box-shadow:0 0 0 0 rgba(139,92,246,.6); animation:pulse 2s infinite; }
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(139,92,246,.5)}70%{box-shadow:0 0 0 8px rgba(139,92,246,0)}100%{box-shadow:0 0 0 0 rgba(139,92,246,0)}}
    .hero h1{ font-size:clamp(1.9rem,3.4vw,2.8rem); font-weight:700; letter-spacing:-.02em; line-height:1.12; margin:20px 0 18px; }
    .hero h1 .u{ color:var(--green); position:relative; white-space:nowrap; }
    .hero h1 .u svg{ position:absolute; left:0; right:0; bottom:-10px; width:100%; height:14px; }
    .hero .lead{ color:var(--muted); font-size:1.18rem; line-height:1.6; max-width:34ch; margin:0 0 26px; }
    .signup{ display:flex; gap:10px; flex-wrap:wrap; max-width:520px; }
    .signup input{ flex:1; min-width:220px; border:1.6px solid var(--line); border-radius:11px; padding:14px 16px; font-size:1rem; font-family:inherit; outline:none; transition:border-color .15s, box-shadow .15s; }
    .signup input:focus{ border-color:var(--green); box-shadow:0 0 0 4px rgba(109,94,252,.14); }
    .trust-line{ display:flex; align-items:center; gap:18px; margin-top:18px; color:var(--muted); font-size:.86rem; flex-wrap:wrap; }
    .trust-line .ch{ color:var(--green); }
    .stars{ color:#ffb800; letter-spacing:1px; }

    /* hero visual */
    .hero-art{ position:relative; }
    .floaty{ animation:floaty 6s ease-in-out infinite; }
    @keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
    .card-ui{ background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 40px 80px -34px rgba(10,37,64,.4); overflow:hidden; }
    .card-ui .top{ display:flex; align-items:center; gap:7px; padding:13px 16px; border-bottom:1px solid var(--line); background:#fbfdfe; }
    .card-ui .top i{ width:11px;height:11px;border-radius:50%; }
    .card-ui .top .t{ margin-left:8px; font-weight:700; font-size:.82rem; color:var(--muted); }
    .card-ui .pad{ padding:18px; }
    .kpis{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
    .kpis .kpi{ border:1px solid var(--line); border-radius:13px; padding:13px; background:linear-gradient(180deg,#fff,#f8fdfb); }
    .kpis .v{ font-size:1.35rem; font-weight:900; letter-spacing:-.02em; color:var(--ink); }
    .kpis .l{ font-size:.7rem; color:var(--muted); }
    .chart{ height:118px; border-radius:13px; border:1px solid var(--line); background:linear-gradient(180deg,var(--mint),#fff); position:relative; overflow:hidden; }
    .chart svg{ position:absolute; inset:0; width:100%; height:100% }
    .chart path.line{ stroke-dasharray:1400; stroke-dashoffset:1400; animation:draw 2.4s ease .4s forwards; }
    @keyframes draw{to{stroke-dashoffset:0}}
    .chip{ position:absolute; background:#fff; border:1px solid var(--line); border-radius:13px; padding:11px 14px; box-shadow:0 20px 40px -20px rgba(10,37,64,.4); display:flex; align-items:center; gap:10px; font-weight:700; font-size:.84rem; }
    .chip .ic{ width:32px;height:32px;border-radius:9px;display:grid;place-items:center;color:#fff;font-size:.95rem; }
    .chip.c1{ top:-18px; left:-26px; } .chip.c2{ bottom:-20px; right:-22px; }
    .chip small{ display:block; color:var(--muted); font-weight:600; font-size:.72rem; }

    /* ===== Logos marquee ===== */
    .logos{ padding:30px 0 8px; }
    .logos .lbl{ text-align:center; color:var(--muted); font-size:.78rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; margin-bottom:16px; }
    .marq-mask{ overflow:hidden; -webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent); mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent); }
    .marq-track{ display:flex; gap:44px; width:max-content; animation:scroll 26s linear infinite; }
    .logos:hover .marq-track{ animation-play-state:paused; }
    .marq-item{ display:flex; align-items:center; gap:9px; color:#67768a; font-weight:800; font-size:1.02rem; white-space:nowrap; }
    .marq-item i{ color:var(--green); }
    @keyframes scroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}

    /* ===== Sections ===== */
    section{ padding:84px 0; }
    .sec-head{ text-align:center; max-width:680px; margin:0 auto 50px; }
    .eyebrow{ display:inline-block; color:var(--green); font-weight:800; text-transform:uppercase; letter-spacing:.12em; font-size:.76rem; margin-bottom:.5rem; }
    .sec-head h2{ font-size:clamp(1.5rem,2.6vw,2.05rem); font-weight:700; letter-spacing:-.015em; margin:0 0 .6rem; }
    .sec-head p{ color:var(--muted); font-size:1.08rem; margin:0; }

    /* product pillars */
    .pillars{ display:grid; grid-template-columns:repeat(2,1fr); gap:22px; }
    .pillar{ display:flex; gap:18px; padding:28px; border-radius:20px; border:1px solid var(--line); background:#fff; transition:transform .2s, box-shadow .2s; }
    .pillar:hover{ transform:translateY(-5px); box-shadow:0 28px 54px -28px rgba(10,37,64,.32); }
    .pillar .ic{ flex:0 0 58px; width:58px;height:58px;border-radius:16px;display:grid;place-items:center;font-size:1.5rem; }
    .tint-green{ background:var(--mint); color:var(--green-d); }
    .tint-blue{ background:var(--blue); color:#2563eb; }
    .tint-yellow{ background:var(--sun); color:#b7791f; }
    .tint-pink{ background:var(--pink); color:#db2777; }
    .pillar h3{ font-size:1.18rem; font-weight:700; margin:2px 0 7px; }
    .pillar p{ color:var(--muted); font-size:.95rem; line-height:1.6; margin:0; }
    .pillar .more{ color:var(--green); font-weight:700; font-size:.9rem; margin-top:10px; display:inline-block; }

    /* feature split */
    .bg-mint{ background:linear-gradient(180deg,var(--mint),#fff); }
    .split{ display:grid; grid-template-columns:1fr 1fr; gap:50px; align-items:center; }
    .split h2{ font-size:clamp(1.4rem,2.4vw,1.85rem); font-weight:700; letter-spacing:-.015em; margin:0 0 14px; }
    .split p.s{ color:var(--muted); font-size:1.06rem; line-height:1.65; margin:0 0 22px; }
    .checks{ display:grid; grid-template-columns:1fr 1fr; gap:12px 18px; }
    .checks li{ display:flex; gap:9px; align-items:flex-start; font-size:.93rem; color:#33485e; font-weight:500; }
    .checks li i{ color:var(--green); margin-top:2px; }
    .art-card{ background:#fff; border:1px solid var(--line); border-radius:20px; padding:20px; box-shadow:0 36px 70px -34px rgba(10,37,64,.35); }
    .art-row{ display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--line); border-radius:13px; margin-bottom:10px; background:#fff; }
    .art-row .av{ width:38px;height:38px;border-radius:10px;display:grid;place-items:center;color:#fff;font-weight:800; }
    .art-row .nm{ font-weight:700; font-size:.9rem; } .art-row .sb{ color:var(--muted); font-size:.78rem; }
    .art-row .pill2{ margin-left:auto; font-size:.72rem; font-weight:800; padding:4px 10px; border-radius:999px; }

    /* steps */
    .steps{ display:grid; grid-template-columns:repeat(3,1fr); gap:26px; position:relative; }
    .steps::before{ content:""; position:absolute; top:28px; left:16%; right:16%; height:2px; background:repeating-linear-gradient(90deg,var(--green) 0 8px,transparent 8px 16px); opacity:.4; }
    .step{ text-align:center; position:relative; }
    .step .num{ width:58px;height:58px;border-radius:50%;background:var(--green);color:#fff;font-weight:900;font-size:1.4rem;display:grid;place-items:center;margin:0 auto 16px; box-shadow:0 14px 26px -12px rgba(109,94,252,.8); position:relative; z-index:1; }
    .step h3{ font-size:1.12rem; font-weight:700; margin:0 0 6px; }
    .step p{ color:var(--muted); font-size:.95rem; margin:0; }

    /* stats band */
    .statband{ background:var(--green); border-radius:26px; padding:46px 24px; color:#fff; }
    .statband .row3{ display:grid; grid-template-columns:repeat(3,1fr); gap:24px; text-align:center; }
    .statband .v{ font-size:clamp(2.1rem,5vw,3rem); font-weight:800; letter-spacing:-.03em; }
    .statband .l{ opacity:.9; font-size:.96rem; margin-top:4px; }

    /* testimonial */
    .quote{ max-width:820px; margin:0 auto; text-align:center; }
    .quote .stars{ font-size:1.2rem; }
    .quote blockquote{ font-size:clamp(1.15rem,2.2vw,1.55rem); font-weight:600; letter-spacing:-.02em; line-height:1.4; margin:16px 0 22px; }
    .quote .who{ display:flex; align-items:center; justify-content:center; gap:12px; }
    .quote .who .av{ width:46px;height:46px;border-radius:50%;background:var(--green);color:#fff;display:grid;place-items:center;font-weight:800; }
    .quote .who .nm{ font-weight:800; } .quote .who .rl{ color:var(--muted); font-size:.86rem; }

    /* pricing */
    .pgrid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:22px; max-width:1000px; margin:0 auto; align-items:stretch; }
    .pcard{ position:relative; background:#fff; border:1px solid var(--line); border-radius:20px; padding:30px; display:flex; flex-direction:column; transition:transform .2s, box-shadow .2s; }
    .pcard:hover{ transform:translateY(-5px); box-shadow:0 30px 56px -28px rgba(10,37,64,.3); }
    .pcard.feat{ border:2px solid var(--green); box-shadow:0 30px 60px -28px rgba(109,94,252,.5); }
    .pcard .tag{ position:absolute; top:-13px; left:50%; transform:translateX(-50%); background:var(--green); color:#fff; font-size:.72rem; font-weight:800; padding:6px 15px; border-radius:999px; }
    .pcard h3{ font-size:1.18rem; font-weight:700; margin:0 0 2px; }
    .pcard .ptag{ color:var(--muted); font-size:.86rem; margin-bottom:14px; }
    .pcard .price{ font-size:2.3rem; font-weight:900; letter-spacing:-.02em; }
    .pcard .price small{ font-size:.85rem; font-weight:600; color:var(--muted); }
    .pcard ul{ margin:18px 0 22px; display:flex; flex-direction:column; gap:10px; }
    .pcard li{ font-size:.9rem; color:#374151; display:flex; gap:9px; align-items:flex-start; }
    .pcard li i{ color:var(--green); margin-top:2px; }
    .pcard li.no{ color:#9aa0b5; } .pcard li.no i{ color:#cbd1de; }
    .pcard .cta-btn{ margin-top:auto; justify-content:center; }

    /* faq */
    .faq{ max-width:800px; margin:0 auto; }
    .faq details{ border:1px solid var(--line); border-radius:14px; padding:2px 20px; margin-bottom:12px; background:#fff; transition:border-color .2s, box-shadow .2s; }
    .faq details[open]{ border-color:rgba(109,94,252,.45); box-shadow:0 18px 34px -26px rgba(10,37,64,.4); }
    .faq summary{ list-style:none; cursor:pointer; font-weight:700; padding:18px 0; display:flex; justify-content:space-between; align-items:center; gap:14px; }
    .faq summary::-webkit-details-marker{ display:none; }
    .faq summary .chev{ transition:transform .25s; color:var(--green); flex:0 0 auto; }
    .faq details[open] summary .chev{ transform:rotate(180deg); }
    .faq p{ color:var(--muted); line-height:1.65; margin:0 0 18px; }

    /* cta + footer */
    .cta-box{ position:relative; overflow:hidden; background:var(--green); border-radius:28px; padding:60px 24px; color:#fff; text-align:center; box-shadow:0 50px 90px -38px rgba(109,94,252,.85); }
    .cta-box::before{ content:""; position:absolute; inset:0; background:radial-gradient(circle at 18% 20%,rgba(255,255,255,.18),transparent 40%),radial-gradient(circle at 85% 90%,rgba(139,92,246,.5),transparent 45%); }
    .cta-box > *{ position:relative; }
    .cta-box h2{ font-size:clamp(1.5rem,2.8vw,2.05rem); font-weight:700; letter-spacing:-.015em; margin:0 0 12px; }
    .cta-box p{ opacity:.95; font-size:1.1rem; margin:0 0 26px; }
    .btn-white{ background:#fff; color:var(--green-d); }
    .btn-white:hover{ background:#f1fff9; color:var(--green-d); transform:translateY(-2px); }

    footer{ background:#06231a; color:#9fc4b6; padding:56px 0 30px; }
    footer .fgrid{ display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:30px; margin-bottom:34px; }
    footer h4{ color:#fff; font-size:.82rem; text-transform:uppercase; letter-spacing:.1em; margin:0 0 14px; }
    footer a{ display:block; color:#9fc4b6; font-size:.92rem; padding:5px 0; transition:color .15s; }
    footer a:hover{ color:#fff; }
    footer .fbrand{ display:flex; align-items:center; gap:11px; font-weight:900; font-size:1.2rem; color:#fff; margin-bottom:12px; }
    footer .fbrand .logo{ width:36px;height:36px;border-radius:10px;background:var(--green);display:grid;place-items:center;color:#fff; }
    footer .fbot{ border-top:1px solid rgba(255,255,255,.1); padding-top:20px; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; font-size:.86rem; }

    /* ===== Dark sections (alternating bands) ===== */
    .sec-dark{ background:var(--dark); color:var(--dark-fg); position:relative; }
    .sec-dark .sec-head h2, .sec-dark h2, .sec-dark h3{ color:#fff; }
    .sec-dark .sec-head p, .sec-dark .split p.s{ color:var(--dark-muted); }
    .sec-dark .eyebrow{ color:#a78bfa; }
    /* feature split on dark */
    .sec-dark .checks li{ color:#c7d0e6; }
    .sec-dark .checks li i{ color:#a78bfa; }
    .sec-dark .art-card{ background:var(--dark-2); border-color:var(--dark-line); box-shadow:0 36px 70px -34px rgba(0,0,0,.7); }
    .sec-dark .art-row{ background:rgba(255,255,255,.03); border-color:var(--dark-line); }
    .sec-dark .art-row .nm{ color:#eef1f8; }
    .sec-dark .art-row .sb{ color:var(--dark-muted); }
    /* testimonial on dark */
    .sec-dark .quote blockquote{ color:#fff; }
    .sec-dark .quote .who .nm{ color:#fff; }
    .sec-dark .quote .who .rl{ color:var(--dark-muted); }
    /* faq on dark */
    .sec-dark .faq details{ background:var(--dark-2); border-color:var(--dark-line); }
    .sec-dark .faq details[open]{ border-color:rgba(167,139,250,.5); box-shadow:0 18px 34px -26px rgba(0,0,0,.6); }
    .sec-dark .faq summary{ color:#fff; }
    .sec-dark .faq summary .chev{ color:#a78bfa; }
    .sec-dark .faq p{ color:var(--dark-muted); }

    @media (max-width:900px){
      .hero-grid,.split,.pillars{ grid-template-columns:1fr; } .split{ gap:30px; }
      .hero-art{ margin-top:18px; } .checks{ grid-template-columns:1fr; }
      footer .fgrid{ grid-template-columns:1fr 1fr; }
    }
    @media (max-width:860px){
      .lmenu{ display:none; } .lburger{ display:inline-block; }
      .lnav.open .lmenu{ display:flex; flex-direction:column; align-items:stretch; position:absolute; top:74px; left:0; right:0; background:#fff; border-bottom:1px solid var(--line); padding:10px 16px; gap:2px; box-shadow:0 20px 40px -24px rgba(10,37,64,.4); }
      .steps{ grid-template-columns:1fr; } .steps::before{ display:none; }
      .statband .row3{ grid-template-columns:1fr; }
    }
    @media (max-width:575px){ .d-none-sm{display:none} footer .fgrid{grid-template-columns:1fr} }
    @media (prefers-reduced-motion: reduce){
      *{ animation:none !important; transition:none !important; }
      .reveal{ opacity:1; transform:none; } .chart path.line{ stroke-dashoffset:0; }
    }
  </style>
</head>
<body>

  <!-- ===== Nav ===== -->
  <nav class="lnav" id="lnav">
    <div class="wrap inner">
      <a class="lbrand" href="<?= url('') ?>"><?php if (Site::showMark()): $__hl = Site::logoUrl(); ?><?php if ($__hl): ?><img class="logo" src="<?= e($__hl) ?>" alt="<?= e(Site::name()) ?>" style="object-fit:contain;background:transparent"><?php else: ?><span class="logo"><i class="bi bi-send-fill"></i></span><?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></a>
      <div class="lmenu">
        <a href="#products">Platform</a>
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
      </div>
      <button class="lburger" id="lburger" aria-label="Menu"><i class="bi bi-list"></i></button>
      <div class="actions">
        <a class="btn btn-ghost d-none-sm" href="<?= url('login') ?>">Log in</a>
        <a class="btn btn-green" href="<?= url('register') ?>">Sign up free</a>
      </div>
    </div>
  </nav>

  <!-- ===== Hero ===== -->
  <header class="hero">
    <div class="wrap hero-grid">
      <div>
        <span class="badge-soft reveal in"><span class="dot"></span> Sends through your own SMTP — never a shared pool</span>
        <h1 class="reveal in d1">The approachable platform to grow with
          <span class="u">email marketing<svg viewBox="0 0 200 14" preserveAspectRatio="none"><path d="M2 9 C 50 2, 150 2, 198 8" fill="none" stroke="#8b5cf6" stroke-width="4" stroke-linecap="round"/></svg></span>
        </h1>
        <p class="lead reveal in d2">Campaigns, Google&nbsp;Sheets mail-merge, multi-SMTP rotation, tracking and automation — all sent through the accounts you already own.</p>
        <form class="signup reveal in d3" method="get" action="<?= url('register') ?>">
          <input type="email" name="email" placeholder="Enter your email" aria-label="Email" required>
          <button class="btn btn-green btn-lg" type="submit">Sign up free <i class="bi bi-arrow-right"></i></button>
        </form>
        <div class="trust-line reveal in d4">
          <span><i class="bi bi-check-circle-fill ch"></i> No credit card required</span>
          <span><i class="bi bi-check-circle-fill ch"></i> Free plan included</span>
          <span class="stars">★★★★★ <span style="color:var(--muted)">loved by senders</span></span>
        </div>
      </div>

      <div class="hero-art reveal in d2">
        <div class="floaty" style="position:relative">
          <div class="chip c1"><span class="ic" style="background:#8b5cf6"><i class="bi bi-envelope-open"></i></span><span>38.2%<small>Open rate</small></span></div>
          <div class="chip c2"><span class="ic" style="background:#2563eb"><i class="bi bi-cursor"></i></span><span>9.6%<small>Click rate</small></span></div>
          <div class="card-ui">
            <div class="top"><i style="background:#ff5f57"></i><i style="background:#febc2e"></i><i style="background:#28c840"></i><span class="t">Dashboard</span></div>
            <div class="pad">
              <div class="kpis">
                <div class="kpi"><div class="v"><span class="count" data-to="12480">0</span></div><div class="l">Emails sent</div></div>
                <div class="kpi"><div class="v"><span class="count" data-to="4820">0</span></div><div class="l">Contacts</div></div>
                <div class="kpi"><div class="v"><span class="count" data-to="98" data-dec="0">0</span>%</div><div class="l">Delivered</div></div>
              </div>
              <div class="chart">
                <svg viewBox="0 0 600 118" preserveAspectRatio="none">
                  <defs><linearGradient id="lg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6d5efc" stop-opacity=".3"/><stop offset="1" stop-color="#6d5efc" stop-opacity="0"/></linearGradient></defs>
                  <path d="M0,92 L60,78 L120,84 L180,54 L240,60 L300,40 L360,46 L420,26 L480,32 L540,16 L600,22 L600,118 L0,118 Z" fill="url(#lg)"/>
                  <path class="line" d="M0,92 L60,78 L120,84 L180,54 L240,60 L300,40 L360,46 L420,26 L480,32 L540,16 L600,22" fill="none" stroke="#6d5efc" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ===== Logos ===== -->
  <div class="logos">
    <div class="wrap">
      <div class="lbl">Works with the SMTP you already use</div>
      <div class="marq-mask">
        <div class="marq-track">
          <?php for ($k = 0; $k < 2; $k++): foreach ($providers as $pv): ?>
            <span class="marq-item"><i class="bi bi-hdd-network-fill"></i> <?= e($pv) ?></span>
          <?php endforeach; endfor; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Product pillars ===== -->
  <section id="products">
    <div class="wrap">
      <div class="sec-head reveal">
        <span class="eyebrow">One platform</span>
        <h2>Everything you need to send, in one place</h2>
        <p>A complete marketing suite — running on your own sending infrastructure.</p>
      </div>
      <div class="pillars">
        <?php foreach ($pillars as $i => $p): ?>
          <div class="pillar reveal d<?= ($i % 2) + 1 ?>">
            <div class="ic tint-<?= e($p['tint']) ?>"><i class="bi bi-<?= e($p['bi']) ?>"></i></div>
            <div>
              <h3><?= e($p['title']) ?></h3>
              <p><?= e($p['text']) ?></p>
              <a class="more" href="<?= url('register') ?>">Get started <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== Feature split ===== -->
  <section id="features" class="sec-dark">
    <div class="wrap split">
      <div class="reveal">
        <span class="eyebrow">Built for deliverability</span>
        <h2>Send smarter from the accounts you trust</h2>
        <p class="s">Rotate across multiple SMTP accounts, personalise every email with merge tags from your lists or a Google Sheet, and track exactly what happens after you hit send.</p>
        <ul class="checks">
          <?php foreach ($capabilities as $c): ?>
            <li><i class="bi bi-check-circle-fill"></i> <?= e($c) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="reveal d2">
        <div class="art-card floaty">
          <div class="art-row"><span class="av" style="background:#8b5cf6">A</span><div><div class="nm">Welcome campaign</div><div class="sb">via Gmail SMTP</div></div><span class="pill2" style="background:var(--mint);color:var(--green-d)">Sent</span></div>
          <div class="art-row"><span class="av" style="background:#2563eb">B</span><div><div class="nm">Sheet mail-merge</div><div class="sb">2,480 recipients</div></div><span class="pill2" style="background:var(--blue);color:#2563eb">Running</span></div>
          <div class="art-row"><span class="av" style="background:#db2777">C</span><div><div class="nm">Drip follow-up</div><div class="sb">stop if replied</div></div><span class="pill2" style="background:var(--pink);color:#db2777">Scheduled</span></div>
          <div class="art-row" style="margin-bottom:0"><span class="av" style="background:#b7791f">D</span><div><div class="nm">Re-engagement</div><div class="sb">via SES rotation</div></div><span class="pill2" style="background:var(--sun);color:#b7791f">Draft</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== How it works ===== -->
  <section id="how">
    <div class="wrap">
      <div class="sec-head reveal">
        <span class="eyebrow">Get going in minutes</span>
        <h2>How it works</h2>
        <p>From zero to your first campaign in three simple steps.</p>
      </div>
      <div class="steps">
        <?php foreach ($steps as $i => $s): ?>
          <div class="step reveal d<?= $i + 1 ?>">
            <div class="num"><?= e($s['n']) ?></div>
            <h3><?= e($s['title']) ?></h3>
            <p><?= e($s['text']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== Stats band ===== -->
  <section style="padding:30px 0 84px">
    <div class="wrap">
      <div class="statband reveal">
        <div class="row3">
          <div><div class="v"><span class="count" data-to="99.9" data-dec="1">0</span>%</div><div class="l">Delivery on your own sending reputation</div></div>
          <div><div class="v"><span class="count" data-to="7">0</span>+</div><div class="l">SMTP providers &amp; presets supported</div></div>
          <div><div class="v">0</div><div class="l">Emails ever sent through a shared pool</div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Testimonial ===== -->
  <section class="sec-dark" style="padding:84px 0">
    <div class="wrap">
      <div class="quote reveal">
        <div class="stars" style="color:#ffb800">★★★★★</div>
        <blockquote>“We moved off our shared-pool tool and now every email goes through our own Workspace and SES accounts. Deliverability went up and the cost went down.”</blockquote>
        <div class="who"><span class="av">RK</span><div style="text-align:left"><div class="nm">Marketing lead</div><div class="rl">B2B events company</div></div></div>
      </div>
    </div>
  </section>

  <!-- ===== Pricing ===== -->
  <?php if (!empty($plans)): ?>
  <section id="pricing" class="bg-mint">
    <div class="wrap">
      <div class="sec-head reveal">
        <span class="eyebrow">Simple pricing</span>
        <h2>Plans that scale with you</h2>
        <p>Start free, upgrade when you grow. Every plan sends through your own SMTP.</p>
      </div>
      <div class="pgrid">
        <?php foreach ($plans as $i => $p): $feat = (int) ($p['is_featured'] ?? 0) === 1; $price = (float) $p['price_monthly']; ?>
          <div class="pcard reveal d<?= $i + 1 ?> <?= $feat ? 'feat' : '' ?>">
            <?php if ($feat): ?><span class="tag">Most popular</span><?php endif; ?>
            <h3><?= e($p['name']) ?></h3>
            <div class="ptag"><?= e($p['tagline'] ?? '') ?></div>
            <div class="price">
              <?= $price > 0 ? e(BILLING_CURRENCY) . ' ' . number_format($price) : 'Free' ?>
              <?php if ($price > 0): ?><small>/ <?= e($p['price_period'] ?? 'month') ?></small><?php endif; ?>
            </div>
            <ul>
              <?php foreach (array_slice(Plan::featureList($p), 0, 6) as $row): ?>
                <li class="<?= $row['included'] ? '' : 'no' ?>">
                  <i class="bi bi-<?= $row['included'] ? 'check-circle-fill' : 'x-circle' ?>"></i>
                  <span><?= e($row['label']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <a class="btn cta-btn <?= $feat ? 'btn-green' : 'btn-outline' ?>" href="<?= url('register') ?>">
              <?= e($p['cta_label'] ?: 'Get started') ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===== FAQ ===== -->
  <section id="faq" class="sec-dark">
    <div class="wrap">
      <div class="sec-head reveal">
        <span class="eyebrow">Questions</span>
        <h2>Frequently asked</h2>
      </div>
      <div class="faq">
        <?php foreach ($faqs as $i => $f): ?>
          <details class="reveal" <?= $i === 0 ? 'open' : '' ?>>
            <summary><?= e($f['q']) ?> <span class="chev"><i class="bi bi-chevron-down"></i></span></summary>
            <p><?= $f['a'] ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== CTA ===== -->
  <section style="padding:20px 0 90px">
    <div class="wrap">
      <div class="cta-box reveal">
        <h2>Ready to send smarter?</h2>
        <p>Create your free account and launch your first campaign today.</p>
        <a class="btn btn-white btn-lg" href="<?= url('register') ?>"><i class="bi bi-rocket-takeoff"></i> Sign up free</a>
      </div>
    </div>
  </section>

  <!-- ===== Footer ===== -->
  <footer>
    <div class="wrap">
      <div class="fgrid">
        <div>
          <div class="fbrand"><?php if (Site::showMark()): $__fl = Site::logoUrl(); ?><?php if ($__fl): ?><img class="logo" src="<?= e($__fl) ?>" alt="<?= e(Site::name()) ?>" style="object-fit:contain;background:transparent"><?php else: ?><span class="logo"><i class="bi bi-send-fill"></i></span><?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></div>
          <p style="font-size:.92rem;line-height:1.6;max-width:30ch;margin:0">The approachable, self-hosted email platform that sends through your own SMTP.</p>
        </div>
        <div>
          <h4>Product</h4>
          <a href="#features">Features</a>
          <a href="#pricing">Pricing</a>
          <a href="#how">How it works</a>
          <a href="<?= url('register') ?>">Sign up free</a>
        </div>
        <div>
          <h4>Company</h4>
          <a href="#faq">FAQ</a>
          <a href="<?= url('login') ?>">Log in</a>
          <a href="<?= url('register') ?>">Get started</a>
        </div>
        <div>
          <h4>Legal</h4>
          <a href="<?= url('legal/terms') ?>">Terms of Service</a>
          <a href="<?= url('legal/privacy') ?>">Privacy Policy</a>
          <a href="<?= url('legal/acceptable-use') ?>">Acceptable Use</a>
        </div>
      </div>
      <div class="fbot">
        <span>© <?= date('Y') ?> <?= e(Site::name()) ?>. All rights reserved.</span>
        <span>Sends through your own SMTP · Self-hosted</span>
      </div>
    </div>
  </footer>

  <script>
  (function(){
    var nav = document.getElementById('lnav');
    var onScroll = function(){ nav.classList.toggle('scrolled', window.scrollY > 12); };
    onScroll(); window.addEventListener('scroll', onScroll, {passive:true});
    document.getElementById('lburger').addEventListener('click', function(){ nav.classList.toggle('open'); });
    document.querySelectorAll('.lmenu a').forEach(function(a){ a.addEventListener('click', function(){ nav.classList.remove('open'); }); });

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var revs = document.querySelectorAll('.reveal:not(.in)');
    if (reduce || !('IntersectionObserver' in window)) { revs.forEach(function(el){ el.classList.add('in'); }); }
    else {
      var io = new IntersectionObserver(function(es){ es.forEach(function(en){ if(en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); } }); }, {threshold:.14, rootMargin:'0px 0px -8% 0px'});
      revs.forEach(function(el){ io.observe(el); });
    }

    function animateCount(el){
      var to = parseFloat(el.dataset.to||'0'), dec = parseInt(el.dataset.dec||'0',10);
      if (reduce){ el.textContent = to.toLocaleString(undefined,{minimumFractionDigits:dec,maximumFractionDigits:dec}); return; }
      var start=null, dur=1500;
      function tick(t){ if(!start)start=t; var p=Math.min((t-start)/dur,1), e=1-Math.pow(1-p,3); el.textContent=(to*e).toLocaleString(undefined,{minimumFractionDigits:dec,maximumFractionDigits:dec}); if(p<1)requestAnimationFrame(tick); }
      requestAnimationFrame(tick);
    }
    var counts=document.querySelectorAll('.count');
    if(!('IntersectionObserver' in window)){ counts.forEach(animateCount); }
    else { var co=new IntersectionObserver(function(es){ es.forEach(function(en){ if(en.isIntersecting){ animateCount(en.target); co.unobserve(en.target); } }); }, {threshold:.6}); counts.forEach(function(el){ co.observe(el); }); }
  })();
  </script>
</body>
</html>
