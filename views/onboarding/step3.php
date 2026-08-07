<?php
/** @var array $sources @var array $answers */
$obStep = 3;
require BASE_PATH . '/views/onboarding/_head.php';
$sourceMeta = [
    'google'   => ['icon' => 'google',          'label' => 'Google',   'grad' => 'grad-google'],
    'facebook' => ['icon' => 'facebook',        'label' => 'Facebook', 'grad' => 'grad-facebook'],
    'twitter'  => ['icon' => 'twitter-x',       'label' => 'Twitter',  'grad' => 'grad-x'],
    'linkedin' => ['icon' => 'linkedin',        'label' => 'LinkedIn', 'grad' => 'grad-linkedin'],
    'other'    => ['icon' => 'question-circle', 'label' => 'Other',    'grad' => 'grad-gray'],
];
?>
<h1 class="ob-title">What Other Tools Do You Currently Use?</h1>
<p class="ob-sub">Just a couple more questions and you're in.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('onboarding/step3') ?>">
  <?= csrf_field() ?>
  <label class="form-label">Do you have any prior experience with an email marketing service?*</label>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="ob-pill w-100 <?= ($answers['prior_tool'] ?? '') === 'yes' ? 'checked' : '' ?>">
        <input type="radio" name="prior_tool" value="yes" <?= ($answers['prior_tool'] ?? '') === 'yes' ? 'checked' : '' ?> required>
        Yes! I'm moving from another tool.
        <span class="ob-pill-check"><i class="bi bi-check-lg"></i></span>
      </label>
    </div>
    <div class="col-md-6">
      <label class="ob-pill w-100 <?= ($answers['prior_tool'] ?? '') === 'no' ? 'checked' : '' ?>">
        <input type="radio" name="prior_tool" value="no" <?= ($answers['prior_tool'] ?? '') === 'no' ? 'checked' : '' ?>>
        No. I'm just starting with <?= e(Site::name()) ?>.
        <span class="ob-pill-check"><i class="bi bi-check-lg"></i></span>
      </label>
    </div>
  </div>

  <label class="form-label">How did you find out about <?= e(Site::name()) ?>?*</label>
  <div class="ob-choice-grid cols-5 mb-4">
    <?php foreach ($sourceMeta as $val => $m): $checked = ($answers['referral_source'] ?? '') === $val; ?>
      <label class="ob-choice <?= $checked ? 'checked' : '' ?>">
        <input type="radio" name="referral_source" value="<?= e($val) ?>" <?= $checked ? 'checked' : '' ?> required>
        <span class="ob-choice-check"><i class="bi bi-check-lg"></i></span>
        <span class="ic <?= e($m['grad']) ?>"><i class="bi bi-<?= e($m['icon']) ?>"></i></span>
        <?= e($m['label']) ?>
      </label>
    <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?= url('onboarding/step2') ?>" class="btn btn-light btn-lg"><i class="bi bi-arrow-left"></i> Back</a>
    <button class="btn btn-primary btn-lg">Continue <i class="bi bi-arrow-right"></i></button>
  </div>
</form>
<?php require BASE_PATH . '/views/onboarding/_foot.php'; ?>
