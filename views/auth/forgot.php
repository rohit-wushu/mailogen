<?php $authTitle = 'Forgot password'; require BASE_PATH . '/views/auth/_head.php'; ?>
<h1>Forgot password?</h1>
<p class="text-muted mb-4">Enter your email and we'll send you a reset link.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('forgot') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control form-control-lg" required autofocus placeholder="you@company.com">
  </div>
  <button class="btn btn-primary btn-lg w-100">Send reset link</button>
</form>

<p class="text-center text-muted mt-4 mb-0"><a href="<?= url('login') ?>" class="fw-semibold"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
