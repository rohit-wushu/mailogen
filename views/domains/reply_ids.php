<?php /** @var array $replyIds @var string $tab */ ?>
<?php require BASE_PATH . '/views/domains/_tabs.php'; ?>

<?php if (!$replyIds): ?>
  <div class="auth-empty">
    <div class="auth-empty-ico"><i class="bi bi-reply-fill"></i></div>
    <div class="auth-empty-title">Add a Reply ID</div>
    <p class="auth-empty-desc">
      Reply IDs are the address replies land in — separate from the From address a campaign sends as.
      Save one or more here, then pick one per campaign.
    </p>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#replyModal"><i class="bi bi-plus-lg"></i> Add Reply ID</button>
  </div>
<?php else: ?>
  <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal"><i class="bi bi-plus-lg"></i> Add Reply ID</button>
  </div>
  <?php foreach ($replyIds as $r): ?>
    <div class="reply-row">
      <div class="reply-ico"><i class="bi bi-reply-fill"></i></div>
      <div class="reply-main">
        <div class="sender-name">
          <?= e($r['label'] ?: $r['email']) ?>
          <?php if ((int) $r['is_default'] === 1): ?><span class="badge bg-primary-subtle text-primary-emphasis badge-soft ms-1">Default</span><?php endif; ?>
        </div>
        <?php if ($r['label']): ?><div class="reply-email"><?= e($r['email']) ?></div><?php endif; ?>
      </div>
      <form method="post" action="<?= url('domains/reply-ids/delete') ?>" data-confirm="Remove this Reply ID?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Add Reply ID modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('domains/reply-ids/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Reply ID</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Reply-to email</label>
            <input type="email" class="form-control" name="email" placeholder="support@yourcompany.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted small">(optional)</span></label>
            <input class="form-control" name="label" placeholder="Support team">
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_default" id="replyDefault">
            <label class="form-check-label" for="replyDefault">Set as default Reply ID</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">Add Reply ID</button>
        </div>
      </form>
    </div>
  </div>
</div>
