<?php /** @var array $requests @var int $pending */ ?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Subscription Requests</h2>
    <p class="text-muted mb-0">Approve or reject plan upgrade requests from your users.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('admin/plans') ?>" class="btn btn-light"><i class="bi bi-credit-card"></i> Plans</a>
    <a href="<?= url('admin/users') ?>" class="btn btn-light"><i class="bi bi-people"></i> Users</a>
  </div>
</div>

<?php if ($pending > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-bell-fill"></i> <strong><?= (int) $pending ?></strong> request<?= $pending === 1 ? '' : 's' ?> awaiting your approval.
  </div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr>
        <th>User</th><th>Plan</th><th>Amount</th><th>Period</th><th>Requested</th><th>Status</th><th class="text-end">Action</th>
      </tr></thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p class="mt-2">No subscription requests yet.</p></div></td></tr>
        <?php else: foreach ($requests as $r): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($r['user_name']) ?></div>
              <div class="text-muted small"><?= e($r['user_email']) ?></div>
            </td>
            <td><span class="fw-semibold"><?= e($r['plan_name']) ?></span></td>
            <td><?= e($r['currency']) ?> <?= number_format((float) $r['amount'], 2) ?></td>
            <td><?= (int) $r['period_months'] ?> mo</td>
            <td class="small text-muted"><?= fmt_dt($r['created_at']) ?></td>
            <td>
              <?php
                $map = ['pending' => ['warning', 'Pending'], 'approved' => ['success', 'Approved'], 'rejected' => ['secondary', 'Rejected']];
                [$c, $lbl] = $map[$r['status']] ?? ['secondary', ucfirst($r['status'])]; ?>
              <span class="badge bg-<?= $c ?>-subtle text-<?= $c ?>-emphasis"><?= $lbl ?></span>
              <?php if ($r['status'] !== 'pending' && $r['decided_at']): ?>
                <div class="text-muted small mt-1"><?= fmt_dt($r['decided_at']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($r['status'] === 'pending'): ?>
                <div class="d-inline-flex gap-2">
                  <form method="post" action="<?= url('admin/requests/approve') ?>"
                        data-confirm="Approve and activate the <?= e($r['plan_name']) ?> plan for <?= e($r['user_name']) ?>?">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
                  </form>
                  <form method="post" action="<?= url('admin/requests/reject') ?>"
                        data-confirm="Reject this request? The user stays on their current plan.">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i> Reject</button>
                  </form>
                </div>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
