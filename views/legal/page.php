<?php /** @var string $doc @var string $docTitle */ $today = date('F j, Y'); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($docTitle) ?> · <?= e(Site::name()) ?></title>
  <link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
  <script>(function(){var t=localStorage.getItem('mg-theme')||'light';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
  <style>
    body{background:var(--bs-tertiary-bg); font-family:"Montserrat",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
    .legal{max-width:820px}
    .legal h2{font-size:1.15rem;margin-top:1.8rem;color:var(--bs-body-color)}
    .legal p,.legal li{color:var(--bs-secondary-color);line-height:1.7}
    .legal-card{background:var(--bs-body-bg);border:1px solid var(--bs-border-color)}
    .legal-nav{background:var(--bs-body-bg)}
    .legal-brand{color:var(--bs-body-color)!important}
    .legal-brand i{color:var(--brand)}
  </style>
</head>
<body>
  <nav class="navbar legal-nav border-bottom">
    <div class="container d-flex align-items-center gap-2">
      <a class="navbar-brand fw-bold legal-brand me-auto" href="<?= url('') ?>"><?php if (Site::showMark()): $__ll = Site::logoUrl(); ?><?php if ($__ll): ?><img src="<?= e($__ll) ?>" alt="<?= e(Site::name()) ?>" style="height:26px;width:auto;vertical-align:middle"> <?php else: ?><i class="bi bi-send-fill"></i> <?php endif; ?><?php endif; ?><?php if (Site::showName()): ?><?= e(Site::name()) ?><?php endif; ?></a>
      <button type="button" class="icon-btn" id="legalThemeToggle" title="Toggle theme" aria-label="Toggle theme"><i class="bi bi-moon-stars-fill"></i></button>
      <a class="btn btn-sm btn-outline-primary" href="<?= url('login') ?>">Sign in</a>
    </div>
  </nav>

  <div class="container my-5">
    <div class="legal legal-card mx-auto rounded-4 shadow-sm p-4 p-md-5">
      <h1 class="h3 mb-1"><?= e($docTitle) ?></h1>
      <p class="text-muted small">Last updated: <?= e($today) ?></p>
      <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i> This is a starter template provided for convenience. Have it reviewed by a qualified lawyer before relying on it for your business.</div>

      <?php if ($doc === 'terms'): ?>
        <p>These Terms of Service ("Terms") govern your use of <?= e(Site::name()) ?> (the "Service"). By creating an account you agree to these Terms.</p>
        <h2>1. The Service</h2>
        <p><?= e(Site::name()) ?> is an email marketing platform that lets you manage contacts and send campaigns through your own connected SMTP/email accounts. We do not send email on your behalf from our own infrastructure; you are responsible for the sending accounts you connect.</p>
        <h2>2. Your account</h2>
        <p>You must provide accurate information, keep your password secure, and are responsible for all activity under your account. You must be at least 18 years old.</p>
        <h2>3. Acceptable use</h2>
        <p>Your use of the Service is also governed by our <a href="<?= url('legal/acceptable-use') ?>">Acceptable Use Policy</a>. Sending unsolicited bulk email (spam) is strictly prohibited and may result in immediate termination.</p>
        <h2>4. Your content &amp; contacts</h2>
        <p>You retain ownership of your contacts and content. You represent that you have consent to email every contact you upload, and that your messages comply with applicable laws (including CAN-SPAM, CASL, and GDPR where relevant).</p>
        <h2>5. Plans &amp; payment</h2>
        <p>Paid plans are billed in advance for the period selected and are non-refundable except where required by law. We may change pricing with reasonable notice. Plan limits (contacts, emails, etc.) are enforced as described on the Billing page.</p>
        <h2>6. Termination</h2>
        <p>You may cancel at any time. We may suspend or terminate accounts that violate these Terms or the Acceptable Use Policy. On termination your access ends and your data may be deleted after a reasonable period.</p>
        <h2>7. Disclaimer &amp; liability</h2>
        <p>The Service is provided "as is" without warranties of any kind. We are not liable for indirect or consequential damages, or for deliverability outcomes, which depend on your sending accounts, content and recipient providers. Our total liability is limited to the fees you paid in the prior 3 months.</p>
        <h2>8. Changes</h2>
        <p>We may update these Terms; continued use after changes constitutes acceptance.</p>
        <h2>9. Contact</h2>
        <p>Questions? Contact us at <a href="mailto:legal@example.com">legal@example.com</a>.</p>

      <?php elseif ($doc === 'privacy'): ?>
        <p>This Privacy Policy explains how <?= e(Site::name()) ?> ("we") collects, uses and protects information when you use the Service.</p>
        <h2>1. Information we collect</h2>
        <ul>
          <li><strong>Account data:</strong> your name, email, company and password (stored hashed).</li>
          <li><strong>Sending credentials:</strong> SMTP/IMAP usernames and passwords you connect, stored <strong>encrypted at rest</strong> (AES-256).</li>
          <li><strong>Contact data:</strong> the contacts you upload (email, name, company, etc.), processed on your behalf.</li>
          <li><strong>Usage &amp; tracking:</strong> campaign opens/clicks, IP, device and browser of recipients (when tracking is enabled by you).</li>
        </ul>
        <h2>2. How we use it</h2>
        <p>To provide and operate the Service, process your campaigns, enforce plan limits, prevent abuse, and communicate with you about your account.</p>
        <h2>3. Your role as data controller</h2>
        <p>For the contacts you upload, you are the data controller and we are a processor acting on your instructions. You are responsible for having a lawful basis (e.g. consent) to contact them and for honouring unsubscribe and data requests.</p>
        <h2>4. Sharing</h2>
        <p>We do not sell your data. Email is delivered through the sending accounts <em>you</em> connect. We use your hosting/database provider to operate the Service.</p>
        <h2>5. Security</h2>
        <p>We use encryption for credentials, hashed passwords, CSRF protection and access controls. No system is perfectly secure; you use the Service at your own risk.</p>
        <h2>6. Retention</h2>
        <p>We keep data while your account is active and for a reasonable period afterwards, then delete or anonymise it.</p>
        <h2>7. Your rights</h2>
        <p>Depending on your jurisdiction you may have rights to access, correct, export or delete your data. Contact <a href="mailto:privacy@example.com">privacy@example.com</a>.</p>
        <h2>8. Cookies</h2>
        <p>We use a session cookie to keep you signed in. Recipient tracking uses a pixel/redirects only when you enable tracking on a campaign.</p>

      <?php else: /* aup */ ?>
        <p>This Acceptable Use Policy ("AUP") applies to everyone using <?= e(Site::name()) ?>. Violations may result in suspension or termination.</p>
        <h2>1. No spam</h2>
        <p>You may only email contacts who have <strong>given you permission</strong> to contact them. Purchased, scraped, rented or harvested lists are prohibited.</p>
        <h2>2. Required by law</h2>
        <ul>
          <li>Every campaign must include a working <strong>unsubscribe</strong> link and your <strong>physical postal address</strong> (we add these automatically — keep your address set in Settings).</li>
          <li>Honour unsubscribe requests promptly; do not email contacts who opted out.</li>
          <li>Do not use misleading subject lines or forged headers.</li>
        </ul>
        <h2>3. Prohibited content</h2>
        <p>No illegal, fraudulent, deceptive, hateful, or malicious content; no malware or phishing; no content that infringes others' rights.</p>
        <h2>4. Prohibited industries / behaviour</h2>
        <p>High-abuse categories (e.g. illegal substances, "get rich quick", pirated material) and any activity that harms deliverability for other users are not allowed.</p>
        <h2>5. Enforcement</h2>
        <p>We may investigate suspected violations and suspend accounts to protect the Service and recipients. Repeated or serious violations result in termination.</p>
        <h2>6. Reporting</h2>
        <p>Report abuse to <a href="mailto:abuse@example.com">abuse@example.com</a>.</p>
      <?php endif; ?>

      <hr class="my-4">
      <div class="d-flex gap-3 small">
        <a href="<?= url('legal/terms') ?>">Terms</a>
        <a href="<?= url('legal/privacy') ?>">Privacy</a>
        <a href="<?= url('legal/acceptable-use') ?>">Acceptable Use</a>
      </div>
    </div>
  </div>
  <script>
    (function () {
      var btn = document.getElementById('legalThemeToggle');
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
