<?php /** @var array $workflows */
$grads = ['grad-violet', 'grad-blue', 'grad-green', 'grad-orange', 'grad-pink', 'grad-teal', 'grad-sky', 'grad-red'];
?>
<div class="page-head">
  <h2>Automations</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('automations/edit') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New automation</a>
  </div>
</div>

<?php if (!$workflows): ?>
  <div class="card"><div class="empty-state"><i class="bi bi-diagram-3"></i>
    <p class="mt-2">No automations yet. Build a drip sequence: <strong>Email &rarr; Wait &rarr; Follow-up &rarr; &hellip;</strong></p>
    <a href="<?= url('automations/edit') ?>" class="btn btn-primary mt-2"><i class="bi bi-plus-lg"></i> Create automation</a></div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($workflows as $i => $w): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-start gap-3 mb-3">
              <span class="stat-icon <?= $grads[$i % count($grads)] ?>" style="width:44px;height:44px;font-size:1.1rem;border-radius:12px;display:grid;place-items:center;color:#fff;flex:0 0 auto"><i class="bi bi-diagram-3"></i></span>
              <div class="flex-grow-1 min-w-0">
                <h6 class="fw-bold mb-1 text-truncate"><?= e($w['name']) ?></h6>
                <?= status_badge($w['status']) ?>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
              <span><i class="bi bi-list-ol text-brand"></i> <strong class="text-body"><?= (int) $w['step_count'] ?></strong> steps</span>
              <span><i class="bi bi-people text-brand"></i> <strong class="text-body"><?= (int) $w['active_count'] ?></strong> enrolled</span>
            </div>
            <p class="small text-muted mb-0"><i class="bi bi-lightning-charge"></i> Trigger: <?= e(str_replace('_', ' ', $w['trigger_type'])) ?></p>
          </div>
          <div class="card-footer bg-transparent d-flex gap-2">
            <a href="<?= url('automations/edit?id=' . $w['id']) ?>" class="btn btn-sm btn-light flex-grow-1"><i class="bi bi-pencil"></i> Edit</a>
            <?php if ($w['status'] === 'active'): ?>
              <form method="post" action="<?= url('automations/pause') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-sm btn-warning" title="Pause"><i class="bi bi-pause"></i></button></form>
            <?php else: ?>
              <form method="post" action="<?= url('automations/activate') ?>" data-confirm="Activate and enrol all contacts in the list?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-sm btn-success" title="Activate"><i class="bi bi-play"></i></button></form>
            <?php endif; ?>
            <form method="post" action="<?= url('automations/delete') ?>" data-confirm="Delete this automation?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
