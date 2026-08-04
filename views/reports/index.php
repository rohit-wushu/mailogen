<?php /** @var array $campaignsList @var int $selectedId @var array $summary @var array $topLinks @var int $totalClicks @var array $campaigns @var array $smtp @var array $series @var array $logs */ ?>
<div class="page-head">
  <h2>Campaign Report</h2>
  <div class="d-flex gap-2">
    <form method="get" action="<?= url('reports') ?>">
      <select class="form-select" name="campaign_id" onchange="this.form.submit()" style="min-width:220px">
        <?php if (!$campaignsList): ?><option>No campaigns</option><?php endif; ?>
        <?php foreach ($campaignsList as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $selectedId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <span class="date-pill"><i class="bi bi-calendar3"></i> Last 14 Days</span>
    <button class="btn btn-light" onclick="window.print()"><i class="bi bi-download"></i> Export</button>
  </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-3">
  <?php
  $cells = [
    ['Sent', $summary['sent'], '', 'send-check-fill', 'green'],
    ['Delivered', $summary['delivered'], '100%', 'envelope-check-fill', 'blue'],
    ['Opens', $summary['opened'], $summary['open_rate'] . '%', 'eye-fill', 'orange'],
    ['Clicks', $summary['clicked'], $summary['click_rate'] . '%', 'cursor-fill', 'sky'],
    ['Unsubscribed', $summary['unsubscribed'], '0%', 'person-dash-fill', 'red'],
    ['Bounced', $summary['bounced'], '0%', 'x-octagon-fill', 'pink'],
  ];
  foreach ($cells as [$l, $v, $sub, $i, $g]): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card" style="grid-template-rows:auto;">
        <div class="stat-icon grad-<?= $g ?>" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-<?= $i ?>"></i></div>
        <div class="stat-top">
          <div class="stat-label"><?= $l ?></div>
          <div class="stat-value" style="font-size:1.4rem"><?= (int) $v ?> <?php if ($sub): ?><span class="text-success" style="font-size:.8rem">↑ <?= $sub ?></span><?php endif; ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-3">
  <div class="card-header">Overview</div>
  <div class="card-body"><canvas id="ovChart" height="80"></canvas></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card h-100"><div class="card-header">Open Rate</div><div class="card-body">
    <div class="donut-stat"><canvas id="openDonut"></canvas><div class="donut-center"><div class="dv"><?= $summary['open_rate'] ?>%</div><div class="dl"><?= (int) $summary['opened'] ?> Opens</div></div></div>
  </div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-header">Click Rate</div><div class="card-body">
    <div class="donut-stat"><canvas id="clickDonut"></canvas><div class="donut-center"><div class="dv"><?= $summary['click_rate'] ?>%</div><div class="dl"><?= (int) $summary['clicked'] ?> Clicks</div></div></div>
  </div></div></div>
  <div class="col-md-6"><div class="card h-100"><div class="card-header">Top Links Clicked</div><div class="card-body">
    <?php if (!$topLinks): ?><p class="text-muted small mb-0">No link clicks recorded yet.</p>
    <?php else: foreach ($topLinks as $l): $p = round((int) $l['c'] / $totalClicks * 100, 1); ?>
      <div class="toplink-row">
        <i class="bi bi-link-45deg text-brand"></i>
        <span class="tl-url"><?= e(mb_strimwidth($l['url'], 0, 42, '…')) ?></span>
        <span class="tl-count"><?= (int) $l['c'] ?></span><span class="tl-pct">(<?= $p ?>%)</span>
      </div>
    <?php endforeach; endif; ?>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7"><div class="card h-100">
    <div class="card-header">All Campaigns</div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
      <thead><tr><th>Campaign</th><th>Sent</th><th>Open %</th><th>Click %</th></tr></thead><tbody>
      <?php if (!$campaigns): ?><tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
      <?php else: foreach ($campaigns as $c): $s = max(1, (int) $c['sent']); ?>
        <tr><td class="fw-semibold"><a class="text-brand" href="<?= url('reports?campaign_id=' . $c['id']) ?>"><?= e($c['name']) ?></a></td>
        <td><?= (int) $c['sent'] ?></td>
        <td><span class="badge bg-info-subtle text-info-emphasis"><?= round((int) $c['opened'] / $s * 100, 1) ?>%</span></td>
        <td><span class="badge bg-warning-subtle text-warning-emphasis"><?= round((int) $c['clicked'] / $s * 100, 1) ?>%</span></td></tr>
      <?php endforeach; endif; ?>
      </tbody></table></div>
  </div></div>
  <div class="col-lg-5"><div class="card h-100">
    <div class="card-header">SMTP Performance</div>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Account</th><th>Sent</th><th>Failed</th><th>Success</th></tr></thead><tbody>
      <?php if (!$smtp): ?><tr><td colspan="4" class="text-center text-muted py-3">No SMTP.</td></tr>
      <?php else: foreach ($smtp as $s): $tot = (int) $s['sent_total'] + (int) $s['fail_total']; $sr = $tot ? round((int) $s['sent_total'] / $tot * 100, 1) : 100; ?>
        <tr><td class="fw-semibold small"><?= e($s['label']) ?></td><td><?= (int) $s['sent_total'] ?></td><td><?= (int) $s['fail_total'] ?></td>
        <td><span class="badge bg-<?= $sr >= 90 ? 'success' : ($sr >= 60 ? 'warning' : 'danger') ?>-subtle"><?= $sr ?>%</span></td></tr>
      <?php endforeach; endif; ?>
      </tbody></table></div>
  </div></div>
</div>

<script>
const rs = <?= json_encode($series) ?>;
const sum = <?= json_encode($summary) ?>;
const grid = getComputedStyle(document.documentElement).getPropertyValue('--bs-border-color') || 'rgba(0,0,0,.06)';
new Chart(document.getElementById('ovChart'), {
  type: 'line',
  data: { labels: rs.labels, datasets: [
    { label: 'Sent', data: rs.sent, borderColor: '#6d5efc', backgroundColor: 'rgba(109,94,252,.10)', tension: .4, fill: true, pointRadius: 2 },
    { label: 'Opens', data: rs.opens, borderColor: '#10b981', tension: .4, pointRadius: 2 },
    { label: 'Clicks', data: rs.clicks, borderColor: '#3b82f6', tension: .4, pointRadius: 2 },
  ]},
  options: { plugins: { legend: { position: 'top', align: 'start', labels: { usePointStyle: true, boxWidth: 8 } } }, scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } } }
});
function donut(id, val, color){
  new Chart(document.getElementById(id), { type: 'doughnut',
    data: { datasets: [{ data: [val, Math.max(0, 100 - val)], backgroundColor: [color, '#eef0f6'], borderWidth: 0, cutout: '78%' }] },
    options: { plugins: { legend: { display: false }, tooltip: { enabled: false } } } });
}
donut('openDonut', sum.open_rate, '#10b981');
donut('clickDonut', sum.click_rate, '#3b82f6');
</script>
