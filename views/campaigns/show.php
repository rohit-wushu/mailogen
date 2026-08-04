<?php /** @var array $campaign @var ?array $report @var array $schedules @var array $audience */
$canSend = in_array($campaign['status'], ['draft', 'scheduled', 'paused'], true);
?>
<div class="page-head">
  <div>
    <h2 class="mb-1"><?= e($campaign['name']) ?> <?= status_badge($campaign['status']) ?></h2>
    <p class="text-muted mb-0"><?= e($campaign['subject'] ?: 'No subject set') ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('campaigns/edit?id=' . $campaign['id']) ?>" class="btn btn-light"><i class="bi bi-pencil"></i> Edit</a>
    <button class="btn btn-outline-primary" onclick="openTestMail()"><i class="bi bi-send"></i> Send test</button>
    <?php if ($campaign['status'] === 'running'): ?>
      <form method="post" action="<?= url('campaigns/pause') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>"><button class="btn btn-warning"><i class="bi bi-pause"></i> Pause</button></form>
    <?php elseif ($campaign['status'] === 'paused'): ?>
      <form method="post" action="<?= url('campaigns/resume') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>"><button class="btn btn-success"><i class="bi bi-play"></i> Resume</button></form>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
  <?php
  $cells = [
    ['Recipients',   (int) ($campaign['total_recipients'] ?? 0),    'people-fill',     'violet'],
    ['Sent',         (int) ($report['sent'] ?? $campaign['sent_count'] ?? 0), 'send-check-fill', 'green'],
    ['Opened',       (int) ($report['opened'] ?? 0),                 'eye-fill',        'orange'],
    ['Clicked',      (int) ($report['clicked'] ?? 0),                'cursor-fill',     'sky'],
    ['Failed',       (int) ($report['failed'] ?? 0),                 'x-octagon-fill',  'red'],
    ['Unsubscribed', (int) ($report['unsubscribed'] ?? 0),          'person-dash-fill','pink'],
  ];
  foreach ($cells as [$l, $v, $i, $g]): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card" style="grid-template-rows:auto;">
        <div class="stat-icon grad-<?= $g ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-<?= $i ?>"></i></div>
        <div class="stat-top">
          <div class="stat-label"><?= $l ?></div>
          <div class="stat-value" style="font-size:1.4rem"><?= $v ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-rocket-takeoff me-1"></i> Launch</div>
      <div class="card-body">
<?php $__isSheet = ($campaign['source_type'] ?? 'list') === 'sheet'; ?>
        <p class="mb-2">
          <i class="bi bi-<?= $__isSheet ? 'file-earmark-spreadsheet text-success' : 'people text-brand' ?>"></i>
          Audience: <strong><?= count($audience) ?></strong> <?= $__isSheet ? 'recipients from Google Sheet' : 'active contacts' ?>
          <?php if ($__isSheet): ?>
            <span class="badge bg-success-subtle text-success-emphasis">Google Sheet</span>
          <?php else: ?>
            <?php if (!empty($campaign['sector'])): ?> <span class="badge bg-info-subtle text-info-emphasis">Sector: <?= e($campaign['sector']) ?></span><?php endif; ?><?php if (!empty($campaign['location'])): ?> <span class="badge bg-primary-subtle text-primary-emphasis">Location: <?= e($campaign['location']) ?></span><?php endif; ?>
          <?php endif; ?>
        </p>
        <?php if ($__isSheet && !empty($sheetError)): ?>
          <div class="alert alert-warning py-2 small mb-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($sheetError) ?></div>
        <?php endif; ?>

        <?php if (!empty($spamCheck)): ?>
          <div class="border rounded-3 p-2 mb-3" style="background:var(--bs-tertiary-bg)">
            <div class="small fw-semibold mb-1"><i class="bi bi-clipboard-check text-brand"></i> Pre-send checklist</div>
            <ul class="list-unstyled small mb-0">
              <?php foreach ($spamCheck as $f):
                $ic = $f['level'] === 'error' ? 'x-circle-fill text-danger' : ($f['level'] === 'warn' ? 'exclamation-triangle-fill text-warning' : 'check-circle-fill text-success'); ?>
                <li class="mb-1"><i class="bi bi-<?= $ic ?> me-1"></i><?= e($f['msg']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php $isScheduled = $campaign['status'] === 'scheduled' && $campaign['scheduled_at']; ?>
        <?php if ($isScheduled): ?>
          <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3">
            <i class="bi bi-clock-history"></i>
            <div>Scheduled for <strong><?= fmt_dt($campaign['scheduled_at']) ?></strong></div>
          </div>
        <?php endif; ?>

        <?php if ($canSend): ?>
          <form method="post" action="<?= url('campaigns/send') ?>" data-confirm="Queue <?= count($audience) ?> emails to send now?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">
            <button class="btn btn-primary w-100 mb-2"><i class="bi bi-send-fill"></i> Send now</button>
          </form>
          <form method="post" action="<?= url('campaigns/schedule') ?>" class="d-flex gap-2">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">
            <input type="datetime-local" class="form-control" name="scheduled_at" required
                   value="<?= $campaign['scheduled_at'] ? e(date('Y-m-d\TH:i', strtotime($campaign['scheduled_at']))) : '' ?>">
            <button class="btn btn-outline-primary text-nowrap">
              <i class="bi bi-clock"></i> <?= $isScheduled ? 'Reschedule' : 'Schedule' ?>
            </button>
          </form>
          <?php if ($isScheduled): ?>
            <form method="post" action="<?= url('campaigns/unschedule') ?>" class="mt-2">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">
              <button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-x-circle"></i> Cancel schedule</button>
            </form>
          <?php endif; ?>
        <?php elseif ($campaign['status'] === 'running'): ?>
          <div class="alert alert-info mb-0"><i class="bi bi-hourglass-split"></i> Sending in progress — handled by the cron worker.</div>
        <?php elseif (in_array($campaign['status'], ['completed', 'cancelled'], true)): ?>
          <div class="alert alert-<?= $campaign['status'] === 'completed' ? 'success' : 'secondary' ?> mb-3">
            <i class="bi bi-<?= $campaign['status'] === 'completed' ? 'check-circle' : 'x-circle' ?>"></i>
            Campaign <?= e($campaign['status']) ?>.
          </div>
          <form method="post" action="<?= url('campaigns/reopen') ?>"
                data-confirm="Reopen this campaign as a draft to schedule or send it again? Previous send history is kept.">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">
            <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Reschedule / Send again</button>
          </form>
          <p class="text-muted small mt-2 mb-0">Reopens as a draft and keeps the previous run's logs — the next send adds a new run on top.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <?php if ($schedules): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-diagram-3 me-1"></i> Follow-up sequence</div>
        <ul class="list-group list-group-flush">
          <?php foreach ($schedules as $s): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-center">
                <span><span class="badge bg-primary-subtle text-primary-emphasis">Step <?= (int) $s['step'] ?></span> after <?= (int) $s['delay_days'] ?> days — <strong><?= e($s['subject']) ?></strong></span>
                <span class="small text-muted"><?= (int) $s['stop_if_replied'] ? 'stops on reply' : '' ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-envelope-paper me-1"></i> Email preview</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis">incl. footer</span>
      </div>
      <div class="card-body p-0">
        <iframe data-autofit srcdoc="<?= e($previewHtml ?? ($campaign['body_html'] ?: '')) ?>" style="width:100%;height:360px;border:0;background:#fff"></iframe>
      </div>
      <div class="card-footer small text-muted">
        <i class="bi bi-info-circle"></i> Shows the compliance footer<?php if (!empty($isFreePlan)): ?> and the free-plan branding<?php endif; ?> that's added automatically to every send.
        <?php if (!empty($isFreePlan) && ($user['role'] ?? '') === 'admin'): ?> Manage it in <a href="<?= url('admin/branding') ?>">Branding</a>.<?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Send test email modal -->
<div class="modal fade" id="testMailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-send text-brand me-1"></i> Send a test email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Send a copy of <strong><?= e($campaign['name']) ?></strong> to:</label>
        <textarea class="form-control" id="tmEmail" rows="2" placeholder="you@example.com, teammate@example.com"><?= e($user['email'] ?? '') ?></textarea>
        <div class="form-text">Separate multiple addresses with commas, spaces or new lines (up to 5).</div>
        <div class="alert alert-secondary d-flex align-items-center gap-2 mt-3 mb-0" id="tmStatus" role="status">
          <i class="bi bi-info-circle"></i><span>This sends a real email through your SMTP so you can preview exactly how it lands.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="tmSend"><i class="bi bi-send"></i> Send test</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var CAMPAIGN_ID = <?= (int) $campaign['id'] ?>;
  var modalEl = document.getElementById('testMailModal');
  if (!modalEl) return;
  var modal   = new bootstrap.Modal(modalEl);
  var email   = document.getElementById('tmEmail');
  var status  = document.getElementById('tmStatus');
  var sendBtn = document.getElementById('tmSend');

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function setStatus(kind, html) {
    status.className = 'alert d-flex align-items-start gap-2 mt-3 mb-0 alert-' + kind;
    status.innerHTML = html;
  }
  function resetModal() {
    setStatus('secondary', '<i class="bi bi-info-circle"></i><span>This sends a real email through your SMTP so you can preview exactly how it lands.</span>');
    sendBtn.disabled = false;
    sendBtn.innerHTML = '<i class="bi bi-send"></i> Send test';
  }
  window.openTestMail = function () { resetModal(); modal.show(); setTimeout(function () { email.focus(); }, 300); };

  function summarise(res) {
    var sent = res.sent || [], failed = res.failed || [], invalid = res.invalid || [];
    var parts = [];
    if (sent.length) {
      parts.push('<div><i class="bi bi-check-circle-fill text-success"></i> Sent to <strong>' + sent.length + '</strong> address' +
        (sent.length === 1 ? '' : 'es') + ': ' + sent.map(esc).join(', ') + '. <span class="text-muted">Check the inbox (and spam).</span></div>');
    }
    if (failed.length) {
      parts.push('<div class="mt-1"><i class="bi bi-x-circle-fill text-danger"></i> Failed: ' +
        failed.map(function (f) { return esc(f.email) + ' <span class="text-muted">(' + esc(f.error || 'error') + ')</span>'; }).join(', ') + '</div>');
    }
    if (invalid.length) {
      parts.push('<div class="mt-1"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Skipped invalid: ' + invalid.map(esc).join(', ') + '</div>');
    }
    if (res.capped) {
      parts.push('<div class="mt-1 text-muted small">Only the first ' + res.max + ' addresses were used.</div>');
    }
    return '<div>' + parts.join('') + '</div>';
  }

  sendBtn.addEventListener('click', function () {
    var raw = (email.value || '').trim();
    if (!raw || raw.indexOf('@') < 0) { email.classList.add('is-invalid'); email.focus(); return; }
    email.classList.remove('is-invalid');
    var count = raw.split(/[\s,;]+/).filter(Boolean).length;

    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending…';
    setStatus('info', '<span class="spinner-border spinner-border-sm"></span><span>Sending test email to ' + count + ' address' + (count === 1 ? '' : 'es') + '…</span>');

    fetch(window.APP_URL + '/campaigns/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ id: CAMPAIGN_ID, to: raw, _csrf: window.CSRF })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      sendBtn.disabled = false;
      if (res && res.ok) {
        setStatus((res.failed && res.failed.length) ? 'warning' : 'success', summarise(res));
        sendBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Send again';
        if (window.toast) window.toast('Test email sent to ' + (res.sent || []).length + ' address(es).', 'success');
      } else if (res && (res.failed || res.invalid)) {
        setStatus('danger', summarise(res));
        sendBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry';
      } else {
        setStatus('danger', '<i class="bi bi-x-circle-fill"></i><span>Could not send: ' + esc((res && res.error) || 'unknown error') + '</span>');
        sendBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry';
      }
    })
    .catch(function () {
      setStatus('danger', '<i class="bi bi-x-circle-fill"></i><span>Network error — please try again.</span>');
      sendBtn.disabled = false;
      sendBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry';
    });
  });

  // Ctrl/Cmd+Enter submits (plain Enter makes a new line in the textarea).
  email.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); sendBtn.click(); } });
})();
</script>
