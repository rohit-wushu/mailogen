<?php /** @var ?array $workflow @var array $steps @var array $lists @var array $groups */ ?>
<div class="page-head">
  <h2><?= $workflow ? 'Edit automation' : 'New automation' ?></h2>
  <div class="d-flex gap-2">
    <a href="<?= url('automations') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>

<form method="post" action="<?= url('automations/store') ?>">
  <?= csrf_field() ?>
  <?php if ($workflow): ?><input type="hidden" name="id" value="<?= (int) $workflow['id'] ?>"><?php endif; ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
          <span class="stat-icon grad-violet" style="width:32px;height:32px;font-size:.9rem;border-radius:9px;display:grid;place-items:center;color:#fff;flex:0 0 auto"><i class="bi bi-sliders"></i></span>
          <span>Settings</span>
        </div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" value="<?= e($workflow['name'] ?? '') ?>" required></div>
          <div class="mb-3"><label class="form-label">Audience list</label>
            <select class="form-select" name="list_id">
              <option value="">— choose a list —</option>
              <?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (int) ($workflow['list_id'] ?? 0) === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?> (<?= (int) $l['contact_count'] ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">SMTP group</label>
            <select class="form-select" name="smtp_group_id">
              <option value="">— any available —</option>
              <?php foreach ($groups as $g): ?><option value="<?= (int) $g['id'] ?>" <?= (int) ($workflow['smtp_group_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Trigger</label>
            <select class="form-select" name="trigger_type">
              <option value="manual" <?= ($workflow['trigger_type'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual — enrol list on activation</option>
              <option value="contact_added" <?= ($workflow['trigger_type'] ?? '') === 'contact_added' ? 'selected' : '' ?>>When a contact is added</option>
            </select>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Save automation</button>
          <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> Activate from the Automations list to start enrolling contacts.</p>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="d-flex align-items-center gap-2">
            <span class="stat-icon grad-blue" style="width:32px;height:32px;font-size:.9rem;border-radius:9px;display:grid;place-items:center;color:#fff;flex:0 0 auto"><i class="bi bi-diagram-3"></i></span>
            Workflow steps
          </span>
          <div class="btn-group">
            <button type="button" class="btn btn-sm btn-light" onclick="addStep('email')"><i class="bi bi-envelope text-brand"></i> Add email</button>
            <button type="button" class="btn btn-sm btn-light" onclick="addStep('wait')"><i class="bi bi-hourglass text-secondary"></i> Add wait</button>
          </div>
        </div>
        <div class="card-body">
          <div id="steps"></div>
          <p class="text-muted small text-center mb-0" id="emptyHint">No steps yet — add an email or a wait.</p>
        </div>
      </div>
    </div>
  </div>
</form>

<template id="emailTpl">
  <div class="wf-step" data-type="email">
    <button type="button" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0" onclick="removeStep(this)"><i class="bi bi-x-lg"></i></button>
    <input type="hidden" name="step_type[__I__]" value="email">
    <h6><i class="bi bi-envelope text-brand"></i> Email</h6>
    <div class="mb-2"><input class="form-control form-control-sm" name="email_subject[__I__]" placeholder="Subject (use {{first_name}})"></div>
    <textarea class="form-control form-control-sm font-monospace mb-2" name="email_body[__I__]" rows="3" placeholder="HTML body"></textarea>
    <div class="d-flex flex-wrap gap-3 small">
      <label class="form-check"><input type="checkbox" class="form-check-input" name="stop_opened[__I__]"> stop if opened</label>
      <label class="form-check"><input type="checkbox" class="form-check-input" name="stop_clicked[__I__]"> stop if clicked</label>
      <label class="form-check"><input type="checkbox" class="form-check-input" name="stop_replied[__I__]"> stop if replied</label>
      <label class="form-check"><input type="checkbox" class="form-check-input" name="stop_unsub[__I__]" checked> stop if unsubscribed</label>
    </div>
  </div>
</template>
<template id="waitTpl">
  <div class="wf-step" data-type="wait">
    <button type="button" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0" onclick="removeStep(this)"><i class="bi bi-x-lg"></i></button>
    <input type="hidden" name="step_type[__I__]" value="wait">
    <h6><i class="bi bi-hourglass text-secondary"></i> Wait</h6>
    <div class="row g-2">
      <div class="col-6"><label class="form-label small">Days</label><input type="number" class="form-control form-control-sm" name="wait_days[__I__]" value="3" min="0"></div>
      <div class="col-6"><label class="form-label small">Hours</label><input type="number" class="form-control form-control-sm" name="wait_hours[__I__]" value="0" min="0"></div>
    </div>
  </div>
</template>

<script>
let wfIndex = 0;
function addStep(type){
  const tpl = document.getElementById(type === 'wait' ? 'waitTpl' : 'emailTpl').innerHTML.replace(/__I__/g, wfIndex++);
  const wrap = document.createElement('div'); wrap.innerHTML = tpl;
  document.getElementById('steps').appendChild(wrap.firstElementChild);
  document.getElementById('emptyHint').style.display = 'none';
}
function removeStep(btn){
  btn.closest('.wf-step').remove();
  if(!document.querySelectorAll('.wf-step').length) document.getElementById('emptyHint').style.display = 'block';
}
const STEPS = <?= json_encode(array_map(fn($s) => [
  'type' => $s['step_type'], 'subject' => $s['subject'], 'body' => $s['body_html'],
  'wd' => (int) $s['wait_days'], 'wh' => (int) $s['wait_hours'],
  'o' => (int) $s['stop_if_opened'], 'c' => (int) $s['stop_if_clicked'], 'r' => (int) $s['stop_if_replied'], 'u' => (int) $s['stop_if_unsub'],
], $steps)) ?>;
STEPS.forEach(s => {
  addStep(s.type);
  const all = document.querySelectorAll('.wf-step'); const el = all[all.length - 1];
  if (s.type === 'wait'){
    el.querySelector('[name^=wait_days]').value = s.wd;
    el.querySelector('[name^=wait_hours]').value = s.wh;
  } else {
    el.querySelector('[name^=email_subject]').value = s.subject || '';
    el.querySelector('[name^=email_body]').value = s.body || '';
    const cbs = el.querySelectorAll('input[type=checkbox]');
    cbs[0].checked=!!s.o; cbs[1].checked=!!s.c; cbs[2].checked=!!s.r; cbs[3].checked=!!s.u;
  }
});
</script>
