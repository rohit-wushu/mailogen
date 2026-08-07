<?php /** @var bool $enabled @var string $host @var int $port @var string $encryption @var string $username @var bool $hasPassword @var string $fromEmail @var string $fromName */ ?>
<div class="page-head">
  <div>
    <h2 class="mb-1">System Email</h2>
    <p class="text-muted mb-0">Dedicated credentials for transactional mail — verification links, password resets, team invites. Without this, the platform borrows whichever tenant SMTP account has the lowest priority, which breaks silently if that account changes.</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <form method="post" action="<?= url('admin/mail/store') ?>" id="sysMailForm">
      <?= csrf_field() ?>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-envelope-paper-fill me-1"></i> SMTP credentials</span>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" name="sys_smtp_enabled" id="sysEnabled" value="1" <?= $enabled ? 'checked' : '' ?>>
            <label class="form-check-label small" for="sysEnabled">Enabled</label>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Host</label>
              <input class="form-control" name="host" value="<?= e($host) ?>" placeholder="smtp.gmail.com" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Port</label>
              <input type="number" class="form-control" name="port" value="<?= (int) $port ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Encryption</label>
              <select class="form-select" name="encryption">
                <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $v => $lbl): ?>
                  <option value="<?= $v ?>" <?= $encryption === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input class="form-control" name="username" value="<?= e($username) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password <?= $hasPassword ? '<span class="text-muted small">(saved — leave blank to keep it)</span>' : '' ?></label>
              <input type="password" class="form-control" name="password" placeholder="<?= $hasPassword ? '••••••••' : '' ?>" autocomplete="new-password">
            </div>
            <div class="col-md-6">
              <label class="form-label">From email</label>
              <input type="email" class="form-control" name="from_email" value="<?= e($fromEmail) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">From name</label>
              <input class="form-control" name="from_name" value="<?= e($fromName) ?>" placeholder="<?= e(APP_NAME) ?>">
            </div>
          </div>
        </div>
        <div class="card-footer">
          <div class="d-flex gap-2 mb-2">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Save settings</button>
            <button type="button" class="btn btn-outline-secondary" id="sysMailTestBtn"><i class="bi bi-send"></i> Send test email</button>
          </div>
          <div id="sysMailTestResult"></div>
        </div>
      </div>
    </form>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-info-circle me-1"></i> How this is used</div>
      <div class="card-body small text-muted">
        <p>When <strong>Enabled</strong> is on and these credentials work, every transactional email — signup verification, password reset, team invite — sends through this account.</p>
        <p class="mb-0">If disabled (or a send fails), the platform falls back to the lowest-priority enabled SMTP account across all tenants, then to PHP's <code>mail()</code>.</p>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('sysMailTestBtn').addEventListener('click', function () {
  const btn = this;
  const r = document.getElementById('sysMailTestResult');
  const data = {
    _csrf: window.CSRF,
    host: document.querySelector('#sysMailForm [name=host]').value,
    port: document.querySelector('#sysMailForm [name=port]').value,
    encryption: document.querySelector('#sysMailForm [name=encryption]').value,
    username: document.querySelector('#sysMailForm [name=username]').value,
    password: document.querySelector('#sysMailForm [name=password]').value,
    from_email: document.querySelector('#sysMailForm [name=from_email]').value,
    from_name: document.querySelector('#sysMailForm [name=from_name]').value
  };
  if (!data.host || !data.username) {
    r.innerHTML = '<div class="alert alert-warning py-2 mb-0">Enter host and username first.</div>';
    return;
  }
  btn.disabled = true;
  const original = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending…';
  r.innerHTML = '';
  fetch(window.APP_URL + '/admin/mail/test', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(data)
  }).then(x => x.json()).then(res => {
    if (res.ok) {
      r.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle-fill"></i> <strong>Success!</strong> Test email sent to ' + (res.to || '') + '.</div>';
    } else {
      r.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-x-circle-fill"></i> <strong>Failed:</strong> ' + (res.error || 'unknown error') + '</div>';
    }
  }).catch(() => { r.innerHTML = '<div class="alert alert-danger py-2 mb-0">Request failed. Please try again.</div>'; })
    .finally(() => { btn.disabled = false; btn.innerHTML = original; });
});
</script>
