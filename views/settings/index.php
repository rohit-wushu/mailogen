<?php /** @var array $plans @var array $unsubscribes @var array $user */
$tzList = ['Asia/Kolkata','UTC','America/New_York','America/Chicago','America/Los_Angeles','Europe/London','Europe/Paris','Asia/Dubai','Asia/Singapore','Australia/Sydney'];
$current = null; foreach ($plans as $p) { if ((int) $p['id'] === (int) ($user['plan_id'] ?? 0)) { $current = $p; } }
?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Settings</h2>
    <p class="text-muted mb-0 small">Manage your profile, security, sending inbox and plan.</p>
  </div>
</div>

<div class="row g-3">
  <!-- Settings nav -->
  <div class="col-lg-3">
    <div class="card settings-nav">
      <div class="nav flex-column nav-pills p-2" role="tablist">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button"><i class="bi bi-person"></i> Profile</button>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-sender" type="button"><i class="bi bi-geo-alt"></i> Sender &amp; Compliance</button>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-security" type="button"><i class="bi bi-shield-lock"></i> Security</button>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-inbox" type="button"><i class="bi bi-inbox"></i> Inbox &amp; Bounces</button>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-appearance" type="button"><i class="bi bi-palette"></i> Appearance</button>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-plan" type="button"><i class="bi bi-gem"></i> Plan</button>
      </div>
    </div>
  </div>

  <!-- Panels -->
  <div class="col-lg-9">
    <div class="tab-content">

      <!-- Profile -->
      <div class="tab-pane fade show active" id="tab-profile">
        <div class="card">
          <div class="card-header"><i class="bi bi-person text-brand me-1"></i> Profile</div>
          <form class="card-body" method="post" action="<?= url('settings/profile') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="org_address" value="<?= e($user['org_address'] ?? '') ?>">
            <input type="hidden" name="org_website" value="<?= e($user['org_website'] ?? '') ?>">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($user['name']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Email <span class="text-muted small">(locked)</span></label><input class="form-control" value="<?= e($user['email']) ?>" disabled></div>
              <div class="col-md-6"><label class="form-label">Company</label><input class="form-control" name="company" value="<?= e($user['company'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($user['phone'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label">Timezone</label>
                <select class="form-select" name="timezone">
                  <?php foreach ($tzList as $tz): ?><option value="<?= e($tz) ?>" <?= ($user['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
            <button class="btn btn-primary mt-3"><i class="bi bi-save"></i> Save profile</button>
          </form>
        </div>
      </div>

      <!-- Sender & Compliance -->
      <div class="tab-pane fade" id="tab-sender">
        <div class="card">
          <div class="card-header"><i class="bi bi-geo-alt text-brand me-1"></i> Sender &amp; Compliance</div>
          <form class="card-body" method="post" action="<?= url('settings/profile') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="name" value="<?= e($user['name']) ?>">
            <input type="hidden" name="company" value="<?= e($user['company'] ?? '') ?>">
            <input type="hidden" name="phone" value="<?= e($user['phone'] ?? '') ?>">
            <input type="hidden" name="timezone" value="<?= e($user['timezone'] ?? 'Asia/Kolkata') ?>">
            <div class="alert alert-warning border-0 py-2 small"><i class="bi bi-exclamation-triangle"></i> A <strong>physical postal address is legally required</strong> in the footer of bulk email (CAN-SPAM / GDPR). It's added automatically to every campaign you send.</div>
            <div class="mb-3"><label class="form-label">Mailing address <span class="text-muted small">(appears in email footer)</span></label><input class="form-control" name="org_address" value="<?= e($user['org_address'] ?? '') ?>" placeholder="123 Business St, City, State 000000, Country"></div>
            <div class="mb-3"><label class="form-label">Website <span class="text-muted small">(optional)</span></label><input class="form-control" name="org_website" value="<?= e($user['org_website'] ?? '') ?>" placeholder="yourcompany.com"></div>
            <button class="btn btn-primary"><i class="bi bi-save"></i> Save sender details</button>
          </form>
        </div>
      </div>

      <!-- Security -->
      <div class="tab-pane fade" id="tab-security">
        <div class="card">
          <div class="card-header"><i class="bi bi-shield-lock text-brand me-1"></i> Change password</div>
          <form class="card-body" method="post" action="<?= url('settings/password') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Current password</label><input type="password" class="form-control" name="current_password" required></div>
              <div class="col-md-6"><label class="form-label">New password <span class="text-muted small">(min 8 chars)</span></label><input type="password" class="form-control" name="new_password" minlength="8" required></div>
            </div>
            <button class="btn btn-primary mt-3"><i class="bi bi-key"></i> Update password</button>
          </form>
        </div>
      </div>

      <!-- Inbox / IMAP -->
      <div class="tab-pane fade" id="tab-inbox">
        <div class="card">
          <div class="card-header"><i class="bi bi-inbox text-brand me-1"></i> Inbox &amp; bounce processing <span class="text-muted small fw-normal ms-1">(IMAP)</span></div>
          <form class="card-body" method="post" action="<?= url('settings/imap') ?>">
            <?= csrf_field() ?>
            <p class="text-muted small">Connect the mailbox your campaigns send <em>from</em>. The cron reads it to auto-suppress bounced addresses and detect replies (which stop follow-ups).</p>
            <div class="row g-3">
              <div class="col-8"><label class="form-label">IMAP host</label><input class="form-control" name="imap_host" value="<?= e($user['imap_host'] ?? '') ?>" placeholder="imap.gmail.com"></div>
              <div class="col-4"><label class="form-label">Port</label><input class="form-control" name="imap_port" value="<?= (int) ($user['imap_port'] ?? 993) ?>"></div>
              <div class="col-12"><label class="form-label">Username</label><input class="form-control" name="imap_user" value="<?= e($user['imap_user'] ?? '') ?>" placeholder="you@yourdomain.com"></div>
              <div class="col-12"><label class="form-label">Password <span class="text-muted small">(App Password; leave blank to keep)</span></label><input type="password" class="form-control" name="imap_pass" autocomplete="new-password"></div>
              <div class="col-12">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="imap_enabled" id="imap_enabled" <?= !empty($user['imap_enabled']) ? 'checked' : '' ?>><label class="form-check-label" for="imap_enabled">Enable inbox processing</label></div>
              </div>
            </div>
            <button class="btn btn-primary mt-3"><i class="bi bi-save"></i> Save inbox settings</button>
          </form>
        </div>
      </div>

      <!-- Appearance -->
      <div class="tab-pane fade" id="tab-appearance">
        <div class="card">
          <div class="card-header"><i class="bi bi-palette text-brand me-1"></i> Appearance</div>
          <div class="card-body">
            <p class="text-muted small mb-3">Choose your interface theme. Your preference is saved automatically and also toggleable from the top bar.</p>
            <div class="row g-3" style="max-width:480px">
              <div class="col-6">
                <form method="post" action="<?= url('settings/theme') ?>"><?= csrf_field() ?><input type="hidden" name="theme" value="light">
                  <button class="theme-card <?= ($user['theme'] ?? 'light') === 'light' ? 'active' : '' ?>">
                    <span class="theme-preview theme-preview--light"></span>
                    <span><i class="bi bi-sun-fill"></i> Light</span>
                  </button>
                </form>
              </div>
              <div class="col-6">
                <form method="post" action="<?= url('settings/theme') ?>"><?= csrf_field() ?><input type="hidden" name="theme" value="dark">
                  <button class="theme-card <?= ($user['theme'] ?? '') === 'dark' ? 'active' : '' ?>">
                    <span class="theme-preview theme-preview--dark"></span>
                    <span><i class="bi bi-moon-stars-fill"></i> Dark</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Plan -->
      <div class="tab-pane fade" id="tab-plan">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-gem text-brand me-1"></i> Your plan</span>
            <a href="<?= url('billing') ?>" class="btn btn-sm btn-primary"><i class="bi bi-credit-card"></i> Manage billing</a>
          </div>
          <div class="card-body">
            <?php if ($current): $price = (float) $current['price_monthly']; ?>
              <div class="plan-banner mb-3">
                <span class="stat-icon grad-violet" style="width:54px;height:54px;border-radius:15px;font-size:1.35rem;flex:0 0 auto"><i class="bi bi-stars"></i></span>
                <div class="flex-grow-1">
                  <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.06em">Current plan</div>
                  <h4 class="text-brand mb-0 fw-bold lh-1"><?= e($current['name']) ?></h4>
                  <div class="text-muted small mt-1"><?= $price > 0 ? e(BILLING_CURRENCY) . ' ' . number_format($price) . ' / month' : 'Free forever' ?></div>
                </div>
                <span class="status-pill status-active align-self-start">Active</span>
              </div>
              <div class="row g-2">
                <?php
                $rows = [
                  ['Contacts', (int) $current['max_contacts'] === -1 ? 'Unlimited' : number_format((int) $current['max_contacts']), 'people-fill', 'violet'],
                  ['Campaigns', (int) $current['max_campaigns'] === -1 ? 'Unlimited' : (int) $current['max_campaigns'], 'megaphone-fill', 'pink'],
                  ['SMTP accounts', (int) $current['max_smtp'] === -1 ? 'Unlimited' : (int) $current['max_smtp'], 'hdd-network-fill', 'teal'],
                  ['Monthly emails', number_format((int) $current['monthly_emails']), 'send-fill', 'green'],
                ];
                foreach ($rows as [$lbl, $val, $ic, $grad]): ?>
                  <div class="col-sm-6"><div class="plan-tile">
                    <span class="stat-icon grad-<?= $grad ?>" style="width:38px;height:38px;border-radius:11px;font-size:.95rem;flex:0 0 auto"><i class="bi bi-<?= $ic ?>"></i></span>
                    <div><div class="text-muted small"><?= $lbl ?></div><div class="fw-bold fs-6"><?= $val ?></div></div>
                  </div></div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state py-4"><i class="bi bi-gem"></i><p class="mt-2 mb-2">You're on the free plan.</p><a href="<?= url('billing') ?>" class="btn btn-sm btn-primary">View plans</a></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
              <span class="small text-muted"><i class="bi bi-slash-circle"></i> Suppression list: <strong><?= count($unsubscribes) ?></strong> address(es)</span>
              <a href="<?= url('suppression') ?>" class="btn btn-sm btn-outline-secondary">Manage suppressions</a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
