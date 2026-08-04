<?php /** @var array $lists */
$grads = ['grad-violet','grad-blue','grad-green','grad-orange','grad-pink','grad-sky','grad-teal','grad-red'];
?>
<div class="page-head">
  <h2>Contact Lists</h2>
  <div class="d-flex gap-2">
    <a href="<?= url('contacts') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to contacts</a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#listModal" onclick="resetListForm()"><i class="bi bi-plus-lg"></i> Create List</button>
  </div>
</div>

<?php if (!$lists): ?>
  <div class="card"><div class="empty-state"><i class="bi bi-collection"></i>
    <p class="mt-2">No lists yet. Group your contacts into lists to target campaigns.</p>
    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#listModal" onclick="resetListForm()"><i class="bi bi-plus-lg"></i> Create your first list</button>
  </div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($lists as $i => $l): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start gap-3">
              <span class="stat-icon <?= $grads[$i % count($grads)] ?>" style="width:46px;height:46px;font-size:1.15rem"><i class="bi bi-collection-fill"></i></span>
              <div class="flex-grow-1 min-w-0">
                <h6 class="fw-bold mb-0 text-truncate"><?= e($l['name']) ?></h6>
                <div class="text-muted small"><?= e($l['description'] ?? '') ?: 'No description' ?></div>
              </div>
            </div>
            <div class="d-flex align-items-baseline gap-2 mt-3">
              <span class="stat-value" style="font-size:1.6rem"><?= (int) $l['contact_count'] ?></span>
              <span class="text-muted small">contacts</span>
            </div>
          </div>
          <div class="card-footer bg-transparent d-flex gap-2">
            <a href="<?= url('contacts?list_id=' . $l['id']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="bi bi-people"></i> View contacts</a>
            <button class="btn btn-sm btn-light" onclick='editList(<?= json_encode($l) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="post" action="<?= url('contacts/list/delete') ?>" data-confirm="Delete list '<?= e($l['name']) ?>'? Contacts are kept.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
              <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- List modal (create / edit) -->
<div class="modal fade" id="listModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('contacts/list/store') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="list_form_id">
      <div class="modal-header"><h5 class="modal-title" id="listModalTitle">New list</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" id="list_name" required></div>
        <div class="mb-3"><label class="form-label">Description</label><input class="form-control" name="description" id="list_desc"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save list</button></div>
    </form>
  </div>
</div>

<script>
function resetListForm(){ document.getElementById('listModalTitle').textContent='New list'; document.getElementById('list_form_id').value=''; document.getElementById('list_name').value=''; document.getElementById('list_desc').value=''; }
function editList(l){ document.getElementById('listModalTitle').textContent='Edit list'; document.getElementById('list_form_id').value=l.id; document.getElementById('list_name').value=l.name; document.getElementById('list_desc').value=l.description||''; new bootstrap.Modal('#listModal').show(); }
</script>
