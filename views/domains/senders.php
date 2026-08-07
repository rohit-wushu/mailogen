<?php /** @var array $senders @var array $verifiedDomains @var string $tab */ ?>
<?php require BASE_PATH . '/views/domains/_tabs.php'; ?>

<?php if (!$verifiedDomains): ?>
  <div class="auth-empty">
    <div class="auth-empty-ico"><i class="bi bi-person-badge"></i></div>
    <div class="auth-empty-title">Add a sender</div>
    <p class="auth-empty-desc">
      Senders are the "From" name and email your campaigns send as. You need at least one verified Sending Domain
      before you can add a sender.
    </p>
    <a href="<?= url('domains') ?>" class="btn btn-dark"><i class="bi bi-shield-check"></i> Verify a domain</a>
  </div>
<?php elseif (!$senders): ?>
  <div class="auth-empty">
    <div class="auth-empty-ico"><i class="bi bi-person-badge"></i></div>
    <div class="auth-empty-title">Add your first sender</div>
    <p class="auth-empty-desc">
      Save a "From" name + email once, then pick it from a list when building a campaign instead of typing it every time.
    </p>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#senderModal"><i class="bi bi-plus-lg"></i> Add sender</button>
  </div>
<?php else: ?>
  <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#senderModal"><i class="bi bi-plus-lg"></i> Add sender</button>
  </div>
  <?php foreach ($senders as $s): ?>
    <div class="sender-row">
      <div class="sender-ico"><i class="bi bi-person-badge"></i></div>
      <div class="sender-main">
        <div class="sender-name">
          <?= e($s['name']) ?>
          <?php if ((int) $s['is_default'] === 1): ?><span class="badge bg-primary-subtle text-primary-emphasis badge-soft ms-1">Default</span><?php endif; ?>
          <?php if ((int) $s['domain_verified'] !== 1): ?><span class="badge bg-warning-subtle text-warning-emphasis badge-soft ms-1">Domain not verified</span><?php endif; ?>
        </div>
        <div class="sender-email"><?= e($s['email']) ?> &middot; <?= e($s['domain_name']) ?></div>
      </div>
      <form method="post" action="<?= url('domains/senders/delete') ?>" data-confirm="Remove this sender?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Add sender modal -->
<div class="modal fade" id="senderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('domains/senders/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add sender</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Domain</label>
            <select class="form-select" name="domain_id" id="senderDomainSel" required>
              <?php foreach ($verifiedDomains as $d): ?><option value="<?= (int) $d['id'] ?>" data-domain="<?= e($d['domain']) ?>"><?= e($d['domain']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Sender name</label>
            <input class="form-control" name="name" placeholder="Your Company" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Sender email</label>
            <input type="email" class="form-control" name="email" id="senderEmailInput" placeholder="hello@yourcompany.com" required>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_default" id="senderDefault">
            <label class="form-check-label" for="senderDefault">Set as default sender</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">Add sender</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const domainSel = document.getElementById('senderDomainSel');
  const emailInput = document.getElementById('senderEmailInput');
  if (!domainSel || !emailInput) return;
  domainSel.addEventListener('change', function () {
    const domain = this.selectedOptions[0]?.dataset.domain || '';
    if (!domain) return;
    const local = (emailInput.value.split('@')[0] || 'hello').trim() || 'hello';
    emailInput.value = local + '@' + domain;
  });
})();
</script>
