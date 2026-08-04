<?php /** @var ?string $text @var ?string $logo @var ?string $link */
$logoUrl = $logo ? (preg_match('#^https?://#', $logo) ? $logo : url($logo)) : '';
?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Branding</h2>
    <p class="text-muted mb-0">This “powered by” footer is added to emails sent by users on the <strong>free plan</strong>. Paid plans get no branding.</p>
  </div>
  <a href="<?= url('admin/plans') ?>" class="btn btn-light"><i class="bi bi-credit-card"></i> Plans</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-palette2 me-1"></i> Footer branding</div>
      <form class="card-body" method="post" action="<?= url('admin/branding/store') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Branding text</label>
          <input class="form-control" name="branding_text" id="bText" value="<?= e($text ?? '') ?>" maxlength="160" placeholder="e.g. Sent with Eventogen Mailer">
          <div class="form-text">Shown next to the logo. Leave blank to show only the logo.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Link <span class="text-muted small">(optional)</span></label>
          <input class="form-control" name="branding_link" value="<?= e($link ?? '') ?>" placeholder="https://your-site.com">
          <div class="form-text">Clicking the branding opens this URL.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Logo image</label>
          <input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
          <div class="form-text">PNG, JPG, GIF, WEBP or SVG · max 1 MB · shown at 22px tall.</div>
          <div class="input-group mt-2">
            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
            <input type="url" class="form-control" name="branding_logo_url"
                   value="<?= e($logo && preg_match('#^https?://#', (string) $logo) ? $logo : '') ?>"
                   placeholder="…or paste a public image URL (https://…)">
          </div>
          <div class="form-text"><i class="bi bi-info-circle"></i> Email clients can only show a logo hosted at a <strong>public URL</strong>. An uploaded file works once the app runs on your real domain; while testing on localhost, paste a public image URL here.</div>
          <?php if ($logoUrl): ?>
            <div class="d-flex align-items-center gap-2 mt-2">
              <img src="<?= e($logoUrl) ?>" alt="logo" style="height:28px;background:#fff;padding:3px;border-radius:6px;border:1px solid var(--bs-border-color)">
              <div class="form-check ms-2">
                <input class="form-check-input" type="checkbox" name="remove_logo" id="rmLogo" value="1">
                <label class="form-check-label small text-danger" for="rmLogo">Remove logo</label>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Save branding</button>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-eye me-1"></i> Email footer preview</div>
      <div class="card-body">
        <div style="background:#fff;border-radius:10px;padding:18px;border:1px solid var(--bs-border-color)">
          <div style="font:14px Arial,sans-serif;color:#333">Hi Jane, …your email content…</div>
          <div style="margin-top:24px;padding-top:14px;border-top:1px solid #eee;font:12px Arial,sans-serif;color:#999;text-align:center;line-height:1.6">
            <strong>Acme Inc</strong> &nbsp;·&nbsp; 123 Street, City<br>
            You received this email because you subscribed to Acme Inc.<br>
            <a href="#" style="color:#888;text-decoration:underline">Unsubscribe</a> from these emails.
          </div>
          <div style="margin-top:10px;font:12px Arial,sans-serif;color:#9aa0ad;text-align:center;line-height:1.6" id="brandPreview">
            <?php if ($logoUrl): ?><img src="<?= e($logoUrl) ?>" alt="" style="height:22px;width:auto;vertical-align:middle"><?php endif; ?>
            <span style="<?= $logoUrl ? 'margin-left:8px;' : '' ?>vertical-align:middle" id="brandPreviewText"><?= e($text ?? '') ?></span>
          </div>
        </div>
        <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> The grey block above the branding is each sender's own compliance footer.</p>
      </div>
    </div>
  </div>
</div>

<script>
// Live-update the branding text in the preview.
document.getElementById('bText')?.addEventListener('input', function () {
  document.getElementById('brandPreviewText').textContent = this.value;
});
</script>
