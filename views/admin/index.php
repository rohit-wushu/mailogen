<?php /** @var array $stats @var array $users */ $__pending = PlanRequest::countPending(); ?>
<div class="page-head">
  <h2>Admin Overview</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('admin/requests') ?>" class="btn btn-light position-relative">
      <i class="bi bi-bell"></i> Requests
      <?php if ($__pending > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $__pending ?></span><?php endif; ?>
    </a>
    <a href="<?= url('admin/users') ?>" class="btn btn-light"><i class="bi bi-people"></i> Users</a>
    <a href="<?= url('admin/plans') ?>" class="btn btn-light"><i class="bi bi-grid"></i> Plans</a>
    <a href="<?= url('admin/logs') ?>" class="btn btn-primary"><i class="bi bi-journal-text"></i> Logs</a>
  </div>
</div>

<?php if ($__pending > 0): ?>
  <div class="alert alert-warning d-flex align-items-center justify-content-between gap-2">
    <span><i class="bi bi-bell-fill me-1"></i> <strong><?= (int) $__pending ?></strong> subscription request<?= $__pending === 1 ? '' : 's' ?> awaiting approval.</span>
    <a href="<?= url('admin/requests') ?>" class="btn btn-sm btn-warning">Review now</a>
  </div>
<?php endif; ?>

<?php
$cards = [
  ['label' => 'Users',         'value' => number_format((int) $stats['users']),                 'icon' => 'people-fill',       'grad' => 'violet'],
  ['label' => 'Campaigns',     'value' => number_format((int) $stats['campaigns']),             'icon' => 'megaphone-fill',    'grad' => 'pink'],
  ['label' => 'Emails Sent',   'value' => number_format((int) $stats['sent']),                  'icon' => 'send-check-fill',   'grad' => 'green'],
  ['label' => 'SMTP Accounts', 'value' => number_format((int) $stats['smtp']),                  'icon' => 'hdd-network-fill',  'grad' => 'sky'],
  ['label' => 'Contacts',      'value' => number_format((int) $stats['contacts']),              'icon' => 'person-lines-fill', 'grad' => 'orange'],
  ['label' => 'MRR',           'value' => '$' . number_format((float) $stats['revenue'], 2),    'icon' => 'cash-stack',        'grad' => 'blue'],
];
?>
<?= render_stat_cards($cards, 2) ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Newest users</span>
    <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-light">Manage all</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Role</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="fw-semibold"><?= e($u['name']) ?></td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td><?= e($u['plan_name'] ?? '—') ?></td>
            <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>-subtle text-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>-emphasis"><?= e($u['role']) ?></span></td>
            <td class="small text-muted"><?= fmt_dt($u['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
