<?php /** @var bool $googleEnabled @var array $errors @var array $old */
$errors = $errors ?? [];
$old    = $old ?? [];
$authTitle = 'Create account'; $authVariant = 'signup'; require BASE_PATH . '/views/auth/_head.php';
?>
<h1>Sign up for free</h1>
<p class="text-muted mb-3">Start sending in minutes — no card required.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<a href="<?= url('auth/google') ?>" class="btn-oauth">
  <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.581C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
  Continue with Google
</a>
<div class="auth-divider">Or sign up with email</div>

<form method="post" action="<?= url('register') ?>" novalidate>
  <?= csrf_field() ?>
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">First name</label>
      <input type="text" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" required autofocus placeholder="Jane" value="<?= e($old['first_name'] ?? '') ?>">
      <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback"><?= e($errors['first_name']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Last name</label>
      <input type="text" name="last_name" class="form-control" placeholder="Doe" value="<?= e($old['last_name'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" required placeholder="you@company.com" value="<?= e($old['email'] ?? $_GET['email'] ?? '') ?>">
      <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone <span class="text-muted small">(optional)</span></label>
      <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= e($old['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Company <span class="text-muted small">(optional)</span></label>
      <input type="text" name="company" class="form-control" placeholder="Acme Inc" value="<?= e($old['company'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" name="password" id="regPw" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8" placeholder="At least 8 characters">
        <button class="btn btn-light border" type="button" data-toggle-pw="#regPw" tabindex="-1"><i class="bi bi-eye"></i></button>
        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="form-check mt-3">
    <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter" value="1" <?= !empty($old['newsletter']) ? 'checked' : '' ?>>
    <label class="form-check-label small text-muted" for="newsletter">I want to subscribe to newsletters.</label>
  </div>
  <div class="form-check mt-1 mb-3">
    <input class="form-check-input <?= isset($errors['agree']) ? 'is-invalid' : '' ?>" type="checkbox" name="agree" id="agree" value="1" required <?= !empty($old['agree']) ? 'checked' : '' ?>>
    <label class="form-check-label small text-muted" for="agree">
      I agree to the <a href="<?= url('legal/terms') ?>" target="_blank">Terms</a>,
      <a href="<?= url('legal/privacy') ?>" target="_blank">Privacy Policy</a> and
      <a href="<?= url('legal/acceptable-use') ?>" target="_blank">Acceptable Use Policy</a>.
    </label>
    <?php if (isset($errors['agree'])): ?><div class="invalid-feedback d-block"><?= e($errors['agree']) ?></div><?php endif; ?>
  </div>
  <button class="btn btn-primary w-100">Sign up</button>
</form>

<p class="text-center text-muted mt-3 mb-0">
  Already have an account? <a href="<?= url('login') ?>" class="fw-semibold">Log in</a>
</p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
