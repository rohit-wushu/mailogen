<?php
/**
 * Shared pricing-card visual used by the admin plan editor and the user
 * billing panel. The including view sets, before the include:
 *   $p          array   the plan row
 *   $cardFooter string  HTML for the button / action area
 *   $cardBadge  ?string optional ribbon text (e.g. "Most popular", "Current")
 *   $cardHi     bool    highlight (featured / current) styling
 *
 * Features come from $p['features'] (one per line; "-" prefix = excluded).
 * The CSS is emitted once per request.
 */
$cardFooter = $cardFooter ?? '';
$cardBadge  = $cardBadge ?? (!empty($p['is_featured']) ? 'Most popular' : null);
$cardHi     = $cardHi ?? !empty($p['is_featured']);
$price      = (float) $p['price_monthly'];
$period     = $p['price_period'] ?: 'month';
$features   = Plan::featureList($p);
?>
<?php if (!defined('PRICING_CSS_DONE')) { define('PRICING_CSS_DONE', 1); ?>
<style>
  .pricing-card { position: relative; height: 100%; display: flex; flex-direction: column;
                  background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);
                  border-radius: 18px; overflow: hidden; transition: transform .15s, box-shadow .15s, border-color .15s; }
  .pricing-card:hover { transform: translateY(-3px); box-shadow: 0 22px 44px -28px rgba(0,0,0,.45); }
  .pricing-card.featured { border-color: var(--brand); box-shadow: 0 22px 50px -26px color-mix(in srgb, var(--brand) 55%, transparent); }
  .pc-badge { position: absolute; top: 14px; right: 14px; background: var(--brand); color: #fff;
              font-size: .7rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
              padding: 4px 10px; border-radius: 999px; }
  .pc-head { padding: 28px 26px 22px; text-align: center; border-bottom: 1px solid var(--bs-border-color); }
  .pc-name { color: var(--brand); font-weight: 800; font-size: 1.25rem; margin: 0; }
  .pc-tagline { color: var(--bs-secondary-color); font-size: .9rem; margin-top: 4px; min-height: 1.2em; }
  .pc-price { margin-top: 18px; line-height: 1; display: flex; align-items: flex-start; justify-content: center; gap: 2px; }
  .pc-cur { font-size: 1.4rem; font-weight: 700; margin-top: 6px; }
  .pc-amount { font-size: 3.2rem; font-weight: 800; letter-spacing: -.02em; }
  .pc-period { color: var(--bs-secondary-color); font-size: 1rem; align-self: flex-end; margin-bottom: 10px; }
  .pc-free { font-size: 2.4rem; font-weight: 800; }
  .pc-billed { color: var(--bs-secondary-color); font-size: .88rem; margin-top: 8px; min-height: 1.1em; }
  .pc-features { list-style: none; margin: 0; padding: 24px 26px; flex: 1 1 auto; }
  .pc-feat { display: flex; align-items: flex-start; gap: 10px; padding: 7px 0; font-size: .95rem; color: var(--bs-body-color); }
  .pc-feat i { color: #22c55e; margin-top: 3px; flex: 0 0 auto; }
  .pc-feat.excluded { color: var(--bs-secondary-color); text-decoration: line-through; }
  .pc-feat.excluded i { color: var(--bs-secondary-color); }
  .pc-footer { padding: 0 26px 26px; }
  .pc-admin-tools { display: flex; gap: 8px; }
</style>
<?php } ?>
<div class="pricing-card <?= $cardHi ? 'featured' : '' ?>">
  <?php if ($cardBadge): ?><span class="pc-badge"><?= e($cardBadge) ?></span><?php endif; ?>
  <div class="pc-head">
    <h3 class="pc-name"><?= e($p['name']) ?></h3>
    <div class="pc-tagline"><?= e($p['tagline'] ?? '') ?></div>
    <div class="pc-price">
      <?php if ($price > 0): ?>
        <span class="pc-cur"><?= e(BILLING_CURRENCY === 'INR' ? '₹' : '$') ?></span>
        <span class="pc-amount"><?= number_format($price, 0) ?></span>
        <span class="pc-period">/<?= e($period) ?></span>
      <?php else: ?>
        <span class="pc-cur"><?= e(BILLING_CURRENCY === 'INR' ? '₹' : '$') ?></span>
        <span class="pc-amount">0</span>
        <span class="pc-period">/<?= e($period) ?></span>
      <?php endif; ?>
    </div>
    <div class="pc-billed"><?= e($p['billed_note'] ?? '') ?></div>
  </div>
  <ul class="pc-features">
    <?php foreach ($features as $f): ?>
      <li class="pc-feat <?= $f['included'] ? '' : 'excluded' ?>">
        <i class="bi bi-<?= $f['included'] ? 'check-circle-fill' : 'dash-circle' ?>"></i>
        <span><?= e($f['label']) ?></span>
      </li>
    <?php endforeach; ?>
    <?php if (!$features): ?><li class="pc-feat text-muted"><span>No features listed.</span></li><?php endif; ?>
  </ul>
  <?php if ($cardFooter !== ''): ?><div class="pc-footer"><?= $cardFooter ?></div><?php endif; ?>
</div>
