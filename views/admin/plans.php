<?php /** @var array $plans */ ?>
<div class="page-head">
  <div>
    <h2 class="mb-1">Subscription Plans</h2>
    <p class="text-muted mb-0">Set the pricing your users see. Changes go live instantly on the Billing page.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('admin/users') ?>" class="btn btn-light"><i class="bi bi-people"></i> Manage Users</a>
    <button class="btn btn-primary" onclick="newPlan()"><i class="bi bi-plus-lg"></i> Add plan</button>
  </div>
</div>

<div class="row g-4">
  <?php foreach ($plans as $p):
    $cardBadge = !empty($p['is_featured']) ? 'Most popular' : null;
    $cardHi    = !empty($p['is_featured']);
    ob_start(); ?>
      <div class="pc-admin-tools">
        <button class="btn btn-light btn-sm flex-grow-1" onclick='editPlan(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i> Edit</button>
        <form method="post" action="<?= url('admin/plans/delete') ?>" data-confirm="Delete the <?= e($p['name']) ?> plan? Users on it move to free limits.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
        </form>
      </div>
      <?php if (!(int) $p['is_active']): ?><div class="text-center small text-warning-emphasis mt-2"><i class="bi bi-eye-slash"></i> Hidden from users</div><?php endif; ?>
    <?php $cardFooter = ob_get_clean(); ?>
    <div class="col-lg-4 col-md-6">
      <?php require BASE_PATH . '/views/partials/pricing_card.php'; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$plans): ?>
    <div class="col-12"><div class="empty-state"><i class="bi bi-gem"></i><p class="mt-2">No plans yet. Click “Add plan” to create your first pricing tier.</p></div></div>
  <?php endif; ?>
</div>

<p class="text-muted small mt-3"><i class="bi bi-info-circle"></i> Tip: set <strong>−1</strong> on any limit for “Unlimited”. Assign plans to individual users from <a href="<?= url('admin/users') ?>">Manage Users</a>.</p>

<!-- Plan editor modal -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="post" action="<?= url('admin/plans/store') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" id="pl_id">
      <div class="modal-header"><h5 class="modal-title" id="planModalTitle">Add plan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">

        <h6 class="text-muted text-uppercase small fw-bold mb-2">Presentation</h6>
        <div class="row g-3 mb-3">
          <div class="col-md-6"><label class="form-label">Plan name *</label><input class="form-control" name="name" id="pl_name" placeholder="e.g. Pro" required></div>
          <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" name="tagline" id="pl_tagline" placeholder="e.g. For growing senders"></div>
          <div class="col-md-4"><label class="form-label">Price</label>
            <div class="input-group"><span class="input-group-text"><?= e(BILLING_CURRENCY === 'INR' ? '₹' : '$') ?></span>
              <input type="number" step="0.01" min="0" class="form-control" name="price_monthly" id="pl_price" value="0"></div>
          </div>
          <div class="col-md-4"><label class="form-label">Per</label>
            <select class="form-select" name="price_period" id="pl_period">
              <option value="month">/ month</option>
              <option value="year">/ year</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Billed note</label><input class="form-control" name="billed_note" id="pl_billed" placeholder="e.g. $36 billed annually"></div>
          <div class="col-md-6"><label class="form-label">Button label</label><input class="form-control" name="cta_label" id="pl_cta" placeholder="Subscribe"></div>
          <div class="col-md-3"><label class="form-label">Sort order</label><input type="number" class="form-control" name="sort_order" id="pl_sort" value="0"></div>
          <div class="col-md-3 d-flex align-items-end gap-3">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" id="pl_featured"><label class="form-check-label" for="pl_featured">Highlight</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="pl_active" checked><label class="form-check-label" for="pl_active">Active</label></div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Features <span class="text-muted small">— one per line. Prefix a line with <code>-</code> to show it struck-through (not included).</span></label>
          <textarea class="form-control font-monospace" name="features" id="pl_features" rows="8" placeholder="All basic features included&#10;Open, click &amp; response tracking&#10;-Scheduling&#10;Up to 5,000 emails / month&#10;Priority support"></textarea>
        </div>

        <h6 class="text-muted text-uppercase small fw-bold mb-2">Limits enforced on the account <span class="text-lowercase fw-normal">(−1 = unlimited)</span></h6>
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Max contacts</label><input type="number" class="form-control" name="max_contacts" id="pl_contacts" value="1000"></div>
          <div class="col-md-3"><label class="form-label">Max campaigns</label><input type="number" class="form-control" name="max_campaigns" id="pl_campaigns" value="10"></div>
          <div class="col-md-3"><label class="form-label">Max SMTP accounts</label><input type="number" class="form-control" name="max_smtp" id="pl_smtp" value="1"></div>
          <div class="col-md-3"><label class="form-label">Emails / month</label><input type="number" class="form-control" name="monthly_emails" id="pl_emails" value="5000"></div>
        </div>

      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save plan</button></div>
    </form>
  </div>
</div>

<script>
function newPlan() {
  document.getElementById('planModalTitle').textContent = 'Add plan';
  const f = document.querySelector('#planModal form'); f.reset();
  pl_id.value=''; pl_period.value='month'; pl_active.checked=true; pl_featured.checked=false;
  pl_contacts.value=1000; pl_campaigns.value=10; pl_smtp.value=1; pl_emails.value=5000; pl_price.value=0; pl_sort.value=0;
  new bootstrap.Modal('#planModal').show();
}
function editPlan(p) {
  document.getElementById('planModalTitle').textContent = 'Edit ' + p.name;
  pl_id.value=p.id; pl_name.value=p.name||''; pl_tagline.value=p.tagline||'';
  pl_price.value=p.price_monthly||0; pl_period.value=p.price_period||'month'; pl_billed.value=p.billed_note||'';
  pl_cta.value=p.cta_label||'Subscribe'; pl_sort.value=p.sort_order||0; pl_features.value=p.features||'';
  pl_featured.checked=Number(p.is_featured)===1; pl_active.checked=Number(p.is_active)===1;
  pl_contacts.value=p.max_contacts; pl_campaigns.value=p.max_campaigns; pl_smtp.value=p.max_smtp; pl_emails.value=p.monthly_emails;
  new bootstrap.Modal('#planModal').show();
}
</script>
