<?php
/** Shared bare layout for the 4-step onboarding wizard. Set $obStep (1-4) before requiring. */
$__step = $obStep ?? 1;
$__labels = [1 => 'Step 1', 2 => 'Step 2', 3 => 'Step 3', 4 => 'Step 4'];
/** @var array $user Current user — resolved here so it's available in step1/2/3.php. */
$user = Auth::user();
?><!doctype html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Get started') ?> · <?= e(Site::metaTitle()) ?></title>
<link rel="icon" href="<?= e(Site::faviconUrl()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="ob-body">
<button type="button" class="icon-btn ob-theme-toggle" id="obThemeToggle" title="Toggle theme" aria-label="Toggle theme">
  <i class="bi bi-moon-stars-fill"></i>
</button>
<div class="ob-blob-a"></div>
<div class="ob-blob-b"></div>
<div class="ob-wrap">
  <div class="ob-steps">
    <?php foreach ([1, 2, 3, 4] as $n): $state = $n < $__step ? 'done' : ($n === $__step ? 'active' : ''); ?>
      <div class="seg <?= $state ?>">
        <span class="dot"><?= $n < $__step ? '<i class="bi bi-check-lg" style="font-size:.7rem"></i>' : $n ?></span>
        <span class="lbl"><?= $__labels[$n] ?></span>
      </div>
      <?php if ($n < 4): ?><span class="line"></span><?php endif; ?>
    <?php endforeach; ?>
  </div>
