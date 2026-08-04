<?php /** @var array $logs */ ?>
<div class="page-head">
  <h2>System Logs</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('admin') ?>" class="btn btn-light"><i class="bi bi-speedometer2"></i> Overview</a>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Level</th><th>Context</th><th>Message</th><th>When</th></tr></thead>
      <tbody>
        <?php if (!$logs): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-journal-text"></i><p class="mt-2">No system logs.</p></div></td></tr>
        <?php else: foreach ($logs as $l):
          $lc = ['info' => 'info', 'warning' => 'warning', 'error' => 'danger'][$l['level']] ?? 'secondary'; ?>
          <tr>
            <td><span class="badge bg-<?= $lc ?>-subtle text-<?= $lc ?>-emphasis text-uppercase"><?= e($l['level']) ?></span></td>
            <td class="small text-muted"><?= e($l['context'] ?? '') ?></td>
            <td class="small"><?= e($l['message']) ?></td>
            <td class="small text-muted text-nowrap"><?= fmt_dt($l['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
