<?php
/** Dashboard layout - opening chrome (sidebar + topbar). */
$__user  = $user ?? Auth::user();
$__theme = $__user['theme'] ?? 'dark';
// Default to '' (not 'dashboard') so that when ?r= isn't set — pretty URLs or
// the PHP built-in server, where index.php derives the route from the path but
// never populates $_GET['r'] — the path fallback below resolves the real route.
$__route = (string) ($_GET['r'] ?? '');
if ($__route === '') {
    $__route = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/') ?: 'dashboard';
}
$__isAdmin = ($__user['role'] ?? '') === 'admin';
$__first = trim((string) ($__user['name'] ?? 'there'));
$__first = explode(' ', $__first)[0] ?: 'there';

$nav = static function (string $route, string $label, string $icon) use ($__route): string {
    $active = ($__route === $route || str_starts_with($__route, $route . '/')) ? ' active' : '';
    return '<a class="nav-link' . $active . '" href="' . url($route) . '">'
         . '<i class="bi bi-' . $icon . '"></i><span>' . $label . '</span></a>';
};
?><!doctype html>
<html lang="en" data-bs-theme="<?= e($__theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' · ' : '') . Site::metaTitle() ?></title>
<?php if ($__metaDesc = Site::metaDescription()): ?><meta name="description" content="<?= e($__metaDesc) ?>"><?php endif; ?>
<?php if ($__metaKeys = Site::metaKeywords()): ?><meta name="keywords" content="<?= e($__metaKeys) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
<!-- Libraries loaded before page content so inline charts/modals always work -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php // Chart.js is only needed where charts render — skip the ~70KB blocking load elsewhere.
if (in_array($__route, ['dashboard', '', 'reports'], true)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
</head>
<body>
<div class="app-shell">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <?php if (Site::showMark()): $__siteLogo = Site::logoUrl(); ?>
        <?php if ($__siteLogo): ?>
          <img src="<?= e($__siteLogo) ?>" alt="<?= e(Site::name()) ?>" class="brand-img">
        <?php else: ?>
          <span class="brand-logo"><i class="bi bi-send-fill"></i></span>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (Site::showName()): ?><span><?= e(Site::name()) ?></span><?php endif; ?>
    </div>
    <nav class="sidebar-nav">
      <?= $nav('dashboard',  'Dashboard',   'house-door-fill') ?>
      <?= $nav('contacts',   'Contacts',    'people-fill') ?>
      <?= $nav('email-verifier', 'Email Verifier', 'patch-check-fill') ?>
      <?= $nav('suppression','Suppression', 'slash-circle-fill') ?>
      <?= $nav('campaigns',  'Campaigns',   'send-fill') ?>
      <?= $nav('templates',  'Templates',   'file-richtext-fill') ?>
      <?= $nav('automations','Automations', 'diagram-3-fill') ?>
      <?= $nav('smtp',       'SMTP Accounts','hdd-network-fill') ?>
      <?= $nav('emails',     'Sent Emails', 'envelope-check-fill') ?>
      <?= $nav('reports',    'Reports',     'bar-chart-fill') ?>
      <?= $nav('billing',    'Billing & Plans', 'credit-card-2-front-fill') ?>
      <?= $nav('settings',   'Settings',    'gear-fill') ?>
      <?php if ($__isAdmin): ?>
        <div class="sidebar-section">Admin</div>
        <?= $nav('admin',       'Overview',  'pie-chart-fill') ?>
        <?= $nav('admin/users', 'Users',     'person-badge-fill') ?>
        <?= $nav('admin/plans', 'Plans',     'credit-card-fill') ?>
        <?= $nav('admin/site',  'Site Settings', 'sliders') ?>
        <?= $nav('admin/branding', 'Branding', 'palette2') ?>
        <?php $__pendingReq = PlanRequest::countPending();
          $__rActive = ($__route === 'admin/requests' || str_starts_with($__route, 'admin/requests/')) ? ' active' : ''; ?>
        <a class="nav-link<?= $__rActive ?>" href="<?= url('admin/requests') ?>">
          <i class="bi bi-bell-fill"></i><span>Requests</span>
          <?php if ($__pendingReq > 0): ?><span class="badge bg-danger rounded-pill ms-auto"><?= $__pendingReq ?></span><?php endif; ?>
        </a>
        <?= $nav('admin/logs',  'System Logs','journal-text') ?>
      <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
      <span><i class="bi bi-brightness-high-fill me-1"></i><span id="themeLabel"><?= $__theme === 'dark' ? 'Dark' : 'Light' ?> Mode</span></span>
      <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch" <?= $__theme === 'dark' ? 'checked' : '' ?>>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <button class="icon-btn d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      <div class="topbar-heading">
        <?php
        // The topbar always carries the current page title beside the search.
        // The dashboard additionally shows a welcome line; other pages keep
        // their own in-page `.page-head` for the larger heading + actions.
        $__isDashboard = in_array($__route ?? '', ['dashboard', ''], true);
        ?>
        <h1><?= e($pageTitle ?? ($__isDashboard ? 'Dashboard' : '')) ?></h1>
        <?php if ($__isDashboard): ?>
          <p class="welcome">Welcome back, <?= e($__first) ?> <span style="filter:grayscale(0)">👋</span></p>
        <?php endif; ?>
      </div>

      <button type="button" class="topbar-search" id="searchTrigger" aria-label="Search (Ctrl K)">
        <i class="bi bi-search search-ico"></i>
        <span class="topbar-search-ph">Search pages and actions…</span>
        <span class="kbd"><span class="cmdk-mod">Ctrl</span> K</span>
      </button>

      <div class="topbar-actions">
        <button class="icon-btn" id="themeToggle" title="Toggle theme">
          <i class="bi bi-<?= $__theme === 'dark' ? 'sun-fill' : 'moon-stars-fill' ?>"></i>
        </button>
        <?php
        $__notifs = $__user ? Notifications::forUser(
            (int) $__user['id'],
            (int) ($__user['is_verified'] ?? 1) === 1
        ) : [];
        $__notifCount = count($__notifs);
        ?>
        <div class="dropdown">
          <button class="icon-btn" type="button" title="Notifications" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
            <i class="bi bi-bell-fill"></i>
            <?php if ($__notifCount > 0): ?><span class="badge-dot"><?= $__notifCount ?></span><?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notif-menu">
            <div class="notif-head">
              <span>Notifications</span>
              <?php if ($__notifCount > 0): ?><span class="notif-count"><?= $__notifCount ?></span><?php endif; ?>
            </div>
            <?php if ($__notifCount === 0): ?>
              <div class="notif-empty">
                <i class="bi bi-check2-circle"></i>
                <span>You're all caught up.</span>
              </div>
            <?php else: ?>
              <?php foreach ($__notifs as $__n): ?>
                <a class="notif-item notif-<?= e($__n['type']) ?>" href="<?= e($__n['url']) ?>">
                  <span class="notif-ico"><i class="bi bi-<?= e($__n['icon']) ?>"></i></span>
                  <span class="notif-body">
                    <span class="notif-title"><?= e($__n['title']) ?></span>
                    <?php if (!empty($__n['time'])): ?>
                      <span class="notif-time"><?= e(Notifications::ago($__n['time'])) ?></span>
                    <?php endif; ?>
                  </span>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="dropdown">
          <button class="btn-user dropdown-toggle" data-bs-toggle="dropdown">
            <span class="avatar"><?= e(strtoupper(substr($__user['name'] ?? 'U', 0, 1))) ?></span>
            <span class="u-meta d-none d-sm-block">
              <span class="u-name d-block"><?= e($__user['name'] ?? '') ?></span>
              <span class="u-mail"><?= e($__user['email'] ?? '') ?></span>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= url('settings') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="<?= url('settings') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="toast-stack" id="toastStack"></div>
      <?php
      $__flashes = [];
      foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $type => $cls) {
          if ($m = flash($type)) { $__flashes[] = ['type' => $cls, 'msg' => $m]; }
      }
      if ($__flashes): ?>
        <script>window.__flashes = <?= js($__flashes) ?>;</script>
      <?php endif; ?>

      <?php if ($__user && (int) ($__user['is_verified'] ?? 1) === 0 && !$__isAdmin): ?>
        <div class="alert alert-warning d-flex align-items-center">
          <i class="bi bi-envelope-exclamation me-2"></i>
          Please verify your email address to unlock sending. Check your inbox for the verification link.
        </div>
      <?php endif; ?>
