<?php /** @var array $plans @var array $unsubscribes @var array $user @var array $modeSmtpFeatures @var array $modeDomainFeatures @var string $freePlanName @var array $freeFeatures @var array $teamMembers @var array $pendingInvites @var array $apiKeys @var ?string $newApiKey */
$__isMember = ($user['team_role'] ?? 'owner') === 'member';
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
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-team" type="button"><i class="bi bi-people"></i> Team</button>
        <?php if (($user['team_role'] ?? 'owner') !== 'member'): ?>
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-api" type="button"><i class="bi bi-code-slash"></i> API</button>
        <?php endif; ?>
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

      <!-- Team -->
      <div class="tab-pane fade" id="tab-team">
        <?php if (!$__isMember): ?>
        <div class="card mb-3">
          <div class="card-header"><i class="bi bi-person-plus text-brand me-1"></i> Invite a team member</div>
          <div class="card-body">
            <p class="text-muted small mb-3">They'll get their own login but share your campaigns, contacts, SMTP/domain setup and plan. <strong>Admin</strong> can do everything except billing; <strong>Member</strong> is limited to campaigns, contacts, templates and automations.</p>
            <form method="post" action="<?= url('team/invite') ?>" class="row g-2 align-items-end">
              <?= csrf_field() ?>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="teammate@company.com">
              </div>
              <div class="col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="team_role">
                  <option value="member">Member</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Send invite</button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!$__isMember && $pendingInvites): ?>
        <div class="card mb-3">
          <div class="card-header"><i class="bi bi-hourglass-split text-brand me-1"></i> Pending invites</div>
          <ul class="list-group list-group-flush">
            <?php foreach ($pendingInvites as $inv): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?= e($inv['email']) ?> <span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize"><?= e($inv['team_role']) ?></span></span>
                <form method="post" action="<?= url('team/cancel-invite') ?>">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header"><i class="bi bi-people text-brand me-1"></i> Team members</div>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><?= e($user['name']) ?> <span class="text-muted small">(<?= e($user['email']) ?>)</span></span>
              <span class="badge bg-brand-subtle text-brand">Owner</span>
            </li>
            <?php foreach ($teamMembers as $m): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><?= e($m['name']) ?> <span class="text-muted small">(<?= e($m['email']) ?>)</span></span>
                <?php if ($__isMember): ?>
                  <span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize"><?= e($m['team_role']) ?></span>
                <?php else: ?>
                  <div class="d-flex gap-2 align-items-center">
                    <form method="post" action="<?= url('team/update-role') ?>" class="d-flex gap-1">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                      <select class="form-select form-select-sm" name="team_role" onchange="this.form.submit()">
                        <option value="member" <?= $m['team_role'] === 'member' ? 'selected' : '' ?>>Member</option>
                        <option value="admin" <?= $m['team_role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                      </select>
                    </form>
                    <form method="post" action="<?= url('team/remove') ?>" data-confirm="Remove <?= e($m['name']) ?> from the team?">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
            <?php if (!$teamMembers): ?><li class="list-group-item text-muted small">No team members yet — invite one above.</li><?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- API -->
      <?php if (!$__isMember): ?>
      <div class="tab-pane fade" id="tab-api">
        <?php if ($newApiKey): ?>
        <div class="alert alert-warning">
          <strong><i class="bi bi-key"></i> Copy this key now</strong> — for security it won't be shown again.
          <div class="input-group mt-2">
            <input type="text" class="form-control font-monospace small" value="<?= e($newApiKey) ?>" readonly id="newApiKeyVal">
            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newApiKeyVal').value)"><i class="bi bi-clipboard"></i> Copy</button>
          </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
          <div class="card-header"><i class="bi bi-code-slash text-brand me-1"></i> API keys</div>
          <div class="card-body">
            <p class="text-muted small">Read contacts &amp; campaign stats, or add contacts, from your own scripts / Zapier / CRM. Send the key as <code>Authorization: Bearer &lt;key&gt;</code> (or <code>X-Api-Key</code>, or <code>?api_key=</code> if your tool can't set headers).</p>
            <form method="post" action="<?= url('api-keys/store') ?>" class="row g-2 align-items-end mb-3">
              <?= csrf_field() ?>
              <div class="col-md-8"><input class="form-control" name="label" placeholder="Label — e.g. Zapier, CRM sync"></div>
              <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Generate key</button></div>
            </form>
            <ul class="list-group list-group-flush">
              <?php foreach ($apiKeys as $k): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <span>
                    <span class="fw-semibold"><?= e($k['label']) ?></span>
                    <span class="text-muted small font-monospace"><?= e($k['key_prefix']) ?>…</span>
                    <span class="text-muted small d-block">Last used: <?= $k['last_used_at'] ? e(fmt_dt($k['last_used_at'])) : 'never' ?></span>
                  </span>
                  <form method="post" action="<?= url('api-keys/revoke') ?>" data-confirm="Revoke this API key? Anything using it will stop working immediately.">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Revoke</button>
                  </form>
                </li>
              <?php endforeach; ?>
              <?php if (!$apiKeys): ?><li class="list-group-item text-muted small">No API keys yet — generate one above.</li><?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><i class="bi bi-book text-brand me-1"></i> Endpoints</div>
          <div class="card-body small">
            <table class="table table-sm mb-0">
              <thead><tr><th>Method</th><th>Endpoint</th><th>Does</th></tr></thead>
              <tbody>
                <tr><td><span class="badge bg-success-subtle text-success-emphasis">GET</span></td><td><code><?= e(url('api/v1/contacts')) ?>?limit=&amp;offset=</code></td><td>List contacts</td></tr>
                <tr><td><span class="badge bg-primary-subtle text-primary-emphasis">POST</span></td><td><code><?= e(url('api/v1/contacts')) ?></code></td><td>Add a contact — JSON or form body: <code>email</code> (required), <code>first_name</code>, <code>last_name</code>, <code>company</code>, <code>phone</code>, <code>sector</code>, <code>location</code></td></tr>
                <tr><td><span class="badge bg-success-subtle text-success-emphasis">GET</span></td><td><code><?= e(url('api/v1/campaigns')) ?>?limit=&amp;offset=</code></td><td>List campaigns with open/click counts</td></tr>
                <tr><td><span class="badge bg-success-subtle text-success-emphasis">GET</span></td><td><code><?= e(url('api/v1/campaigns/show')) ?>?id=</code></td><td>Single campaign detail + stats</td></tr>
                <tr><td><span class="badge bg-success-subtle text-success-emphasis">GET</span></td><td><code><?= e(url('api/v1/stats')) ?></code></td><td>Account-wide summary (same numbers as the dashboard)</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Plan -->
      <div class="tab-pane fade" id="tab-plan">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-gem text-brand me-1"></i> Your plan</span>
            <a href="<?= url('billing') ?>" class="btn btn-sm btn-primary"><i class="bi bi-credit-card"></i> Manage billing</a>
          </div>
          <div class="card-body">
            <?php if ($current): $price = Plan::priceFor($current, (string) ($user['sending_mode'] ?? 'smtp')); ?>
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

        <?php
          $__cheapest = null;
          foreach ($plans as $__p) {
            if ((float) $__p['price_smtp'] > 0 || (float) $__p['price_domain'] > 0) { $__cheapest = $__p; break; }
          }
          $__cur = BILLING_CURRENCY === 'INR' ? '₹' : '$';
          $__mode = $user['sending_mode'] ?? 'smtp';
        ?>
        <div class="card mt-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-signpost-split text-brand me-1"></i> How you send campaigns</span>
            <button type="button" class="mode-info-btn" data-bs-toggle="modal" data-bs-target="#modeCompareModal" title="Compare SMTP vs hosted sending"><i class="bi bi-info-circle"></i></button>
          </div>
          <div class="card-body">
            <p class="text-muted small">Domain-based sending is handled by us and priced higher; bringing your own SMTP costs us nothing to run, so it's priced lower. Your current plan's price updates immediately when you switch.</p>
            <form method="post" action="<?= url('settings/sending-mode') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="sending_mode" id="settingsSendingMode" value="<?= e($__mode) ?>">
              <div class="mode-picker">
                <button type="button" class="mode-card <?= $__mode === 'smtp' ? 'active' : '' ?>" data-mode="smtp">
                  <span class="mode-card-check"><i class="bi bi-check-lg"></i></span>
                  <span class="mode-card-ico grad-blue"><i class="bi bi-hdd-network-fill"></i></span>
                  <span class="mode-card-title">Bring your own SMTP</span>
                  <span class="mode-card-sub">Use your own SMTP or ESP account</span>
                  <?php if ($__cheapest && (float) $__cheapest['price_smtp'] > 0): ?><span class="mode-card-price">From <?= e($__cur) ?><?= number_format((float) $__cheapest['price_smtp'], 0) ?><small>/mo</small></span><?php endif; ?>
                </button>
                <button type="button" class="mode-card <?= $__mode === 'domain' ? 'active' : '' ?>" data-mode="domain">
                  <span class="mode-card-check"><i class="bi bi-check-lg"></i></span>
                  <span class="mode-card-ico grad-violet"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                  <span class="mode-card-title">Use our sending infrastructure</span>
                  <span class="mode-card-sub">We handle delivery for you</span>
                  <?php if ($__cheapest && (float) $__cheapest['price_domain'] > 0): ?><span class="mode-card-price">From <?= e($__cur) ?><?= number_format((float) $__cheapest['price_domain'], 0) ?><small>/mo</small></span><?php endif; ?>
                </button>
              </div>
              <button class="btn btn-primary btn-sm mt-3"><i class="bi bi-save"></i> Save</button>
            </form>
          </div>
        </div>
        <script>
        (function () {
          var input = document.getElementById('settingsSendingMode');
          if (!input) return;
          document.querySelectorAll('#tab-plan .mode-card[data-mode]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              document.querySelectorAll('#tab-plan .mode-card[data-mode]').forEach(function (b) { b.classList.remove('active'); });
              btn.classList.add('active');
              input.value = btn.dataset.mode;
            });
          });
        })();
        </script>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="modeCompareModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Compare sending modes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-green"><i class="bi bi-gift-fill"></i></span>
                <div>
                  <div class="mc-col-title"><?= e($freePlanName) ?></div>
                  <div class="mc-col-price">Free</div>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($freeFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
                <?php if (!$freeFeatures): ?><li class="text-muted"><span>No features listed.</span></li><?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-blue"><i class="bi bi-hdd-network-fill"></i></span>
                <div>
                  <div class="mc-col-title">Bring your own SMTP</div>
                  <?php if ($__cheapest && (float) $__cheapest['price_smtp'] > 0): ?><div class="mc-col-price">From <?= e($__cur) ?><?= number_format((float) $__cheapest['price_smtp'], 0) ?>/mo</div><?php endif; ?>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($modeSmtpFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-violet"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <div>
                  <div class="mc-col-title">Hosted sending</div>
                  <?php if ($__cheapest && (float) $__cheapest['price_domain'] > 0): ?><div class="mc-col-price">From <?= e($__cur) ?><?= number_format((float) $__cheapest['price_domain'], 0) ?>/mo</div><?php endif; ?>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($modeDomainFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
