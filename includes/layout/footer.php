    </main>
    <footer class="app-foot text-muted small px-4 py-3 d-flex flex-wrap justify-content-between gap-2 border-top">
      <span>© <?= date('Y') ?> <?= e(Site::name()) ?></span>
      <span class="d-flex gap-3">
        <a href="<?= url('legal/terms') ?>" target="_blank" class="text-muted">Terms</a>
        <a href="<?= url('legal/privacy') ?>" target="_blank" class="text-muted">Privacy</a>
        <a href="<?= url('legal/acceptable-use') ?>" target="_blank" class="text-muted">Acceptable Use</a>
      </span>
    </footer>
  </div>
</div>

<form id="themeForm" method="post" action="<?= url('settings/theme') ?>" class="d-none">
  <?= csrf_field() ?>
  <input type="hidden" name="theme" id="themeInput">
</form>

<!-- Command palette (Ctrl/Cmd + K) -->
<div class="cmdk" id="cmdk" aria-hidden="true">
  <div class="cmdk-box" role="dialog" aria-modal="true" aria-label="Search">
    <div class="cmdk-head">
      <i class="bi bi-search"></i>
      <input id="cmdkInput" type="text" placeholder="Search pages and actions…" autocomplete="off" spellcheck="false">
      <kbd>ESC</kbd>
    </div>
    <div class="cmdk-list" id="cmdkList"></div>
    <div class="cmdk-empty d-none" id="cmdkEmpty"><i class="bi bi-search"></i><div>No matches</div></div>
  </div>
</div>

<?php
$__cmdk = [
  ['g' => 'Pages', 'label' => 'Dashboard',       'icon' => 'house-door-fill',        'url' => url('dashboard')],
  ['g' => 'Pages', 'label' => 'Contacts',        'icon' => 'people-fill',            'url' => url('contacts')],
  ['g' => 'Pages', 'label' => 'Email Verifier',  'icon' => 'patch-check-fill',       'url' => url('email-verifier')],
  ['g' => 'Pages', 'label' => 'Suppression list','icon' => 'slash-circle-fill',      'url' => url('suppression')],
  ['g' => 'Pages', 'label' => 'Campaigns',       'icon' => 'send-fill',              'url' => url('campaigns')],
  ['g' => 'Pages', 'label' => 'Templates',       'icon' => 'file-richtext-fill',     'url' => url('templates')],
  ['g' => 'Pages', 'label' => 'Automations',     'icon' => 'diagram-3-fill',         'url' => url('automations')],
  ['g' => 'Pages', 'label' => 'Deliverability',  'icon' => 'shield-check',           'url' => url('deliverability')],
  ['g' => 'Pages', 'label' => 'Sent Emails',     'icon' => 'envelope-check-fill',    'url' => url('emails')],
  ['g' => 'Pages', 'label' => 'Reports',         'icon' => 'bar-chart-fill',         'url' => url('reports')],
  ['g' => 'Pages', 'label' => 'Billing & Plans', 'icon' => 'credit-card-2-front-fill','url' => url('billing')],
  ['g' => 'Pages', 'label' => 'Settings',        'icon' => 'gear-fill',              'url' => url('settings')],
  ['g' => 'Actions', 'label' => 'New campaign',  'icon' => 'plus-circle',            'url' => url('campaigns/edit')],
  ['g' => 'Actions', 'label' => 'New template',  'icon' => 'plus-circle',            'url' => url('templates/edit')],
  ['g' => 'Actions', 'label' => 'Import contacts','icon' => 'upload',                'url' => url('contacts/import')],
  ['g' => 'Actions', 'label' => 'Verify an email','icon' => 'patch-check',           'url' => url('email-verifier')],
];
if (($user['team_role'] ?? 'owner') !== 'member') {
  $__cmdk[] = ['g' => 'Pages', 'label' => 'SMTP Accounts', 'icon' => 'hdd-network-fill', 'url' => url('smtp')];
  $__cmdk[] = ['g' => 'Actions', 'label' => 'Add SMTP account', 'icon' => 'plus-circle', 'url' => url('smtp')];
}
if (Auth::isAdmin()) {
  $__cmdk[] = ['g' => 'Admin', 'label' => 'Admin Overview', 'icon' => 'pie-chart-fill',   'url' => url('admin')];
  $__cmdk[] = ['g' => 'Admin', 'label' => 'Manage Users',   'icon' => 'person-badge-fill','url' => url('admin/users')];
  $__cmdk[] = ['g' => 'Admin', 'label' => 'Plans',          'icon' => 'credit-card-fill', 'url' => url('admin/plans')];
  $__cmdk[] = ['g' => 'Admin', 'label' => 'System Logs',    'icon' => 'journal-text',     'url' => url('admin/logs')];
}
?>

<script>
  window.APP_URL = <?= json_encode(rtrim(APP_URL, '/')) ?>;
  window.CSRF = <?= json_encode(csrf_token()) ?>;
  window.CMDK_ITEMS = <?= js($__cmdk) ?>;
</script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
