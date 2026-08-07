<?php /** @var array $invite @var ?array $owner */
$authTitle = 'Join the team';
require BASE_PATH . '/views/auth/_head.php';
$orgName = ($owner['company'] ?? '') ?: ($owner['name'] ?? APP_NAME);
$roleLabel = $invite['team_role'] === 'admin' ? 'Admin' : 'Member';
?>
<h1>Join <?= e($orgName) ?></h1>
<p class="text-muted mb-3">You've been invited as <strong><?= e($roleLabel) ?></strong> on <?= e(APP_NAME) ?>. Set a name and password to get started.</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('team/accept') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($invite['token']) ?>">
  <div class="row g-2">
    <div class="col-12">
      <label class="form-label">Your name</label>
      <input type="text" name="name" class="form-control" required autofocus placeholder="Jane Doe">
    </div>
    <div class="col-12">
      <label class="form-label">Email</label>
      <input type="email" class="form-control" value="<?= e($invite['email']) ?>" disabled>
    </div>
    <div class="col-12">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" name="password" id="invPw" class="form-control" required minlength="8" placeholder="At least 8 characters">
        <button class="btn btn-light border" type="button" data-toggle-pw="#invPw" tabindex="-1"><i class="bi bi-eye"></i></button>
      </div>
    </div>
  </div>
  <button class="btn btn-primary w-100 mt-3">Join the team</button>
</form>

<p class="text-center text-muted mt-3 mb-0">
  Already have an account? <a href="<?= url('login') ?>" class="fw-semibold">Log in</a>
</p>
<?php require BASE_PATH . '/views/auth/_foot.php'; ?>
