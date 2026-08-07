<?php
/** @var float $priceSmtp @var float $priceDomain @var array $modeSmtpFeatures @var array $modeDomainFeatures @var string $freePlanName @var array $freeFeatures @var string $currentMode */
$obStep = 4;
require BASE_PATH . '/views/onboarding/_head.php';
$__cur = BILLING_CURRENCY === 'INR' ? '₹' : '$';
?>
<h1 class="ob-title">How Will You Send Campaigns?</h1>
<p class="ob-sub">You can switch this anytime later from Settings.
  <button type="button" class="mode-info-btn" data-bs-toggle="modal" data-bs-target="#modeCompareModal" title="Compare all options"><i class="bi bi-info-circle"></i> Compare options</button>
</p>

<?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>

<form method="post" action="<?= url('onboarding/step4') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="sending_mode" id="sendingMode" value="<?= e($currentMode) ?>">
  <div class="mode-picker mb-4">
    <button type="button" class="mode-card <?= $currentMode === 'smtp' ? 'active' : '' ?>" data-mode="smtp">
      <span class="mode-card-check"><i class="bi bi-check-lg"></i></span>
      <span class="mode-card-ico grad-blue"><i class="bi bi-hdd-network-fill"></i></span>
      <span class="mode-card-title">Bring your own SMTP</span>
      <span class="mode-card-sub">Use your own SMTP or ESP account</span>
      <?php if ($priceSmtp > 0): ?><span class="mode-card-price">From <?= e($__cur) ?><?= number_format($priceSmtp, 0) ?><small>/mo</small></span><?php endif; ?>
    </button>
    <button type="button" class="mode-card <?= $currentMode === 'domain' ? 'active' : '' ?>" data-mode="domain">
      <span class="mode-card-check"><i class="bi bi-check-lg"></i></span>
      <span class="mode-card-ico grad-violet"><i class="bi bi-cloud-arrow-up-fill"></i></span>
      <span class="mode-card-title">Use our sending infrastructure</span>
      <span class="mode-card-sub">We handle delivery for you</span>
      <?php if ($priceDomain > 0): ?><span class="mode-card-price">From <?= e($__cur) ?><?= number_format($priceDomain, 0) ?><small>/mo</small></span><?php endif; ?>
    </button>
  </div>
  <div class="form-text mb-4">Free plan works with either option.</div>

  <div class="d-flex justify-content-between">
    <a href="<?= url('onboarding/step3') ?>" class="btn btn-light btn-lg"><i class="bi bi-arrow-left"></i> Back</a>
    <button class="btn btn-primary btn-lg">Finish <i class="bi bi-check-lg"></i></button>
  </div>
</form>

<div class="modal fade" id="modeCompareModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Compare sending modes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-green"><i class="bi bi-gift-fill"></i></span>
                <div>
                  <div class="mc-col-title"><?= e($freePlanName) ?></div>
                  <div class="mc-col-price">Free</div>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($freeFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
                <?php if (!$freeFeatures): ?><li class="text-muted"><span>No features listed.</span></li><?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-blue"><i class="bi bi-hdd-network-fill"></i></span>
                <div>
                  <div class="mc-col-title">Bring your own SMTP</div>
                  <?php if ($priceSmtp > 0): ?><div class="mc-col-price">From <?= e($__cur) ?><?= number_format($priceSmtp, 0) ?>/mo</div><?php endif; ?>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($modeSmtpFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mc-col">
              <div class="mc-col-head">
                <span class="mode-card-ico grad-violet"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <div>
                  <div class="mc-col-title">Hosted sending</div>
                  <?php if ($priceDomain > 0): ?><div class="mc-col-price">From <?= e($__cur) ?><?= number_format($priceDomain, 0) ?>/mo</div><?php endif; ?>
                </div>
              </div>
              <ul class="mc-feat-list">
                <?php foreach ($modeDomainFeatures as $f): ?>
                  <li class="<?= $f['included'] ? '' : 'excluded' ?>"><i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i><span><?= e($f['label']) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var input = document.getElementById('sendingMode');
  document.querySelectorAll('.mode-card[data-mode]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.mode-card[data-mode]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      input.value = btn.dataset.mode;
    });
  });
})();
</script>
<?php require BASE_PATH . '/views/onboarding/_foot.php'; ?>
