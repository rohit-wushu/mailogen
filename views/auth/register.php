<?php $authTitle = 'Create account'; require BASE_PATH . '/views/auth/_head.php'; ?>
<h1>Create your account</h1>
<p class="text-muted mb-4">Start sending in minutes — no card required.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('register') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full name</label>
      <input type="text" name="name" class="form-control form-control-lg" required autofocus placeholder="Jane Doe">
    </div>
    <div class="col-md-6">
      <label class="form-label">Company <span class="text-muted small">(optional)</span></label>
      <input type="text" name="company" class="form-control form-control-lg" placeholder="Acme Inc">
    </div>
    <div class="col-12">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control form-control-lg" required placeholder="you@company.com" value="<?= e($_GET['email'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Password</label>
      <div class="input-group input-group-lg">
        <input type="password" name="password" id="regPw" class="form-control" required minlength="8" placeholder="At least 8 characters">
        <button class="btn btn-light border" type="button" data-toggle-pw="#regPw" tabindex="-1"><i class="bi bi-eye"></i></button>
      </div>
    </div>
  </div>
  <div class="form-check mt-3 mb-4">
    <input class="form-check-input" type="checkbox" name="agree" id="agree" value="1" required>
    <label class="form-check-label small text-muted" for="agree">
      I agree to the <a href="<?= url('legal/terms') ?>" target="_blank">Terms</a>,
      <a href="<?= url('legal/privacy') ?>" target="_blank">Privacy Policy</a> and
      <a href="<?= url('legal/acceptable-use') ?>" target="_blank">Acceptable Use Policy</a>.
    </label>
  </div>
  <button class="btn btn-primary btn-lg w-100">Create account</button>
</form>

<p class="text-center text-muted mt-4 mb-0">
  Already have an account? <a href="<?= url('login') ?>" class="fw-semibold">Sign in</a>
</p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
