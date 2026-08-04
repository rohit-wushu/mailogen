<?php $authTitle = 'Set new password'; require BASE_PATH . '/views/auth/_head.php'; ?>
<h1>Choose a new password</h1>
<p class="text-muted mb-4">Pick a strong password you don't use elsewhere.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('reset') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <div class="mb-3">
    <label class="form-label">New password</label>
    <div class="input-group input-group-lg">
      <input type="password" name="password" id="resetPw" class="form-control" required minlength="8" autofocus placeholder="At least 8 characters">
      <button class="btn btn-light border" type="button" data-toggle-pw="#resetPw" tabindex="-1"><i class="bi bi-eye"></i></button>
    </div>
  </div>
  <button class="btn btn-primary btn-lg w-100">Update password</button>
</form>

<p class="text-center text-muted mt-4 mb-0"><a href="<?= url('login') ?>" class="fw-semibold"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
