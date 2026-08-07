<?php
/** @var array $target @var array $rows @var int $total @var int $page @var int $pages @var string $q */
$statusColor = ['active' => 'success', 'unsubscribed' => 'secondary', 'bounced' => 'danger'];
$verifyColor = ['valid' => 'success', 'risky' => 'warning', 'invalid' => 'danger', 'unknown' => 'secondary', 'unverified' => 'secondary'];
?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Contacts — <?= e($target['name']) ?></h2>
    <p class="text-muted mb-0"><?= number_format($total) ?> total · read-only</p>
  </div>
  <a href="<?= url('admin/users/view?id=' . (int) $target['id']) ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to tenant</a>
</div>

<div class="card mb-3"><div class="card-body">
  <form method="get" action="<?= url('admin/users/contacts') ?>" class="row g-2 align-items-end">
    <input type="hidden" name="id" value="<?= (int) $target['id'] ?>">
    <div class="col-md-8">
      <label class="form-label small mb-1">Search</label>
      <div class="search-field"><i class="bi bi-search"></i><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="name, email or company"></div>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Search</button>
      <a href="<?= url('admin/users/contacts?id=' . (int) $target['id']) ?>" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div></div>

<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state py-4"><i class="bi bi-person-lines-fill"></i><p class="mt-2 mb-0">No contacts match.</p></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Company</th><th>Status</th><th>Verification</th><th>Added</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $c): $sc = $statusColor[$c['status']] ?? 'secondary'; $vc = $verifyColor[$c['verify_status']] ?? 'secondary'; ?>
            <tr>
              <td class="fw-semibold"><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?: '<span class="text-muted">—</span>' ?></td>
              <td class="text-muted"><?= e($c['email']) ?></td>
              <td class="small text-muted"><?= e($c['company'] ?: '—') ?></td>
              <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>-emphasis"><?= e(ucfirst($c['status'])) ?></span></td>
              <td><span class="badge bg-<?= $vc ?>-subtle text-<?= $vc ?>-emphasis"><?= e(ucfirst($c['verify_status'])) ?></span></td>
              <td class="small text-nowrap text-muted"><?= fmt_dt($c['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
  <nav class="mt-3 d-flex justify-content-between align-items-center">
    <span class="text-muted small">Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> contacts</span>
    <div class="btn-group">
      <a class="btn btn-light <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= url('admin/users/contacts?id=' . (int) $target['id'] . '&page=' . ($page - 1) . '&q=' . urlencode($q)) ?>"><i class="bi bi-chevron-left"></i></a>
      <a class="btn btn-light <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= url('admin/users/contacts?id=' . (int) $target['id'] . '&page=' . ($page + 1) . '&q=' . urlencode($q)) ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
  </nav>
<?php endif; ?>
