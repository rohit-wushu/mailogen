<?php /** @var array $domains @var array $records @var string $tab */ ?>
<?php require BASE_PATH . '/views/domains/_tabs.php'; ?>

<?php if (!$domains): ?>
  <div class="auth-empty">
    <div class="auth-empty-ico"><i class="bi bi-shield-check"></i></div>
    <div class="auth-empty-title">Authenticate your domain</div>
    <p class="auth-empty-desc">
      Confirm your domain so inbox providers know your emails are from you. This is the first step before you can send.
      Once verified, your campaigns are more likely to reach the inbox instead of the spam folder.
    </p>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#domainModal"><i class="bi bi-plus-lg"></i> Add domain</button>
    <div>
      <span class="auth-empty-banner"><i class="bi bi-exclamation-circle"></i> You need at least one verified domain before you can add a sender or send a campaign.</span>
    </div>
  </div>
<?php else: ?>
  <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#domainModal"><i class="bi bi-plus-lg"></i> Add Domain</button>
  </div>
  <?php foreach ($domains as $d):
    $r = $records[(int) $d['id']];
    $verified = (int) $d['is_verified'] === 1;
  ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1"><?= e($d['domain']) ?>
              <?php if ($verified): ?><span class="badge bg-success-subtle text-success-emphasis badge-soft ms-1">Verified</span>
              <?php else: ?><span class="badge bg-warning-subtle text-warning-emphasis badge-soft ms-1">Not verified</span><?php endif; ?>
            </h5>
            <div class="text-muted small">
              Last checked: <?= fmt_dt($d['last_checked_at']) ?>
              &middot; SPF <?= (int) $d['spf_verified'] === 1 ? '✅' : '❌' ?>
              &middot; DKIM <?= (int) $d['dkim_verified'] === 1 ? '✅' : '❌' ?>
              &middot; DMARC <?= (int) $d['dmarc_verified'] === 1 ? '✅' : '⚪ optional' ?>
            </div>
          </div>
          <div class="d-flex gap-2">
            <form method="post" action="<?= url('domains/verify') ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <button class="btn btn-light btn-sm"><i class="bi bi-arrow-repeat"></i> Verify</button>
            </form>
            <form method="post" action="<?= url('domains/delete') ?>" data-confirm="Remove this domain? Linked SMTP accounts and senders will stop working.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>

        <div class="alert alert-info py-2 px-3 small mb-3">
          <i class="bi bi-info-circle"></i>
          Enter the <strong>Name / Host</strong> exactly as shown below for each record. Most DNS providers (Hostinger, GoDaddy, Namecheap…)
          want the <strong>short form</strong> shown in bold — not the full <code><?= e($d['domain']) ?></code> address — since they append your domain automatically.
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th style="width:70px">Type</th><th style="width:230px">Name / Host</th><th>Value</th><th style="width:40px"></th></tr></thead>
            <tbody>
              <?php foreach ($r as $key => $rec): $rid = 'dns_' . $d['id'] . '_' . $key; $hid = 'host_' . $d['id'] . '_' . $key; ?>
                <tr>
                  <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e($rec['type']) ?></span></td>
                  <td class="text-break small">
                    <div class="d-flex align-items-center gap-1">
                      <code id="<?= $hid ?>" class="fw-bold"><?= e($rec['hostShort']) ?></code>
                      <button type="button" class="row-actions" title="Copy Name" onclick="copyText('<?= $hid ?>')"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">or full host: <?= e($rec['host']) ?></div>
                    <?php if ($key === 'spf'): ?>
                      <div class="dns-name-note dns-name-note--ok"><i class="bi bi-check-circle"></i> <?= e($rec['nameNote']) ?></div>
                    <?php else: ?>
                      <div class="dns-name-note dns-name-note--warn"><i class="bi bi-exclamation-triangle"></i> <?= e($rec['nameNote']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-break small"><code id="<?= $rid ?>"><?= e($rec['sample']) ?></code>
                    <div class="text-muted" style="font-size:.75rem"><?= e($rec['hint']) ?></div>
                  </td>
                  <td><button type="button" class="row-actions" title="Copy value" onclick="copyText('<?= $rid ?>')"><i class="bi bi-clipboard"></i></button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Add domain modal -->
<div class="modal fade" id="domainModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('domains/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add sending domain</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Domain</label>
          <input type="text" name="domain" class="form-control" placeholder="yourcompany.com" required>
          <div class="form-text">The domain your From addresses use, e.g. <code>mail.yourcompany.com</code> or <code>yourcompany.com</code>.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">Add & generate DKIM key</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function copyText(id){
  const el = document.getElementById(id);
  navigator.clipboard.writeText(el.textContent).then(() => {
    const original = el.textContent;
    el.textContent = 'Copied!';
    setTimeout(() => { el.textContent = original; }, 900);
  });
}
</script>
