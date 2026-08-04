<?php /** @var array $contacts @var array $lists @var array $tags @var int $total @var int $page @var int $pages @var array $filters @var array $cardStats @var array $verifyCounts */ ?>
<div class="page-head">
  <h2>Contacts</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('contacts/lists') ?>" class="btn btn-light"><i class="bi bi-collection"></i> Lists</a>
    <a href="<?= url('contacts/import') ?>" class="btn btn-light"><i class="bi bi-upload"></i> Import</a>
    <?php $unv = (int) ($verifyCounts['unverified'] ?? 0); ?>
    <div class="btn-group">
      <button class="btn btn-light" id="verifyBtn" data-list="<?= (int) ($filters['list_id'] ?? 0) ?>">
        <i class="bi bi-patch-check"></i> Verify emails
        <?php if ($unv > 0): ?><span class="badge bg-secondary"><?= $unv ?></span><?php endif; ?>
      </button>
      <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
      <?php $inv = (int) ($verifyCounts['invalid'] ?? 0); $rsk = (int) ($verifyCounts['risky'] ?? 0); ?>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><button class="dropdown-item" id="verifyAllBtn"><i class="bi bi-arrow-repeat me-2"></i>Re-verify all addresses</button></li>
        <li><button class="dropdown-item" id="deepVerifyBtn"><i class="bi bi-patch-check-fill me-2 text-brand"></i>Deep verify <span class="text-muted small">(confirm mailboxes · slow)</span></button></li>
        <li><hr class="dropdown-divider"></li>
        <li class="dropdown-header small text-uppercase">Clean list</li>
        <li>
          <form method="post" action="<?= url('contacts/clean-invalid') ?>" data-confirm="Permanently delete the <?= $inv ?> invalid contact(s)?">
            <?= csrf_field() ?><input type="hidden" name="scope" value="invalid">
            <button class="dropdown-item text-danger <?= $inv === 0 ? 'disabled' : '' ?>"><i class="bi bi-patch-exclamation me-2"></i>Remove invalid<?php if ($inv): ?> <span class="badge bg-danger-subtle text-danger-emphasis ms-1"><?= $inv ?></span><?php endif; ?></button>
          </form>
        </li>
        <li>
          <form method="post" action="<?= url('contacts/clean-invalid') ?>" data-confirm="Permanently delete the <?= $inv + $rsk ?> invalid + risky contact(s)?">
            <?= csrf_field() ?><input type="hidden" name="scope" value="both">
            <button class="dropdown-item text-danger <?= ($inv + $rsk) === 0 ? 'disabled' : '' ?>"><i class="bi bi-trash3 me-2"></i>Remove invalid &amp; risky<?php if ($inv + $rsk): ?> <span class="badge bg-danger-subtle text-danger-emphasis ms-1"><?= $inv + $rsk ?></span><?php endif; ?></button>
          </form>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="<?= url('contacts/dedupe') ?>" data-confirm="Remove duplicate contacts with the same email? The oldest copy of each is kept.">
            <?= csrf_field() ?>
            <button class="dropdown-item"><i class="bi bi-stack me-2"></i>Remove duplicate emails</button>
          </form>
        </li>
      </ul>
    </div>
    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#contactModal" onclick="resetContactForm()"><i class="bi bi-person-plus"></i> Add Contact</button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#listModal" onclick="resetListForm()"><i class="bi bi-plus-lg"></i> Create List</button>
  </div>
</div>

<!-- Verification progress (shown while verifying) -->
<div class="card mb-3 d-none" id="verifyPanel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="fw-semibold"><i class="bi bi-patch-check text-brand"></i> Verifying email addresses…</span>
      <span class="small text-muted" id="verifyStat">starting…</span>
    </div>
    <div class="progress" style="height:8px"><div class="progress-bar progress-bar-striped progress-bar-animated" id="verifyBar" style="width:0%"></div></div>
    <div class="d-flex gap-3 mt-2 small" id="verifyTally"></div>
  </div>
</div>

<?= render_stat_cards($cardStats, 3) ?>

<div class="card">
  <div class="card-body border-bottom">
    <form class="filter-bar" method="get" action="<?= url('contacts') ?>">
      <select class="form-select" name="list_id" style="max-width:180px" onchange="this.form.submit()">
        <option value="">All Lists</option>
        <?php foreach ($lists as $l): ?>
          <option value="<?= (int) $l['id'] ?>" <?= (int) ($filters['list_id'] ?? 0) === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" name="status" style="max-width:160px" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['active' => 'Subscribed', 'unsubscribed' => 'Unsubscribed', 'bounced' => 'Bounced'] as $s => $lbl): ?>
          <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select" name="verify" style="max-width:170px" onchange="this.form.submit()">
        <option value="">Any verification</option>
        <?php foreach (['valid' => 'Valid', 'risky' => 'Risky', 'invalid' => 'Invalid', 'unknown' => 'Unknown', 'unverified' => 'Not verified'] as $s => $lbl): ?>
          <option value="<?= $s ?>" <?= ($filters['verify_status'] ?? '') === $s ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($sectors)): ?>
      <select class="form-select" name="sector" style="max-width:180px" onchange="this.form.submit()">
        <option value="">All Sectors</option>
        <?php foreach ($sectors as $sec): ?>
          <option value="<?= e($sec['sector']) ?>" <?= ($filters['sector'] ?? '') === $sec['sector'] ? 'selected' : '' ?>><?= e($sec['sector']) ?> (<?= (int) $sec['c'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <?php if (!empty($locations)): ?>
      <select class="form-select" name="location" style="max-width:180px" onchange="this.form.submit()">
        <option value="">All Locations</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?= e($loc['location']) ?>" <?= ($filters['location'] ?? '') === $loc['location'] ? 'selected' : '' ?>><?= e($loc['location']) ?> (<?= (int) $loc['c'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <div class="search-field"><i class="bi bi-search"></i>
        <input class="form-control" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search contacts...">
      </div>
      <button class="btn btn-light"><i class="bi bi-funnel"></i> Filters</button>
    </form>
  </div>
  <div id="bulkBar" class="d-none align-items-center justify-content-between px-3 py-2 border-bottom" style="background:var(--bs-tertiary-bg)">
    <span class="small fw-semibold"><span id="selCount">0</span> selected</span>
    <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn"><i class="bi bi-trash"></i> Delete selected</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th style="width:40px"><input type="checkbox" class="form-check-input" id="checkAll"></th>
        <th>Name</th><th>Email</th><th>Verification</th><th>List</th><th>Tags</th><th>Status</th><th>Added On</th><th style="width:40px"></th>
      </tr></thead>
      <tbody>
        <?php if (!$contacts): ?>
          <tr><td colspan="9"><div class="empty-state"><i class="bi bi-people"></i><p class="mt-2">No contacts found. Add one or import a CSV.</p></div></td></tr>
        <?php else:
          $listName = [];
          foreach ($lists as $l) { $listName[(int) $l['id']] = $l['name']; }
          foreach ($contacts as $c):
            $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: '—';
            $statusMap = ['active' => ['subscribed', 'Subscribed'], 'unsubscribed' => ['unsubscribed', 'Unsubscribed'], 'bounced' => ['bounced', 'Bounced']];
            [$scls, $slabel] = $statusMap[$c['status']] ?? ['active', ucfirst($c['status'])];
            $ctags = array_filter(array_map('trim', explode(',', (string) ($c['tags'] ?? ''))));
        ?>
          <tr>
            <td><input type="checkbox" class="form-check-input row-check" value="<?= (int) $c['id'] ?>"></td>
            <td class="fw-semibold"><?= e($name) ?></td>
            <td class="text-muted"><?= e($c['email']) ?></td>
            <td>
              <?php $vs = $c['verify_status'] ?? 'unverified';
              if ($vs === 'unverified'): ?><span class="text-muted small">—</span>
              <?php else:
                $vcolor = EmailVerifier::color($vs);
                $vicon = ['valid' => 'patch-check-fill', 'invalid' => 'patch-exclamation-fill', 'risky' => 'exclamation-triangle-fill', 'unknown' => 'question-circle-fill'][$vs] ?? 'circle'; ?>
                <span class="badge bg-<?= $vcolor ?>-subtle text-<?= $vcolor ?>-emphasis text-capitalize" title="<?= e($c['verify_reason'] ?? '') ?>"><i class="bi bi-<?= $vicon ?>"></i> <?= e($vs) ?></span>
              <?php endif; ?>
            </td>
            <td><?= isset($listName[(int) $c['list_id']]) ? e($listName[(int) $c['list_id']]) : '<span class="text-muted">—</span>' ?></td>
            <td>
              <?php foreach ($ctags as $i => $t):
                $pal = ['#eef0ff:#5b4ee8', '#e9fbf3:#059669', '#fff6e8:#d97706', '#eaf3ff:#2563eb', '#fdecec:#dc2626'][$i % 5];
                [$bg, $fg] = explode(':', $pal); ?>
                <span class="tag-pill" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= e($t) ?></span>
              <?php endforeach; ?>
              <?php if (!$ctags): ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td><span class="status-pill status-<?= $scls ?>"><?= $slabel ?></span></td>
            <td class="text-muted small text-nowrap"><?= fmt_dt($c['created_at']) ?></td>
            <td>
              <div class="dropdown">
                <button class="row-actions" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><button class="dropdown-item" onclick='editContact(<?= json_encode($c) ?>)'><i class="bi bi-pencil me-2"></i>Edit</button></li>
                  <li>
                    <form method="post" action="<?= url('contacts/delete') ?>" data-confirm="Delete this contact?">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                      <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="text-muted small">Showing <?= $contacts ? (($page - 1) * 50 + 1) : 0 ?> to <?= ($page - 1) * 50 + count($contacts) ?> of <?= (int) $total ?> contacts</span>
    <?php if ($pages > 1): ?>
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $pages; $p++):
          $qs = array_filter(['q' => $filters['q'] ?? '', 'list_id' => $filters['list_id'] ?? '', 'sector' => $filters['sector'] ?? '', 'location' => $filters['location'] ?? '', 'status' => $filters['status'] ?? '', 'verify' => $filters['verify_status'] ?? '', 'page' => $p]); ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= url('contacts') . '?' . http_build_query($qs) ?>"><?= $p ?></a></li>
        <?php endfor; ?>
      </ul></nav>
    <?php endif; ?>
  </div>
</div>

<!-- Contact modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('contacts/store') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="contact_id">
      <div class="modal-header"><h5 class="modal-title" id="contactModalTitle">Add contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" id="c_email" required></div>
        <div class="col-6"><label class="form-label">First name</label><input class="form-control" name="first_name" id="c_first"></div>
        <div class="col-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" id="c_last"></div>
        <div class="col-6"><label class="form-label">Company</label><input class="form-control" name="company" id="c_company"></div>
        <div class="col-6"><label class="form-label">Phone</label><input class="form-control" name="phone" id="c_phone"></div>
        <div class="col-6"><label class="form-label">Sector <span class="text-muted small">(segment)</span></label><input class="form-control" name="sector" id="c_sector" list="sectorList" placeholder="e.g. Healthcare"></div>
        <div class="col-6"><label class="form-label">Location <span class="text-muted small">(segment)</span></label><input class="form-control" name="location" id="c_location" list="locationList" placeholder="e.g. Mumbai"></div>
        <div class="col-12"><label class="form-label">List</label>
          <select class="form-select" name="list_id" id="c_list"><option value="">— none —</option>
            <?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>

<!-- List modal -->
<div class="modal fade" id="listModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('contacts/list/store') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="list_form_id">
      <div class="modal-header"><h5 class="modal-title">New list</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" id="list_name" required></div>
        <div class="mb-3"><label class="form-label">Description</label><input class="form-control" name="description" id="list_desc"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save list</button></div>
    </form>
  </div>
</div>

<datalist id="sectorList"><?php foreach (($sectors ?? []) as $sec): ?><option value="<?= e($sec['sector']) ?>"><?php endforeach; ?></datalist>
<datalist id="locationList"><?php foreach (($locations ?? []) as $loc): ?><option value="<?= e($loc['location']) ?>"><?php endforeach; ?></datalist>

<form id="bulkForm" method="post" action="<?= url('contacts/bulk-delete') ?>" class="d-none"><?= csrf_field() ?><div id="bulkIds"></div></form>

<script>
const rowChecks = () => Array.from(document.querySelectorAll('tbody .row-check'));
function updateBulk(){
  const sel = rowChecks().filter(c => c.checked).length;
  document.getElementById('selCount').textContent = sel;
  document.getElementById('bulkBar').classList.toggle('d-none', sel === 0);
  document.getElementById('bulkBar').classList.toggle('d-flex', sel > 0);
}
document.getElementById('checkAll')?.addEventListener('change', function () {
  rowChecks().forEach(c => { c.checked = this.checked; });
  updateBulk();
});
document.addEventListener('change', function (e) {
  if (e.target.classList.contains('row-check') && e.target.id !== 'checkAll') updateBulk();
});
document.getElementById('bulkDeleteBtn')?.addEventListener('click', async function () {
  const ids = rowChecks().filter(c => c.checked).map(c => c.value);
  if (!ids.length) return;
  const ok = await window.confirmDialog('Delete ' + ids.length + ' selected contact(s)? This cannot be undone.', { danger: true, ok: 'Delete' });
  if (!ok) return;
  const box = document.getElementById('bulkIds'); box.innerHTML = '';
  ids.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; box.appendChild(i); });
  document.getElementById('bulkForm').submit();
});

// ---- Email verification (batched loop with progress) ----
const verifyListId = document.getElementById('verifyBtn')?.dataset.list || '';

async function runVerify(deep) {
  const btn = document.getElementById('verifyBtn');
  const listId = verifyListId;
  btn.disabled = true;
  const panel = document.getElementById('verifyPanel');
  panel.classList.remove('d-none');
  panel.querySelector('.fw-semibold').innerHTML = deep
    ? '<i class="bi bi-patch-check text-brand"></i> Deep-verifying mailboxes… <span class="text-muted small">(slower, confirms each inbox)</span>'
    : '<i class="bi bi-patch-check text-brand"></i> Verifying email addresses…';
  const bar = document.getElementById('verifyBar');
  const stat = document.getElementById('verifyStat');
  const tally = document.getElementById('verifyTally');
  bar.classList.add('progress-bar-animated', 'progress-bar-striped');
  let totalStart = 0;

  while (true) {
    let res;
    try {
      res = await fetch('<?= url('contacts/verify') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ list_id: listId, deep: deep ? '1' : '', _csrf: window.CSRF })
      }).then(r => r.json());
    } catch (e) { window.toast('Verification failed. Please retry.', 'error'); break; }
    if (!res.ok) { window.toast('Verification error.', 'error'); break; }

    const done = res.counts.valid + res.counts.invalid + res.counts.risky + res.counts.unknown;
    const total = done + res.remaining;
    if (!totalStart) totalStart = total;
    const pct = total ? Math.round(done / total * 100) : 100;
    bar.style.width = pct + '%';
    stat.textContent = done + ' / ' + total + ' checked';
    tally.innerHTML =
      '<span class="text-success">✓ ' + res.counts.valid + ' valid</span>' +
      '<span class="text-warning">⚠ ' + res.counts.risky + ' risky</span>' +
      '<span class="text-danger">✗ ' + res.counts.invalid + ' invalid</span>' +
      '<span class="text-muted">? ' + res.counts.unknown + ' unknown</span>';

    if (res.remaining <= 0 || res.processed === 0) break;
  }
  bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
  window.toast('Verification complete.', 'success');
  setTimeout(() => location.reload(), 1200);
}

document.getElementById('verifyBtn')?.addEventListener('click', async function () {
  // If everything is already verified, offer a full re-check.
  if (<?= $unv ?> === 0) {
    const ok = await window.confirmDialog('All contacts are already verified. Re-verify all of them again?', { ok: 'Re-verify all' });
    if (!ok) return;
    await resetVerification();
  }
  runVerify(false);
});
document.getElementById('verifyAllBtn')?.addEventListener('click', async function () {
  const ok = await window.confirmDialog('Re-verify every contact from scratch?', { ok: 'Re-verify all' });
  if (!ok) return;
  await resetVerification();
  runVerify(false);
});
document.getElementById('deepVerifyBtn')?.addEventListener('click', async function () {
  const ok = await window.confirmDialog('Deep-verify every contact — connects to mail servers to confirm each mailbox. This is accurate but slow, and needs outbound port 25 (works locally / on a VPS, but is blocked on most shared hosting). Continue?', { ok: 'Deep verify' });
  if (!ok) return;
  await resetVerification();
  runVerify(true);
});
async function resetVerification() {
  try {
    await fetch('<?= url('contacts/verify-reset') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ list_id: verifyListId, _csrf: window.CSRF })
    });
  } catch (e) { window.toast('Could not reset verification.', 'error'); }
}
function resetContactForm(){ document.getElementById('contactModalTitle').textContent='Add contact'; document.querySelector('#contactModal form').reset(); document.getElementById('contact_id').value=''; }
function editContact(c){
  document.getElementById('contactModalTitle').textContent='Edit contact';
  document.getElementById('contact_id').value=c.id;
  c_email.value=c.email; c_first.value=c.first_name||''; c_last.value=c.last_name||'';
  c_company.value=c.company||''; c_phone.value=c.phone||''; c_list.value=c.list_id||''; c_sector.value=c.sector||''; c_location.value=c.location||'';
  new bootstrap.Modal('#contactModal').show();
}
function resetListForm(){ document.getElementById('list_form_id').value=''; document.getElementById('list_name').value=''; document.getElementById('list_desc').value=''; }

</script>
