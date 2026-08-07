<?php
/** @var array $ranges @var array $answers @var array $errors */
$errors = $errors ?? [];
$obStep = 2;
require BASE_PATH . '/views/onboarding/_head.php';
$rangeLabels = [
    '1-1000' => '1 - 1000', '1000-5000' => '1,000 - 5,000', '5000-10000' => '5,000 - 10,000',
    '10000-50000' => '10,000 - 50,000', '50000-100000' => '50,000 - 100,000',
    '100000-500000' => '100,000 - 500,000', '500000-1000000' => '500,000 - 1 Million', '1000000+' => '1 Million +',
];
?>
<h1 class="ob-title">Please Tell Us About Your Business</h1>
<p class="ob-sub">So we can craft a better <?= e(Site::name()) ?> experience for you.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('onboarding/step2') ?>" novalidate>
  <?= csrf_field() ?>
  <label class="form-label">How many subscribers do you have?*</label>
  <div class="ob-choice-grid mb-1">
    <?php foreach ($rangeLabels as $val => $label): $checked = ($answers['subscriber_range'] ?? '') === $val; ?>
      <label class="ob-pill <?= $checked ? 'checked' : '' ?>">
        <input type="radio" name="subscriber_range" value="<?= e($val) ?>" <?= $checked ? 'checked' : '' ?> required>
        <?= e($label) ?>
        <span class="ob-pill-check"><i class="bi bi-check-lg"></i></span>
      </label>
    <?php endforeach; ?>
  </div>
  <?php if (isset($errors['subscriber_range'])): ?><div class="text-danger small mb-3"><?= e($errors['subscriber_range']) ?></div><?php else: ?><div class="mb-4"></div><?php endif; ?>
  <div class="mb-3">
    <label class="form-label">How do you collect your subscribers?*</label>
    <input type="text" name="collection_method" class="form-control form-control-lg <?= isset($errors['collection_method']) ? 'is-invalid' : '' ?>" required value="<?= e($answers['collection_method'] ?? '') ?>" placeholder="e.g. website signup form, imported list, events">
    <?php if (isset($errors['collection_method'])): ?><div class="invalid-feedback"><?= e($errors['collection_method']) ?></div><?php endif; ?>
  </div>
  <div class="mb-4">
    <label class="form-label">What type of content do you wish to send?*</label>
    <input type="text" name="content_type" class="form-control form-control-lg <?= isset($errors['content_type']) ? 'is-invalid' : '' ?>" required value="<?= e($answers['content_type'] ?? '') ?>" placeholder="e.g. newsletters, product updates, promotions">
    <?php if (isset($errors['content_type'])): ?><div class="invalid-feedback"><?= e($errors['content_type']) ?></div><?php endif; ?>
  </div>
  <div class="d-flex justify-content-between">
    <a href="<?= url('onboarding/step1') ?>" class="btn btn-light btn-lg"><i class="bi bi-arrow-left"></i> Back</a>
    <button class="btn btn-primary btn-lg">Continue <i class="bi bi-arrow-right"></i></button>
  </div>
</form>
<?php require BASE_PATH . '/views/onboarding/_foot.php'; ?>
