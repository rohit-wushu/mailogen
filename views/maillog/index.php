<?php
/** @var array $rows @var array $counts @var array $campaigns @var int $total @var int $page @var int $pages @var array $filters */
$qs = function (array $over = []) use ($filters) {
    $p = array_filter(array_merge(['status' => $filters['status'], 'campaign_id' => $filters['campaign_id'] ?: '', 'q' => $filters['q']], $over), fn($v) => $v !== '' && $v !== 0);
    return $p ? '?' . http_build_query($p) : '';
};
?>
<div class="page-head">
  <h2>Sent Emails</h2>
  <a href="<?= url('reports') ?>" class="btn btn-light"><i class="bi bi-bar-chart"></i> Reports</a>
</div>

<!-- Headline counts -->
<div class="row g-3 mb-3">
  <?php
  $cards = [
    ['Total', (int) $counts['total'], 'envelope', 'violet'],
    ['Delivered', (int) $counts['sent'], 'check-circle', 'green'],
    ['Failed', (int) $counts['failed'], 'x-octagon', 'red'],
    ['Pending', (int) $counts['pending'], 'hourglass-split', 'orange'],
  ];
  foreach ($cards as [$l, $v, $i, $g]): ?>
    <div class="col-6 col-xl-3">
      <div class="stat-card" style="grid-template-rows:auto;">
        <div class="stat-icon grad-<?= $g ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-<?= $i ?>"></i></div>
        <div class="stat-top"><div class="stat-label"><?= $l ?></div><div class="stat-value" style="font-size:1.4rem"><?= number_format($v) ?></div></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="<?= url('emails') ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-1">Search recipient or subject</label>
        <div class="search-field"><i class="bi bi-search"></i>
          <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="name@example.com or subject…">
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1">Campaign</label>
        <select class="form-select" name="campaign_id">
          <option value="">All campaigns</option>
          <?php foreach ($campaigns as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['campaign_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select class="form-select" name="status">
          <?php foreach (['' => 'Any', 'sent' => 'Delivered', 'failed' => 'Failed', 'queued' => 'Queued', 'sending' => 'Sending'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
        <a href="<?= url('emails') ?>" class="btn btn-light" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Log table -->
<div class="card">
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-envelope"></i><p class="mt-2">No emails match. Once you send a campaign, every message shows up here.</p></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Recipient</th><th>Subject</th><th>Campaign</th><th>SMTP</th>
            <th>Sent</th><th>Status</th><th>Engagement</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="fw-semibold text-truncate" style="max-width:200px"><?= e($r['email']) ?></td>
              <td class="text-truncate" style="max-width:220px"><?= e($r['subject'] ?: '—') ?></td>
              <td>
                <?php if ($r['workflow_id']): ?>
                  <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-diagram-3"></i> Automation</span>
                <?php elseif ($r['step'] > 0): ?>
                  <span class="badge bg-secondary-subtle text-secondary-emphasis">Follow-up #<?= (int) $r['step'] ?></span>
                <?php else: ?>
                  <?= e($r['campaign_name'] ?? '—') ?>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e($r['smtp_label'] ?? '—') ?></td>
              <td class="small text-nowrap"><?= $r['sent_at'] ? fmt_dt($r['sent_at']) : '<span class="text-muted">—</span>' ?></td>
              <td>
                <?= status_badge($r['status'] === 'sent' ? 'sent' : $r['status']) ?>
                <?php if ($r['status'] === 'failed' && $r['error']): ?>
                  <i class="bi bi-info-circle text-danger" title="<?= e($r['error']) ?>"></i>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int) $r['opens'] > 0): ?><span class="badge bg-success-subtle text-success-emphasis me-1" title="Opened"><i class="bi bi-eye"></i> <?= (int) $r['opens'] ?></span><?php endif; ?>
                <?php if ((int) $r['clicks'] > 0): ?><span class="badge bg-primary-subtle text-primary-emphasis" title="Clicked"><i class="bi bi-cursor"></i> <?= (int) $r['clicks'] ?></span><?php endif; ?>
                <?php if ((int) $r['opens'] === 0 && (int) $r['clicks'] === 0): ?><span class="text-muted small">—</span><?php endif; ?>
              </td>
              <td class="text-end">
                <a href="<?= url('emails/view?id=' . (int) $r['id']) ?>" target="_blank" class="btn btn-sm btn-light" title="View the email that was sent"><i class="bi bi-eye"></i></a>
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
    <span class="text-muted small">Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> emails</span>
    <div class="btn-group">
      <a class="btn btn-light <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= url('emails') . $qs(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a>
      <a class="btn btn-light <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= url('emails') . $qs(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
  </nav>
<?php endif; ?>
