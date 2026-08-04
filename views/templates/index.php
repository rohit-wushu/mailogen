<?php /** @var array $templates */
$grads = [
  'linear-gradient(135deg,#6d5efc,#8b5cf6)', 'linear-gradient(135deg,#0ea5e9,#2563eb)',
  'linear-gradient(135deg,#10b981,#059669)', 'linear-gradient(135deg,#f59e0b,#f97316)',
  'linear-gradient(135deg,#ec4899,#f43f5e)', 'linear-gradient(135deg,#14b8a6,#06b6d4)',
  'linear-gradient(135deg,#8b5cf6,#6366f1)', 'linear-gradient(135deg,#64748b,#334155)',
];
?>
<div class="page-head">
  <h2>Templates</h2>
  <a href="<?= url('templates/edit') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create Template</a>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
  <div class="seg-tabs">
    <button class="active">All Templates</button>
    <button onclick="return false">My Templates</button>
    <button onclick="return false">Favorites</button>
  </div>
  <div class="d-flex gap-2">
    <div class="search-field"><i class="bi bi-search"></i><input class="form-control" id="tplSearch" placeholder="Search templates..." style="min-width:240px"></div>
    <select class="form-select" style="max-width:170px"><option>All Categories</option><option>Newsletter</option><option>Promotion</option><option>Transactional</option></select>
  </div>
</div>

<?php if (!$templates): ?>
  <div class="card"><div class="empty-state"><i class="bi bi-file-richtext"></i><p class="mt-2">No templates yet. Build a reusable email design.</p>
  <a href="<?= url('templates/edit') ?>" class="btn btn-primary mt-2">Create your first template</a></div></div>
<?php else: ?>
  <div class="row g-3" id="tplGrid">
    <?php foreach ($templates as $i => $t): ?>
      <div class="col-6 col-md-4 col-xl-3 tpl-item" data-name="<?= e(strtolower($t['name'] . ' ' . $t['subject'])) ?>">
        <div class="tmpl-card">
          <a href="<?= url('templates/edit?id=' . $t['id']) ?>" class="tmpl-thumb d-block" style="background:<?= $grads[$i % count($grads)] ?>">
            <?php if (!empty($t['thumbnail'])): ?>
              <img src="<?= e($t['thumbnail']) ?>" alt="<?= e($t['name']) ?>" loading="lazy"
                   style="width:100%;height:100%;object-fit:cover;display:block">
            <?php else: ?>
              <div class="tmpl-mini">
                <div style="font-weight:700;font-size:.66rem;border-bottom:1px solid #eee;padding-bottom:4px;margin-bottom:6px"><?= e(mb_strimwidth($t['subject'], 0, 40, '…')) ?></div>
                <div style="height:6px;background:#eef0f6;border-radius:3px;margin-bottom:4px;width:90%"></div>
                <div style="height:6px;background:#eef0f6;border-radius:3px;margin-bottom:4px;width:70%"></div>
                <div style="height:6px;background:#eef0f6;border-radius:3px;margin-bottom:8px;width:80%"></div>
                <div style="height:18px;width:60px;background:#6d5efc;border-radius:4px"></div>
              </div>
            <?php endif; ?>
          </a>
          <div class="tmpl-actions">
            <a href="<?= url('templates/preview?id=' . $t['id']) ?>" target="_blank" title="Preview"><i class="bi bi-eye"></i></a>
            <a href="<?= url('templates/edit?id=' . $t['id']) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= url('templates/delete') ?>" data-confirm="Delete this template?" class="d-inline">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
              <button title="Delete" class="text-danger"><i class="bi bi-trash"></i></button>
            </form>
          </div>
          <div class="p-3">
            <div class="fw-bold text-truncate"><?= e($t['name']) ?></div>
            <div class="text-muted small">Added on <?= date('M j, Y', strtotime($t['created_at'])) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
document.getElementById('tplSearch')?.addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.tpl-item').forEach(el => {
    el.style.display = el.dataset.name.includes(q) ? '' : 'none';
  });
});
document.querySelectorAll('.seg-tabs button').forEach(b => b.addEventListener('click', function () {
  document.querySelectorAll('.seg-tabs button').forEach(x => x.classList.remove('active'));
  this.classList.add('active');
}));
</script>
