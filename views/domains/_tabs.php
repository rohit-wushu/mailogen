<?php /** @var string $tab */ ?>
<div class="page-head">
  <h2>Authentication</h2>
</div>
<div class="auth-tabs">
  <a href="<?= url('domains') ?>" class="auth-tab <?= $tab === 'domains' ? 'active' : '' ?>">Domains</a>
  <a href="<?= url('domains/senders') ?>" class="auth-tab <?= $tab === 'senders' ? 'active' : '' ?>">Sender Management</a>
  <a href="<?= url('domains/reply-ids') ?>" class="auth-tab <?= $tab === 'reply-ids' ? 'active' : '' ?>">Reply IDs</a>
</div>
