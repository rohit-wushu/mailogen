<?php
/** @var array $platform @var array $risky @var array $recent */
// Mirrors the thresholds in lib/Reputation.php — display-only.
$bounceBad    = $platform['bounce_rate'] > 0.05;
$complaintBad = $platform['complaint_rate'] > 0.001;
?>
<div class="page-head">
  <h2>Deliverability</h2>
</div>

<p class="text-muted">Every tenant's domain-based campaign shares the one Amazon SES connection, so one tenant's bad list can burn sender reputation for everyone. This tracks bounce/complaint health platform-wide and flags tenants pushing the shared reputation into risk.</p>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-blue" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-graph-up"></i></div>
      <div class="stat-top"><div class="stat-label">Sample (recent sends)</div><div class="stat-value" style="font-size:1.4rem"><?= number_format($platform['sample']) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-<?= $bounceBad ? 'red' : 'green' ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-arrow-return-left"></i></div>
      <div class="stat-top"><div class="stat-label">Bounce rate</div><div class="stat-value" style="font-size:1.4rem"><?= number_format($platform['bounce_rate'] * 100, 2) ?>%</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-<?= $complaintBad ? 'red' : 'green' ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-flag"></i></div>
      <div class="stat-top"><div class="stat-label">Complaint rate</div><div class="stat-value" style="font-size:1.4rem"><?= number_format($platform['complaint_rate'] * 100, 3) ?>%</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-<?= ($bounceBad || $complaintBad) ? 'red' : 'green' ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-<?= ($bounceBad || $complaintBad) ? 'exclamation-triangle' : 'shield-check' ?>"></i></div>
      <div class="stat-top"><div class="stat-label">Platform status</div><div class="stat-value" style="font-size:1.1rem"><?= ($bounceBad || $complaintBad) ? 'At risk' : 'Healthy' ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-exclamation-triangle text-brand me-1"></i> At-risk tenants (last 30 days)</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($risky) ?></span>
      </div>
      <?php if (!$risky): ?>
        <div class="empty-state py-4"><i class="bi bi-shield-check"></i><p class="mt-2 mb-0">No tenant is over the bounce/complaint threshold right now.</p></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Tenant</th><th>Sample</th><th>Bounce</th><th>Complaint</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($risky as $t): ?>
                <tr>
                  <td><div class="fw-semibold"><?= e($t['name']) ?></div><div class="small text-muted"><?= e($t['email']) ?></div></td>
                  <td><?= number_format($t['sample']) ?></td>
                  <td><span class="text-<?= $t['bounce_rate'] > 0.05 ? 'danger' : 'muted' ?>"><?= number_format($t['bounce_rate'] * 100, 1) ?>%</span></td>
                  <td><span class="text-<?= $t['complaint_rate'] > 0.001 ? 'danger' : 'muted' ?>"><?= number_format($t['complaint_rate'] * 100, 2) ?>%</span></td>
                  <td class="text-end"><a class="btn btn-sm btn-light" href="<?= url('admin/users/view?id=' . $t['user_id']) ?>">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-list-ul text-brand me-1"></i> Recent bounce / complaint events</div>
      <?php if (!$recent): ?>
        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0">No bounce or complaint events recorded yet.</p></div>
      <?php else: ?>
        <div class="table-responsive" style="max-height:420px">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Tenant</th><th>Email</th><th>Event</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
                <tr>
                  <td class="small"><?= e($r['user_name']) ?></td>
                  <td class="small text-muted"><?= e($r['email']) ?></td>
                  <td>
                    <?php if ($r['event'] === 'complained'): ?>
                      <span class="badge bg-warning-subtle text-warning-emphasis">Complaint</span>
                    <?php else: ?>
                      <span class="badge bg-<?= $r['bounce_type'] === 'hard' ? 'danger' : 'secondary' ?>-subtle text-<?= $r['bounce_type'] === 'hard' ? 'danger' : 'secondary' ?>-emphasis"><?= ucfirst((string) $r['bounce_type']) ?> bounce</span>
                    <?php endif; ?>
                  </td>
                  <td class="small text-nowrap text-muted"><?= fmt_dt($r['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
