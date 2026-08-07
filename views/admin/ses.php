<?php /** @var ?array $sesConn @var bool $autoUpgradeEnabled @var int $autoUpgradeThreshold */ ?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Amazon SES</h2>
    <p class="text-muted mb-0">Platform-wide sending connection — configured once here, used by every tenant's domain-based campaigns. Tenants only verify their own Sending Domain; they never see or manage these credentials.</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cloud-arrow-up text-brand me-1"></i> Connection</span>
        <?php if ($sesConn): ?>
          <?php if (!empty($sesConn['verified_at'])): ?>
            <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle-fill"></i> Connected</span>
          <?php else: ?>
            <span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-exclamation-triangle-fill"></i> Connection failed</span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($sesConn && !empty($sesConn['last_error'])): ?>
          <div class="alert alert-danger py-2 px-3 small"><i class="bi bi-exclamation-triangle"></i> <?= e($sesConn['last_error']) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= url('admin/ses/store') ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">AWS Access Key ID</label>
              <input class="form-control" name="access_key" value="<?= e(!empty($sesConn['access_key']) ? Crypto::decrypt($sesConn['access_key']) : '') ?>" placeholder="AKIA…" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Region</label>
              <select class="form-select" name="region">
                <?php foreach (['us-east-1','us-east-2','us-west-2','eu-west-1','eu-central-1','ap-south-1','ap-southeast-1','ap-southeast-2','ap-northeast-1'] as $r): ?>
                  <option value="<?= e($r) ?>" <?= ($sesConn['region'] ?? 'us-east-1') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">AWS Secret Access Key <?= $sesConn ? '<span class="text-muted small">(leave blank to keep)</span>' : '' ?></label>
              <input type="password" class="form-control" name="secret_key" autocomplete="new-password" <?= $sesConn ? '' : 'required' ?>>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Save &amp; verify</button>
            <?php if ($sesConn): ?>
              <button class="btn btn-outline-danger" formaction="<?= url('admin/ses/disconnect') ?>" onclick="return confirm('Disconnect Amazon SES? Domain-based campaigns will stop sending for every tenant until you reconnect.')"><i class="bi bi-x-circle"></i> Disconnect</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-info-circle me-1"></i> How this works</div>
      <div class="card-body small text-muted">
        <p>Each tenant verifies their own domain under <strong>Authentication</strong> (SPF/DKIM/DMARC) — that step stays per-tenant since it proves they own the domain they're sending from.</p>
        <p>Actual delivery, though, goes through this single AWS SES connection for the whole platform, same as how Brevo or Mailchimp own their own sending infrastructure. Tenants never enter AWS credentials themselves.</p>
        <p class="mb-0">If this isn't connected, every tenant's domain-based campaigns will fail to send with a clear "contact your administrator" error — legacy SMTP-account campaigns are unaffected.</p>
      </div>
    </div>
  </div>

  <?php if ($sesConn && !empty($sesConn['webhook_token'])): $__hookUrl = rtrim(APP_URL, '/') . '/webhooks/ses-platform.php?t=' . e($sesConn['webhook_token']); ?>
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-broadcast me-1"></i> Bounce / complaint webhook</div>
      <div class="card-body">
        <p class="text-muted small">Create an SNS topic for bounces &amp; complaints on this SES identity, then paste this URL as an HTTPS subscription endpoint. Every tenant's domain-based sends feed through it automatically — this powers the <a href="<?= url('admin/deliverability') ?>">Deliverability</a> page and the platform suppression list. This token is a secret.</p>
        <div class="input-group">
          <input type="text" class="form-control font-monospace small" value="<?= $__hookUrl ?>" readonly id="sesWebhookUrl">
          <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('sesWebhookUrl').value)"><i class="bi bi-clipboard"></i> Copy</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-lightning-charge text-brand me-1"></i> Auto-upgrade large campaigns to managed sending</div>
      <div class="card-body">
        <p class="text-muted small">When a tenant launches a campaign above this recipient count, their account is switched from BYO-SMTP to domain (SES) sending automatically — no confirmation prompt — <em>if</em> they already have a verified Sending Domain. Protects deliverability at volumes most SMTP/ESP accounts choke on. Silent for the tenant except a notice email; nothing happens if they have no verified domain yet.</p>
        <form method="post" action="<?= url('admin/ses/auto-upgrade') ?>">
          <?= csrf_field() ?>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="auto_ses_enabled" id="autoSesEnabled" <?= $autoUpgradeEnabled ? 'checked' : '' ?>>
                <label class="form-check-label" for="autoSesEnabled">Enabled</label>
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label">Recipient threshold</label>
              <input type="number" min="1" class="form-control" name="auto_ses_threshold" value="<?= (int) $autoUpgradeThreshold ?>">
            </div>
            <div class="col-md-3">
              <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
