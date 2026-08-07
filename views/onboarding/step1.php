<?php /** @var array $countries @var array $timezones @var array $errors @var array $old */
$errors = $errors ?? [];
$old    = $old ?? [];
$val = static fn (string $postKey, string $userKey) => $old[$postKey] ?? $user[$userKey] ?? '';
$obStep = 1; require BASE_PATH . '/views/onboarding/_head.php';
?>
<h1 class="ob-title">Add Some Details</h1>
<p class="ob-sub">Why? We need a valid physical address to ensure your emails comply with international <a href="<?= url('legal/acceptable-use') ?>" target="_blank">anti-spam laws</a>.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('onboarding/step1') ?>" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12">
      <label class="form-label">Company/Individual name*</label>
      <input type="text" name="company" class="form-control form-control-lg <?= isset($errors['company']) ? 'is-invalid' : '' ?>" required autofocus value="<?= e($val('company', 'company')) ?>">
      <?php if (isset($errors['company'])): ?><div class="invalid-feedback"><?= e($errors['company']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
      <label class="form-label">Address line*</label>
      <input type="text" name="address_line" class="form-control form-control-lg <?= isset($errors['org_address']) ? 'is-invalid' : '' ?>" required value="<?= e($val('address_line', 'org_address')) ?>">
      <?php if (isset($errors['org_address'])): ?><div class="invalid-feedback"><?= e($errors['org_address']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">City*</label>
      <input type="text" name="city" class="form-control form-control-lg <?= isset($errors['city']) ? 'is-invalid' : '' ?>" required value="<?= e($val('city', 'city')) ?>">
      <?php if (isset($errors['city'])): ?><div class="invalid-feedback"><?= e($errors['city']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Zip/ Postal code*</label>
      <input type="text" name="zip" class="form-control form-control-lg <?= isset($errors['zip']) ? 'is-invalid' : '' ?>" required value="<?= e($val('zip', 'zip')) ?>">
      <?php if (isset($errors['zip'])): ?><div class="invalid-feedback"><?= e($errors['zip']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">State/ Province/ Region*</label>
      <input type="text" name="state" class="form-control form-control-lg <?= isset($errors['state']) ? 'is-invalid' : '' ?>" required value="<?= e($val('state', 'state')) ?>">
      <?php if (isset($errors['state'])): ?><div class="invalid-feedback"><?= e($errors['state']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Country*</label>
      <select class="form-select form-select-lg <?= isset($errors['country']) ? 'is-invalid' : '' ?>" name="country" required>
        <option value="">Select…</option>
        <?php $selCountry = $val('country', 'country'); ?>
        <?php foreach ($countries as $c): ?><option value="<?= e($c) ?>" <?= $selCountry === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select>
      <?php if (isset($errors['country'])): ?><div class="invalid-feedback d-block"><?= e($errors['country']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
      <label class="form-label">Time zone*</label>
      <select class="form-select form-select-lg" name="timezone" required>
        <?php $selTz = $old['timezone'] ?? $user['timezone'] ?? 'Asia/Kolkata'; ?>
        <?php foreach ($timezones as $tz): ?><option value="<?= e($tz) ?>" <?= $selTz === $tz ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Website url <span class="text-muted small">(optional)</span></label>
      <input type="text" name="website" class="form-control form-control-lg" placeholder="yourcompany.com" value="<?= e($val('website', 'org_website')) ?>">
    </div>
  </div>
  <button class="btn btn-primary btn-lg w-100 mt-4">Continue <i class="bi bi-arrow-right"></i></button>
</form>
<?php require BASE_PATH . '/views/onboarding/_foot.php'; ?>
