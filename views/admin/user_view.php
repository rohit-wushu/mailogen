<?php
/** @var array $target @var ?array $plan @var array $domains @var int $campaignCount
 *  @var array $recentCampaigns @var int $contactCount @var int $smtpCount
 *  @var int $sentCount @var array $rates */
?>
<div class="page-head">
  <div>
    <h2 class="mb-1"><?= e($target['name']) ?></h2>
    <p class="text-muted mb-0"><?= e($target['email']) ?> <?= $target['company'] ? '· ' . e($target['company']) : '' ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('admin/users') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> All companies</a>
    <?php if ($target['role'] !== 'admin'): ?>
      <form method="post" action="<?= url('admin/impersonate') ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $target['id'] ?>">
        <button class="btn btn-primary"><i class="bi bi-incognito"></i> Login as this user</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-violet" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-send-check-fill"></i></div>
      <div class="stat-top"><div class="stat-label">Emails sent</div><div class="stat-value" style="font-size:1.4rem"><?= number_format($sentCount) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-pink" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-megaphone-fill"></i></div>
      <div class="stat-top"><div class="stat-label">Campaigns</div><div class="stat-value" style="font-size:1.4rem"><?= number_format($campaignCount) ?></div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= url('admin/users/contacts?id=' . (int) $target['id']) ?>" class="stat-card text-decoration-none" style="grid-template-rows:auto;">
      <div class="stat-icon grad-orange" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-person-lines-fill"></i></div>
      <div class="stat-top"><div class="stat-label">Contacts <i class="bi bi-arrow-up-right small"></i></div><div class="stat-value" style="font-size:1.4rem"><?= number_format($contactCount) ?></div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="grid-template-rows:auto;">
      <div class="stat-icon grad-<?= ($rates['bounce_rate'] > 0.05 || $rates['complaint_rate'] > 0.001) ? 'red' : 'green' ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-activity"></i></div>
      <div class="stat-top"><div class="stat-label">Bounce / complaint</div><div class="stat-value" style="font-size:1.1rem"><?= $rates['sample'] > 0 ? number_format($rates['bounce_rate'] * 100, 1) . '% / ' . number_format($rates['complaint_rate'] * 100, 2) . '%' : '—' ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-person me-1"></i> Account</div>
      <div class="card-body">
        <dl class="row mb-0 small">
          <dt class="col-5 text-muted">Status</dt>
          <dd class="col-7"><?= (int) $target['status'] === 1 ? '<span class="status-pill status-active">Active</span>' : '<span class="status-pill status-unsubscribed">Suspended</span>' ?></dd>
          <dt class="col-5 text-muted">Role</dt>
          <dd class="col-7"><span class="badge bg-<?= $target['role'] === 'admin' ? 'danger' : 'secondary' ?>-subtle text-<?= $target['role'] === 'admin' ? 'danger' : 'secondary' ?>-emphasis"><?= e($target['role']) ?></span></dd>
          <dt class="col-5 text-muted">Plan</dt>
          <dd class="col-7"><?= $plan ? e($plan['name']) : '<span class="text-muted">—</span>' ?></dd>
          <dt class="col-5 text-muted">Email verified</dt>
          <dd class="col-7"><?= (int) $target['is_verified'] === 1 ? 'Yes' : 'No' ?></dd>
          <dt class="col-5 text-muted">Joined</dt>
          <dd class="col-7"><?= fmt_dt($target['created_at']) ?></dd>
          <dt class="col-5 text-muted">Last login</dt>
          <dd class="col-7"><?= $target['last_login_at'] ? fmt_dt($target['last_login_at']) : '<span class="text-muted">Never</span>' ?></dd>
        </dl>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-patch-check me-1"></i> Sending domains</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($domains) ?></span>
      </div>
      <?php if (!$domains): ?>
        <div class="empty-state py-4"><i class="bi bi-globe"></i><p class="mt-2 mb-0">No sending domain added yet.</p></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Domain</th><th>SPF</th><th>DKIM</th><th>DMARC</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($domains as $d): ?>
                <tr>
                  <td class="fw-semibold"><?= e($d['domain']) ?></td>
                  <td><i class="bi bi-<?= $d['spf_verified'] ? 'check-circle-fill text-success' : 'x-circle text-muted' ?>"></i></td>
                  <td><i class="bi bi-<?= $d['dkim_verified'] ? 'check-circle-fill text-success' : 'x-circle text-muted' ?>"></i></td>
                  <td><i class="bi bi-<?= $d['dmarc_verified'] ? 'check-circle-fill text-success' : 'x-circle text-muted' ?>"></i></td>
                  <td><?= $d['is_verified'] ? '<span class="status-pill status-active">Verified</span>' : '<span class="status-pill status-unsubscribed">Pending</span>' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-hdd-network me-1"></i> Sending setup</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
          <span class="text-muted">SMTP accounts</span><span class="fw-semibold"><?= number_format($smtpCount) ?></span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted">Verified sending domains</span><span class="fw-semibold"><?= count(array_filter($domains, static fn ($d) => (int) $d['is_verified'] === 1)) ?> / <?= count($domains) ?></span>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-megaphone me-1"></i> Recent campaigns</div>
      <?php if (!$recentCampaigns): ?>
        <div class="empty-state py-4"><i class="bi bi-send"></i><p class="mt-2 mb-0">No campaigns yet.</p></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Status</th><th>Sent</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentCampaigns as $c): ?>
                <tr>
                  <td class="fw-semibold"><?= e($c['name']) ?></td>
                  <td class="small text-muted"><?= e(ucfirst((string) $c['status'])) ?></td>
                  <td><?= number_format((int) ($c['sent_count'] ?? 0)) ?></td>
                  <td class="small text-nowrap text-muted"><?= fmt_dt($c['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
