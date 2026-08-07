<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $q */
$cat = function (?string $reason): array {
    $r = strtolower((string) $reason);
    if (str_contains($r, 'bounce')) return ['Bounce', 'danger'];
    if (str_contains($r, 'complaint') || str_contains($r, 'spam')) return ['Complaint', 'warning'];
    return ['Manual', 'secondary'];
};
?>
<div class="page-head">
  <h2>Global Suppression List</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSup"><i class="bi bi-plus-lg"></i> Add address</button>
</div>

<p class="text-muted">Platform-wide — these addresses can't be emailed by <strong>any</strong> tenant. A hard bounce or spam complaint from any tenant's send lands here automatically, protecting the shared Amazon SES sender reputation.</p>

<div class="card mb-3"><div class="card-body">
  <form method="get" action="<?= url('admin/suppression') ?>" class="row g-2 align-items-end">
    <div class="col-md-8">
      <label class="form-label small mb-1">Search</label>
      <div class="search-field"><i class="bi bi-search"></i><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="email@example.com"></div>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Search</button>
      <a href="<?= url('admin/suppression') ?>" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
    </div>
  </form>
</div></div>

<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-shield-check"></i><p class="mt-2">No platform-wide suppressions yet. Bounces and complaints from any tenant will appear here automatically.</p></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Email</th><th>Type</th><th>Source tenant</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): [$label, $color] = $cat($r['reason']); ?>
            <tr>
              <td class="fw-semibold"><?= e($r['email']) ?></td>
              <td><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $label ?></span></td>
              <td class="small text-muted"><?= $r['source_email'] ? e($r['source_name'] . ' · ' . $r['source_email']) : '—' ?></td>
              <td class="small text-nowrap"><?= fmt_dt($r['created_at']) ?></td>
              <td class="text-end">
                <form method="post" action="<?= url('admin/suppression/remove') ?>" class="d-inline" data-confirm="Remove <?= e($r['email']) ?> from the platform suppression list? Every tenant could email it again.">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button class="btn btn-sm btn-light text-danger" title="Remove"><i class="bi bi-trash"></i></button>
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
      <a class="btn btn-light <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= url('admin/suppression?page=' . ($page - 1) . '&q=' . urlencode($q)) ?>"><i class="bi bi-chevron-left"></i></a>
      <a class="btn btn-light <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= url('admin/suppression?page=' . ($page + 1) . '&q=' . urlencode($q)) ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
  </nav>
<?php endif; ?>

<div class="modal fade" id="addSup" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="<?= url('admin/suppression/add') ?>">
    <div class="modal-header"><h5 class="modal-title">Add to platform suppression list</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <?= csrf_field() ?>
      <label class="form-label">Email address</label>
      <input type="email" name="email" class="form-control" placeholder="someone@example.com" required>
      <div class="form-text">No tenant will be able to send to this address.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Add</button></div>
  </form>
</div></div></div>
