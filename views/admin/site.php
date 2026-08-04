<?php /** @var ?string $siteName @var ?string $logo @var ?string $favicon @var ?string $metaTitle @var ?string $metaDesc @var ?string $metaKeys */
$logoUrl = $logo ? (preg_match('#^https?://#', $logo) ? $logo : url($logo)) : '';
$favUrl  = $favicon ? (preg_match('#^https?://#', $favicon) ? $favicon : url($favicon)) : '';
?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Site Settings</h2>
    <p class="text-muted mb-0">Your site name, logo, favicon and SEO meta tags.</p>
  </div>
  <a href="<?= url('admin/branding') ?>" class="btn btn-light"><i class="bi bi-palette2"></i> Email Branding</a>
</div>

<form method="post" action="<?= url('admin/site/store') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <!-- Identity -->
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-card-text me-1"></i> Identity &amp; SEO</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Site name</label>
            <input class="form-control" name="site_name" value="<?= e($siteName ?? '') ?>" maxlength="100" placeholder="<?= e(APP_NAME) ?>">
            <div class="form-text">Shown in the sidebar, login screen and footer.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Meta title</label>
            <input class="form-control" name="meta_title" value="<?= e($metaTitle ?? '') ?>" maxlength="160" placeholder="Defaults to the site name">
            <div class="form-text">Browser tab title / search-result title (appended after each page name).</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Meta description</label>
            <textarea class="form-control" name="meta_description" rows="2" maxlength="300" placeholder="A short sentence describing your platform for search engines."><?= e($metaDesc ?? '') ?></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label">Meta keywords <span class="text-muted small">(optional)</span></label>
            <input class="form-control" name="meta_keywords" value="<?= e($metaKeys ?? '') ?>" placeholder="email marketing, bulk email, smtp">
          </div>
        </div>
      </div>
    </div>

    <!-- Logo + favicon -->
    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-image me-1"></i> Logo</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Show in header</label>
            <select class="form-select" name="brand_display">
              <?php foreach (['both' => 'Logo + title', 'logo' => 'Logo only', 'title' => 'Title only'] as $v => $lbl): ?>
                <option value="<?= $v ?>" <?= ($brandMode ?? 'both') === $v ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Controls the brand shown in the sidebar, login and public pages.</div>
          </div>
          <?php if ($logoUrl): ?>
            <div class="d-flex align-items-center gap-3 mb-2">
              <span class="p-2 rounded" style="background:#1f2540"><img src="<?= e($logoUrl) ?>" alt="logo" style="height:34px;width:auto"></span>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_logo" id="rmLogo" value="1">
                <label class="form-check-label small text-danger" for="rmLogo">Remove</label>
              </div>
            </div>
          <?php endif; ?>
          <input type="file" class="form-control mb-2" name="logo" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
            <input type="url" class="form-control" name="logo_url" value="<?= e($logo && preg_match('#^https?://#', (string) $logo) ? $logo : '') ?>" placeholder="…or paste an image URL">
          </div>
          <div class="form-text">PNG/SVG recommended · max 2 MB · shown at ~38px tall. Leave empty to use the default icon.</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><i class="bi bi-star me-1"></i> Favicon</div>
        <div class="card-body">
          <?php if ($favUrl): ?>
            <div class="d-flex align-items-center gap-3 mb-2">
              <span class="p-2 rounded" style="background:#1f2540"><img src="<?= e($favUrl) ?>" alt="favicon" style="height:24px;width:24px;object-fit:contain"></span>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_favicon" id="rmFav" value="1">
                <label class="form-check-label small text-danger" for="rmFav">Remove</label>
              </div>
            </div>
          <?php endif; ?>
          <input type="file" class="form-control mb-2" name="favicon" accept=".ico,.png,.svg">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
            <input type="url" class="form-control" name="favicon_url" value="<?= e($favicon && preg_match('#^https?://#', (string) $favicon) ? $favicon : '') ?>" placeholder="…or paste an icon URL">
          </div>
          <div class="form-text">ICO/PNG/SVG · max 512 KB · square (e.g. 32×32).</div>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Save site settings</button></div>
</form>
