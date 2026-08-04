<?php
/** @var array $plans @var array $current @var bool $active @var array $usage @var array $payments @var ?array $pendingReq */
$user = $GLOBALS['user'] ?? ($user ?? []);
?>
<div class="page-head">
  <h2>Billing &amp; Plans</h2>
</div>

<?php if (!empty($pendingReq)): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-hourglass-split fs-5"></i>
    <div>Your request to subscribe to the <strong><?= e($pendingReq['plan_name']) ?></strong> plan is
      <strong>awaiting admin approval</strong>. We'll upgrade your account as soon as it's approved.</div>
  </div>
<?php endif; ?>

<!-- Current plan + usage -->
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="text-muted small text-uppercase">Current plan</div>
        <h3 class="mb-1"><?= e($current['name']) ?>
          <?php if (!empty($user['plan_id']) && $active): ?>
            <span class="badge bg-success-subtle text-success-emphasis">Active</span>
          <?php elseif (!empty($user['plan_id']) && !$active): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis">Expired — on free limits</span>
          <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">Free</span>
          <?php endif; ?>
        </h3>
        <?php if (!empty($user['plan_expires_at']) && $active): ?>
          <div class="text-muted small">Renews / expires on <strong><?= fmt_dt($user['plan_expires_at']) ?></strong></div>
        <?php endif; ?>
      </div>
      <div class="text-end">
        <div class="display-6 fw-bold"><?= (float) $current['price_monthly'] > 0 ? e(BILLING_CURRENCY) . ' ' . number_format((float) $current['price_monthly']) : 'Free' ?></div>
        <?php if ((float) $current['price_monthly'] > 0): ?><div class="text-muted small">per month</div><?php endif; ?>
      </div>
    </div>

    <hr>
    <div class="row g-3">
      <?php foreach ($usage as $u): ?>
        <div class="col-6 col-lg-3">
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted"><i class="bi bi-<?= e($u['icon']) ?> me-1"></i><?= e($u['label']) ?></span>
            <span class="fw-semibold">
              <?= number_format($u['used']) ?> / <?= $u['unlimited'] ? '∞' : number_format($u['limit']) ?>
            </span>
          </div>
          <div class="progress" style="height:7px">
            <div class="progress-bar <?= $u['pct'] >= 90 ? 'bg-danger' : ($u['pct'] >= 70 ? 'bg-warning' : 'bg-success') ?>"
                 style="width:<?= $u['unlimited'] ? 6 : max(3, $u['pct']) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Plan cards -->
<h4 class="mb-3">Choose a plan</h4>
<div class="row g-4 mb-4">
  <?php foreach ($plans as $p):
    $isCurrent = (int) ($user['plan_id'] ?? 0) === (int) $p['id'] && $active;
    $price = (float) $p['price_monthly'];
    $cardHi = $isCurrent || !empty($p['is_featured']);
    $cardBadge = $isCurrent ? 'Current plan' : (!empty($p['is_featured']) ? 'Most popular' : null);

    $reqPending = !empty($pendingReq) && (int) $pendingReq['plan_id'] === (int) $p['id'];
    ob_start(); ?>
      <?php if ($isCurrent): ?>
        <button class="btn btn-light w-100" disabled><i class="bi bi-check2-circle"></i> Your current plan</button>
      <?php elseif ($reqPending): ?>
        <button class="btn btn-warning w-100" disabled><i class="bi bi-hourglass-split"></i> Pending approval</button>
      <?php elseif (!empty($pendingReq)): ?>
        <button class="btn btn-light w-100" disabled>Request pending</button>
      <?php else: ?>
        <form method="post" action="<?= url('billing/request') ?>"
              <?= $price > 0 ? 'data-confirm="Send a request to subscribe to the ' . e($p['name']) . ' plan? An admin will review and approve it."' : '' ?>>
          <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
          <button class="btn <?= $price <= 0 ? 'btn-outline-secondary' : 'btn-primary' ?> w-100"><?= e($p['cta_label'] ?: ($price <= 0 ? 'Switch to Free' : 'Subscribe')) ?></button>
        </form>
      <?php endif; ?>
    <?php $cardFooter = ob_get_clean(); ?>
    <div class="col-lg-4 col-md-6">
      <?php require BASE_PATH . '/views/partials/pricing_card.php'; ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- Payment history -->
<div class="card">
  <div class="card-header"><i class="bi bi-receipt me-1"></i> Payment history</div>
  <?php if (!$payments): ?>
    <div class="card-body text-muted">No payments yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td class="small"><?= fmt_dt($pay['created_at']) ?></td>
              <td><?= e($pay['plan_name']) ?> <span class="text-muted small">(<?= (int) $pay['period_months'] ?>mo)</span></td>
              <td><?= e($pay['currency']) ?> <?= number_format((float) $pay['amount'], 2) ?></td>
              <td class="text-capitalize"><?= e($pay['gateway']) ?></td>
              <td><?= status_badge($pay['status'] === 'paid' ? 'completed' : ($pay['status'] === 'failed' ? 'failed' : 'draft')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-check"></i> Paid plans are activated by our team after your request is approved. You'll see the change here once it's done.</p>
