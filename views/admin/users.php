<?php /** @var array $users @var array $plans */ ?>
<div class="page-head">
  <h2>Companies / Workspaces</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('admin/plans') ?>" class="btn btn-light"><i class="bi bi-grid"></i> Plans</a>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="fw-semibold"><?= e($u['name']) ?></td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td>
              <form method="post" action="<?= url('admin/users/plan') ?>" class="d-flex gap-1">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <select class="form-select form-select-sm" name="plan_id" onchange="this.form.submit()" style="min-width:130px">
                  <option value="">— none —</option>
                  <?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) ($u['plan_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>-subtle text-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>-emphasis"><?= e($u['role']) ?></span></td>
            <td><?= (int) $u['status'] === 1 ? '<span class="status-pill status-active">Active</span>' : '<span class="status-pill status-unsubscribed">Suspended</span>' ?></td>
            <td class="small text-muted"><?= fmt_dt($u['last_login_at']) ?></td>
            <td class="text-end">
              <a href="<?= url('admin/users/view?id=' . (int) $u['id']) ?>" class="btn btn-sm btn-light" title="View details"><i class="bi bi-eye"></i></a>
              <?php if ($u['role'] !== 'admin'): ?>
                <form method="post" action="<?= url('admin/impersonate') ?>" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="btn btn-sm btn-light" title="Login as this user"><i class="bi bi-incognito"></i></button>
                </form>
                <form method="post" action="<?= url('admin/users/toggle') ?>" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="btn btn-sm btn-light"><?= (int) $u['status'] === 1 ? '<i class="bi bi-lock"></i> Suspend' : '<i class="bi bi-unlock"></i> Activate' ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
