<?php /** @var array $stats @var array $cards @var array $series @var array $recentCampaigns @var ?array $topCampaign @var array $smtpAccounts */
$sparkColors = [
  'violet' => '#6d5efc', 'pink' => '#ec4899', 'green' => '#10b981', 'blue' => '#3b82f6',
  'orange' => '#f59e0b', 'sky' => '#0ea5e9', 'red' => '#ef4444', 'teal' => '#14b8a6',
];
?>

<?php if (!empty($onboarding) && !$onboarding['complete']):
  $gsAccents = ['#22d3ee', '#fbbf24', '#34d399', '#60a5fa', '#f472b6', '#a78bfa'];
  $firstPending = null;
  foreach ($onboarding['steps'] as $i => $s) { if (!$s['done'] && $firstPending === null) { $firstPending = $i; } }
?>
<!-- Getting-started timeline (auto-hides when complete) -->
<div class="card mb-4 border-0">
  <div class="card-body">
    <div class="gs-wrap">
      <h2 class="gs-title"><?= e($greeting ?? 'Welcome') ?>, <?= e($firstName ?? 'there') ?> <span class="wave-emoji">👋</span></h2>
      <p class="gs-sub">Welcome to <?= e(Site::name()) ?>! To send your first email campaign, please complete these simple steps.</p>
      <div class="gs-timeline">
        <?php foreach ($onboarding['steps'] as $i => $s):
          $rowClass = $s['done'] ? 'gs-done' : ($i === $firstPending ? 'gs-current' : '');
          $accent = $gsAccents[$i % count($gsAccents)];
        ?>
          <div class="gs-row <?= $rowClass ?>">
            <div class="gs-num-col"><div class="gs-num"><?= $s['done'] ? '<i class="bi bi-check-lg"></i>' : ($i + 1) ?></div></div>
            <a href="<?= url($s['url']) ?>" class="gs-card <?= $s['done'] ? 'gs-done' : '' ?>" style="--gs-accent:<?= e($accent) ?>">
              <div class="gs-icon"><i class="bi bi-<?= e($s['icon']) ?>"></i></div>
              <div>
                <div class="gs-card-title"><?= e($s['title']) ?></div>
                <div class="gs-card-desc"><?= e($s['desc']) ?></div>
              </div>
              <?php if ($s['done']): ?><i class="bi bi-check-circle-fill gs-check"></i><?php endif; ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($onboarding) && $onboarding['complete']): ?>
<!-- Stat cards -->
<div class="row g-3 mb-3">
  <?php foreach ($cards as $c):
    $delta = (float) $c['delta'];
    $dir = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    $arrow = $delta > 0 ? 'arrow-up' : ($delta < 0 ? 'arrow-down' : 'dash');
  ?>
    <div class="col-6 col-xl-3">
      <div class="stat-card stat-card--spark">
        <div class="stat-head">
          <div class="stat-icon grad-<?= e($c['grad']) ?>"><i class="bi bi-<?= e($c['icon']) ?>"></i></div>
          <div class="stat-top">
            <div class="stat-label"><?= e($c['label']) ?></div>
            <div class="stat-value"><?= e($c['display']) ?></div>
          </div>
          <?= sparkline_svg($c['spark'], $sparkColors[$c['grad']] ?? '#6d5efc') ?>
        </div>
        <div class="stat-foot">
          <span class="delta <?= $dir ?>"><i class="bi bi-<?= $arrow ?>"></i><?= $delta == 0.0 ? '0' : abs($delta) ?>%<span class="muted">vs last 14 days</span></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($stats['queue_pending'] > 0): ?>
  <div class="alert alert-info d-flex align-items-center">
    <i class="bi bi-hourglass-split me-2"></i>
    <?= (int) $stats['queue_pending'] ?> email(s) waiting in the send queue — the cron worker processes them in batches.
  </div>
<?php endif; ?>

<!-- Chart + engagement -->
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Sends, Opens &amp; Clicks <span class="text-muted fw-normal">(last 14 days)</span></span>
        <span class="date-pill"><i class="bi bi-calendar3"></i> Last 14 Days</span>
      </div>
      <div class="card-body"><canvas id="activityChart" height="96"></canvas></div>
      <div class="summary-strip">
        <?php
        $tot = ['Emails Sent' => array_sum($series['sent']), 'Total Opens' => array_sum($series['opens']), 'Total Clicks' => array_sum($series['clicks'])];
        $items = [
          ['send-fill', 'bg-soft-primary', array_sum($series['sent']), 'Emails Sent'],
          ['eye-fill', 'bg-soft-warning', array_sum($series['opens']), 'Total Opens'],
          ['cursor-fill', 'bg-soft-info', array_sum($series['clicks']), 'Total Clicks'],
          ['graph-up-arrow', 'bg-soft-success', $stats['click_rate'] . '%', 'Avg. Click Rate'],
        ];
        foreach ($items as [$ic, $bg, $val, $lbl]): ?>
          <div class="summary-item">
            <span class="si-ico <?= $bg ?>"><i class="bi bi-<?= $ic ?>"></i></span>
            <span><span class="si-val"><?= e((string) $val) ?></span><br><span class="si-lbl"><?= $lbl ?></span></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card mb-3">
      <div class="card-header">Engagement Overview</div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-5"><canvas id="engagementChart"></canvas></div>
          <div class="col-7">
            <?php
            $legend = [
              ['#10b981', 'Opens', $stats['open_rate'] . '%', $stats['opens']],
              ['#3b82f6', 'Clicks', $stats['click_rate'] . '%', $stats['clicks']],
              ['#f59e0b', 'Unsubscribed', $stats['unsub_rate'] . '%', $stats['unsubscribed']],
              ['#8b5cf6', 'Bounced', $stats['bounce_rate'] . '%', $stats['failed']],
            ];
            foreach ($legend as [$col, $lbl, $pct, $n]): ?>
              <div class="legend-row">
                <span class="dot" style="background:<?= $col ?>"></span><?= $lbl ?>
                <span class="lg-val"><?= e((string) $pct) ?> <span class="lg-sub">(<?= (int) $n ?>)</span></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Top performing campaign -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-trophy-fill text-warning"></i> Top Performing Campaign</span>
        <a href="<?= url('campaigns') ?>" class="text-brand"><i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="card-body">
        <?php if ($topCampaign): ?>
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="stat-icon grad-violet" style="width:44px;height:44px;font-size:1.1rem"><i class="bi bi-send-fill"></i></span>
            <div>
              <div class="fw-bold"><?= e($topCampaign['name']) ?></div>
              <div class="text-muted small">Created <?= fmt_dt($topCampaign['created_at']) ?></div>
            </div>
            <span class="ms-auto"><?= status_badge($topCampaign['status']) ?></span>
          </div>
          <div class="row text-center g-2">
            <?php foreach ([['Sent', (int) $topCampaign['sent_count']], ['Opens', $topCampaign['open_rate'] . '%'], ['Clicks', $topCampaign['click_rate'] . '%'], ['Clicks', (int) $topCampaign['clicks']]] as $i => [$l, $v]): ?>
              <div class="col-3"><div class="fw-bold"><?= e((string) $v) ?></div><div class="text-muted small"><?= $l ?></div></div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state py-3"><i class="bi bi-trophy"></i><p class="mt-2 mb-0 small">No campaign data yet.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Recent campaigns + SMTP -->
<div class="row g-3 mt-0">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-megaphone-fill text-brand me-1"></i> Recent Campaigns</span>
        <a href="<?= url('campaigns/edit') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New</a>
      </div>

      <?php if (!$recentCampaigns): ?>
        <div class="card-body">
          <div class="rc-empty">
            <span class="rc-empty-ico grad-violet"><i class="bi bi-send-fill"></i></span>
            <h6 class="mb-1">No campaigns yet</h6>
            <p class="text-muted small mb-3">Create your first campaign and start reaching your <strong><?= number_format($stats['contacts'] ?? 0) ?></strong> contacts.</p>
            <a href="<?= url('campaigns/edit') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create campaign</a>
          </div>
        </div>
      <?php else: ?>
        <div class="rc-list">
          <?php
          $grads = ['grad-violet','grad-pink','grad-blue','grad-orange','grad-teal'];
          foreach ($recentCampaigns as $i => $c):
            $sent = max(1, (int) $c['sent_count']);
            $openR  = round((int) $c['opens'] / $sent * 100, 1);
            $clickR = round((int) $c['clicks'] / $sent * 100, 1);
          ?>
            <a class="rc-row" href="<?= url('campaigns/show?id=' . $c['id']) ?>">
              <span class="rc-ico <?= $grads[$i % count($grads)] ?>"><i class="bi bi-send-fill"></i></span>
              <div class="rc-main">
                <div class="rc-name text-truncate"><?= e($c['name']) ?></div>
                <div class="rc-sub"><?= status_badge($c['status']) ?> <span class="text-muted">· <?= fmt_dt($c['created_at']) ?></span></div>
              </div>
              <div class="rc-metrics">
                <div class="rc-metric"><div class="rc-val"><?= number_format((int) $c['sent_count']) ?></div><div class="rc-lbl">Sent</div></div>
                <div class="rc-metric"><div class="rc-val text-success"><?= $openR ?>%</div><div class="rc-lbl">Opens</div></div>
                <div class="rc-metric"><div class="rc-val text-primary"><?= $clickR ?>%</div><div class="rc-lbl">Clicks</div></div>
              </div>
              <i class="bi bi-chevron-right rc-chev"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-hdd-network-fill text-brand me-1"></i> SMTP Accounts</span>
        <a href="<?= url('smtp') ?>" class="text-brand small fw-semibold">View All</a>
      </div>
      <div class="card-body">
        <?php if (!$smtpAccounts): ?>
          <div class="rc-empty py-3">
            <span class="rc-empty-ico grad-teal"><i class="bi bi-hdd-network-fill"></i></span>
            <h6 class="mb-1">No SMTP connected</h6>
            <p class="text-muted small mb-3">Connect a sending account to start sending.</p>
            <a href="<?= url('smtp') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i> Add SMTP</a>
          </div>
        <?php else: foreach ($smtpAccounts as $a):
          $limit = (int) $a['daily_limit'];
          $today = (int) $a['sent_today'];
          $pct = $limit > 0 ? min(100, round($today / $limit * 100)) : 0;
          $on = (int) $a['is_enabled'] === 1;
          $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success'); ?>
          <div class="smtp-row">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="smtp-dot <?= $on ? 'on' : 'off' ?>"></span>
              <span class="fw-semibold text-truncate flex-grow-1"><?= e($a['label']) ?></span>
              <span class="badge bg-<?= $on ? 'success' : 'secondary' ?>-subtle text-<?= $on ? 'success' : 'secondary' ?>-emphasis"><?= $on ? 'On' : 'Off' ?></span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-1">
              <span><?= e(provider_label($a['provider'] ?? 'custom')) ?></span>
              <span><?= number_format($today) ?><?= $limit > 0 ? ' / ' . number_format($limit) : '' ?> today</span>
            </div>
            <div class="progress" style="height:5px"><div class="progress-bar <?= $barColor ?>" style="width:<?= max(2, $pct) ?>%"></div></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
const series = <?= json_encode($series) ?>;
const eng = <?= json_encode(['opens' => (int) $stats['opens'], 'clicks' => (int) $stats['clicks'], 'unsub' => (int) $stats['unsubscribed'], 'bounced' => (int) $stats['failed']]) ?>;

const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-border-color') || 'rgba(0,0,0,.06)';
new Chart(document.getElementById('activityChart'), {
  type: 'line',
  data: { labels: series.labels, datasets: [
    { label: 'Emails Sent', data: series.sent, borderColor: '#6d5efc', backgroundColor: 'rgba(109,94,252,.12)', tension: .4, fill: true, pointRadius: 3, pointBackgroundColor: '#6d5efc' },
    { label: 'Opens', data: series.opens, borderColor: '#10b981', backgroundColor: 'transparent', tension: .4, pointRadius: 3, pointBackgroundColor: '#10b981' },
    { label: 'Clicks', data: series.clicks, borderColor: '#f59e0b', backgroundColor: 'transparent', tension: .4, pointRadius: 3, pointBackgroundColor: '#f59e0b' },
  ]},
  options: { responsive: true, maintainAspectRatio: true,
    plugins: { legend: { position: 'top', align: 'start', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } } },
    scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } } }
});
new Chart(document.getElementById('engagementChart'), {
  type: 'doughnut',
  data: { labels: ['Opens', 'Clicks', 'Unsubscribed', 'Bounced'], datasets: [{
    data: [eng.opens, eng.clicks, eng.unsub, eng.bounced || 1],
    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'], borderWidth: 0, cutout: '72%' }] },
  options: { plugins: { legend: { display: false } } }
});
</script>
