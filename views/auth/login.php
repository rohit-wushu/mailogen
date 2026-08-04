<?php $authTitle = 'Sign in'; require BASE_PATH . '/views/auth/_head.php'; ?>
<h1>Welcome back</h1>
<p class="text-muted mb-4">Sign in to continue to your dashboard.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('success')): ?><div class="alert alert-success py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('login') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control form-control-lg" required autofocus placeholder="you@company.com">
  </div>
  <div class="mb-2">
    <label class="form-label">Password</label>
    <div class="input-group input-group-lg">
      <input type="password" name="password" id="loginPw" class="form-control" required placeholder="••••••••">
      <button class="btn btn-light border" type="button" data-toggle-pw="#loginPw" tabindex="-1"><i class="bi bi-eye"></i></button>
    </div>
  </div>
  <div class="d-flex justify-content-end mb-3">
    <a href="<?= url('forgot') ?>" class="small">Forgot password?</a>
  </div>
  <button class="btn btn-primary btn-lg w-100">Sign in</button>
</form>

<p class="text-center text-muted mt-4 mb-0">
  Don't have an account? <a href="<?= url('register') ?>" class="fw-semibold">Create one free</a>
</p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
