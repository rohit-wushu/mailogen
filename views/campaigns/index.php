<?php /** @var array $campaigns */ ?>
<div class="page-head">
  <h2>Campaigns</h2>
  <a href="<?= url('campaigns/edit') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New campaign</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Campaign</th><th>Status</th><th>Recipients</th><th>Sent</th><th>Opens</th><th>Clicks</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$campaigns): ?>
          <tr><td colspan="8"><div class="empty-state"><i class="bi bi-megaphone"></i><p class="mt-2">No campaigns yet.</p>
          <a href="<?= url('campaigns/edit') ?>" class="btn btn-primary mt-2">Create a campaign</a></div></td></tr>
        <?php else: foreach ($campaigns as $c): ?>
          <tr>
            <td><a href="<?= url('campaigns/show?id=' . $c['id']) ?>" class="fw-semibold text-brand"><?= e($c['name']) ?></a></td>
            <td><?= status_badge($c['status']) ?></td>
            <td><?= (int) $c['total_recipients'] ?></td>
            <td><?= (int) $c['sent_count'] ?></td>
            <td><?= (int) $c['opens'] ?></td>
            <td><?= (int) $c['clicks'] ?></td>
            <td class="small text-muted"><?= fmt_dt($c['created_at']) ?></td>
            <td class="text-end text-nowrap">
              <a href="<?= url('campaigns/show?id=' . $c['id']) ?>" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
              <a href="<?= url('campaigns/edit?id=' . $c['id']) ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('campaigns/delete') ?>" class="d-inline" data-confirm="Delete this campaign?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
