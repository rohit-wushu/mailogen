<?php
/** @var array $rows @var array $counts @var int $total @var int $page @var int $pages @var array $filters */
$cat = function (?string $reason): array {
    $r = strtolower((string) $reason);
    if (str_contains($r, 'bounce')) return ['Bounce', 'danger'];
    if (str_contains($r, 'complaint') || str_contains($r, 'spam')) return ['Complaint', 'warning'];
    if (str_contains($r, 'manual')) return ['Manual', 'secondary'];
    return ['Unsubscribe', 'info'];
};
?>
<div class="page-head">
  <h2>Suppression List</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSup"><i class="bi bi-plus-lg"></i> Add address</button>
</div>

<p class="text-muted">These addresses are <strong>never emailed</strong> — they unsubscribed, bounced, or complained. Keeping them suppressed protects your sender reputation.</p>

<div class="row g-3 mb-3">
  <?php foreach ([['Total', $counts['total'], 'slash-circle', 'violet'], ['Unsubscribed', $counts['unsubscribe'], 'person-dash', 'sky'], ['Bounced', $counts['bounce'], 'arrow-return-left', 'red'], ['Complaints', $counts['complaint'], 'flag', 'orange']] as [$l, $v, $i, $g]): ?>
    <div class="col-6 col-xl-3">
      <div class="stat-card" style="grid-template-rows:auto;">
        <div class="stat-icon grad-<?= $g ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-<?= $i ?>"></i></div>
        <div class="stat-top"><div class="stat-label"><?= $l ?></div><div class="stat-value" style="font-size:1.4rem"><?= number_format((int) $v) ?></div></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body">
  <form method="get" action="<?= url('suppression') ?>" class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label small mb-1">Search</label>
      <div class="search-field"><i class="bi bi-search"></i><input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="email@example.com"></div>
    </div>
    <div class="col-md-4">
      <label class="form-label small mb-1">Type</label>
      <select class="form-select" name="type">
        <?php foreach (['' => 'All types', 'unsubscribe' => 'Unsubscribed', 'bounce' => 'Bounced', 'complaint' => 'Complaints'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
      <a href="<?= url('suppression') ?>" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div></div>

<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-shield-check"></i><p class="mt-2">No suppressed addresses. Unsubscribes and bounces will appear here automatically.</p></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Email</th><th>Type</th><th>Reason</th><th>Scope</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): [$label, $color] = $cat($r['reason']); ?>
            <tr>
              <td class="fw-semibold"><?= e($r['email']) ?></td>
              <td><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $label ?></span></td>
              <td class="small text-muted"><?= e($r['reason'] ?: '—') ?></td>
              <td class="small"><?= $r['campaign_id'] ? 'Campaign #' . (int) $r['campaign_id'] : '<span class="text-muted">Global</span>' ?></td>
              <td class="small text-nowrap"><?= fmt_dt($r['created_at']) ?></td>
              <td class="text-end">
                <form method="post" action="<?= url('suppression/remove') ?>" class="d-inline" data-confirm="Remove <?= e($r['email']) ?> from suppression? They could be emailed again.">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button class="btn btn-sm btn-light text-danger" title="Remove from suppression"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
  <nav class="mt-3 d-flex justify-content-between align-items-center">
    <span class="text-muted small">Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> addresses</span>
    <div class="btn-group">
      <a class="btn btn-light <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= url('suppression?page=' . ($page - 1) . '&type=' . e($filters['type']) . '&q=' . urlencode($filters['q'])) ?>"><i class="bi bi-chevron-left"></i></a>
      <a class="btn btn-light <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= url('suppression?page=' . ($page + 1) . '&type=' . e($filters['type']) . '&q=' . urlencode($filters['q'])) ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
  </nav>
<?php endif; ?>

<!-- Add modal -->
<div class="modal fade" id="addSup" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="<?= url('suppression/add') ?>">
    <div class="modal-header"><h5 class="modal-title">Add to suppression list</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <?= csrf_field() ?>
      <label class="form-label">Email address</label>
      <input type="email" name="email" class="form-control" placeholder="someone@example.com" required>
      <div class="form-text">This address will be excluded from all future sends.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Add</button></div>
  </form>
</div></div></div>
