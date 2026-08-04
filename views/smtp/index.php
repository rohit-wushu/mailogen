<?php /** @var array $accounts @var array $groups @var array $presets */ ?>
<div class="page-head">
  <h2>SMTP Accounts</h2>
  <div class="d-flex gap-2">
    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#groupModal" onclick="resetGroupForm()"><i class="bi bi-collection"></i> New Group</button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#smtpModal" onclick="resetSmtpForm()"><i class="bi bi-plus-lg"></i> Add SMTP Account</button>
  </div>
</div>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Host</th><th>Username</th><th>Status</th><th>Daily Limit</th><th>Used</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$accounts): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="bi bi-hdd-network"></i><p class="mt-2">No SMTP accounts. Connect Gmail, Google Workspace, Brevo, SES, Mailgun, SendGrid or any custom SMTP.</p></div></td></tr>
        <?php else: foreach ($accounts as $a):
          $pct = (int) $a['daily_limit'] > 0 ? min(100, round((int) $a['sent_today'] / (int) $a['daily_limit'] * 100, 1)) : 0; ?>
          <tr>
            <td class="fw-semibold"><?= e($a['label']) ?><div class="text-muted small fw-normal"><?= e(provider_label($a['provider'])) ?></div></td>
            <td class="text-muted small"><?= e($a['host']) ?>:<?= (int) $a['port'] ?></td>
            <td class="text-muted small"><?= e($a['username']) ?></td>
            <td>
              <?php if ((int) $a['is_enabled'] === 0): ?>
                <span class="status-pill status-bounced">Disabled</span>
              <?php else: ?>
                <span class="status-pill status-active"><?= $a['last_status'] === 'failed' ? 'Error' : 'Active' ?></span>
              <?php endif; ?>
            </td>
            <td><?= number_format((int) $a['daily_limit']) ?></td>
            <td><?= (int) $a['sent_today'] ?> (<?= $pct ?>%)</td>
            <td class="text-end text-nowrap">
              <button class="row-actions" data-email="<?= e($a['from_email']) ?>" onclick="smtpTest(<?= (int) $a['id'] ?>, this)" title="Test"><i class="bi bi-send"></i></button>
              <button class="row-actions" onclick='editSmtp(<?= json_encode($a) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('smtp/toggle') ?>" class="d-inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button class="row-actions" title="Enable/disable"><i class="bi bi-<?= (int) $a['is_enabled'] === 1 ? 'pause' : 'play' ?>"></i></button>
              </form>
              <form method="post" action="<?= url('smtp/delete') ?>" class="d-inline" data-confirm="Delete this SMTP account?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button class="row-actions text-danger" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">SMTP Usage Overview</div>
      <div class="card-body">
        <?php if (!$accounts): ?><p class="text-muted mb-0">Add an account to see usage.</p>
        <?php else: foreach ($accounts as $a):
          $pct = (int) $a['daily_limit'] > 0 ? min(100, round((int) $a['sent_today'] / (int) $a['daily_limit'] * 100, 1)) : 0; ?>
          <div class="usage-row">
            <div class="fw-semibold text-truncate"><?= e($a['label']) ?></div>
            <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
            <div class="text-muted small text-end"><?= number_format((int) $a['sent_today']) ?> / <?= number_format((int) $a['daily_limit']) ?> (<?= $pct ?>%)</div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">Rotation Groups
        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#groupModal" onclick="resetGroupForm()"><i class="bi bi-plus-lg"></i></button>
      </div>
      <div class="card-body">
        <?php if (!$groups): ?><p class="text-muted small mb-0">No groups yet. Create one to enable round-robin / failover sending across accounts.</p>
        <?php else: foreach ($groups as $g): ?>
          <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <div>
              <div class="fw-semibold"><?= e($g['name']) ?></div>
              <span class="badge bg-primary-subtle text-primary-emphasis text-capitalize"><?= e(str_replace('_', ' ', $g['rotation_mode'])) ?></span>
              <span class="text-muted small ms-1"><?= (int) $g['smtp_count'] ?> accounts</span>
            </div>
            <div class="text-nowrap">
              <button class="row-actions" onclick='editGroup(<?= json_encode($g) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('smtp/group/delete') ?>" class="d-inline" data-confirm="Delete this group?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                <button class="row-actions text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- SMTP modal -->
<div class="modal fade" id="smtpModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <form class="modal-content" method="post" action="<?= url('smtp/store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="smtp_id">
      <div class="modal-header"><h5 class="modal-title" id="smtpModalTitle">Add SMTP account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Label *</label><input class="form-control" name="label" id="label" placeholder="e.g. Google Workspace 1" required></div>
          <div class="col-md-6">
            <label class="form-label">Provider</label>
            <select class="form-select" name="provider" id="provider" onchange="applyPreset(PRESETS, this.value, 'sm_')">
              <?php foreach ($presets as $key => $_): ?><option value="<?= e($key) ?>"><?= e(provider_label($key)) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Host *</label><input class="form-control" name="host" id="sm_host" required></div>
          <div class="col-md-3"><label class="form-label">Port *</label><input class="form-control" name="port" id="sm_port" value="587" required></div>
          <div class="col-md-3"><label class="form-label">Encryption</label><select class="form-select" name="encryption" id="sm_encryption"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="none">None</option></select></div>
          <div class="col-md-6"><label class="form-label">Username *</label><input class="form-control" name="username" id="username" required></div>
          <div class="col-md-6"><label class="form-label">Password <span class="text-muted small" id="pwHint"></span></label><input type="password" class="form-control" name="password" id="password" autocomplete="new-password"></div>
          <div class="col-md-6"><label class="form-label">From name *</label><input class="form-control" name="from_name" id="from_name" required></div>
          <div class="col-md-6"><label class="form-label">From email *</label><input type="email" class="form-control" name="from_email" id="from_email" required></div>
          <div class="col-md-4"><label class="form-label">Rotation group</label>
            <select class="form-select" name="group_id" id="group_id"><option value="">— none —</option>
              <?php foreach ($groups as $g): ?><option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Priority</label><input type="number" class="form-control" name="priority" id="priority" value="10"></div>
          <div class="col-md-4"><label class="form-label">Daily limit</label><input type="number" class="form-control" name="daily_limit" id="daily_limit" value="1500"></div>
        </div>
        <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle"></i> For Gmail / Google Workspace use an <strong>App Password</strong> (2-Step Verification required).</div>
        <div id="testResult" class="mt-3"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-primary" id="btnTestConn"><i class="bi bi-plug"></i> Test Connection</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">Save account</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Group modal -->
<div class="modal fade" id="groupModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('smtp/group/store') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="group_form_id">
      <div class="modal-header"><h5 class="modal-title">Rotation group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Group name *</label><input class="form-control" name="name" id="group_name" required></div>
        <div class="mb-3"><label class="form-label">Rotation mode</label>
          <select class="form-select" name="rotation_mode" id="rotation_mode">
            <option value="round_robin">Round robin — cycle accounts one by one</option>
            <option value="random">Random — pick a random account each send</option>
            <option value="priority">Priority — always prefer lowest priority value</option>
            <option value="failover">Auto failover — primary, fall through on error</option>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save group</button></div>
    </form>
  </div>
</div>

<script>
const PRESETS = <?= json_encode($presets) ?>;
function resetSmtpForm(){
  document.getElementById('smtpModalTitle').textContent='Add SMTP account';
  document.querySelector('#smtpModal form').reset();
  document.getElementById('smtp_id').value=''; document.getElementById('pwHint').textContent=''; document.getElementById('password').required=true;
  document.getElementById('testResult').innerHTML='';
  applyPreset(PRESETS, document.getElementById('provider').value, 'sm_');
}

document.getElementById('btnTestConn')?.addEventListener('click', function () {
  const r = document.getElementById('testResult');
  const btn = this;
  const data = {
    _csrf: window.CSRF,
    id: document.getElementById('smtp_id').value,
    host: document.getElementById('sm_host').value,
    port: document.getElementById('sm_port').value,
    encryption: document.getElementById('sm_encryption').value,
    username: document.getElementById('username').value,
    password: document.getElementById('password').value,
    from_email: document.getElementById('from_email').value,
    from_name: document.getElementById('from_name').value
  };
  if (!data.host || !data.username) {
    r.innerHTML = '<div class="alert alert-warning py-2 mb-0">Enter host and username first.</div>';
    return;
  }
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing…';
  r.innerHTML = '';
  fetch(window.APP_URL + '/smtp/test-connection', {
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
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-plug"></i> Test Connection'; });
});
function editSmtp(a){
  document.getElementById('smtpModalTitle').textContent='Edit SMTP account';
  smtp_id.value=a.id; label.value=a.label; provider.value=a.provider;
  sm_host.value=a.host; sm_port.value=a.port; sm_encryption.value=a.encryption;
  username.value=a.username; from_name.value=a.from_name; from_email.value=a.from_email;
  group_id.value=a.group_id||''; priority.value=a.priority; daily_limit.value=a.daily_limit;
  password.value=''; password.required=false; document.getElementById('pwHint').textContent='(leave blank to keep current)';
  document.getElementById('testResult').innerHTML='';
  new bootstrap.Modal('#smtpModal').show();
}
function resetGroupForm(){ group_form_id.value=''; group_name.value=''; rotation_mode.value='round_robin'; }
function editGroup(g){ group_form_id.value=g.id; group_name.value=g.name; rotation_mode.value=g.rotation_mode; new bootstrap.Modal('#groupModal').show(); }
</script>
