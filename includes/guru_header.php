<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_role('teacher');
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);

$guruMenu = [
    ['dashboard.php', 'bi-speedometer2', 'Dashboard'],
    ['rekomendasi.php', 'bi-send-check', 'Rekomendasi Buku'],
];
$initial = mb_strtoupper(mb_substr($user['name'] ?? 'G', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Guru Pamulihan E-Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-backdrop" id="adminBackdrop"></div>
<div class="admin-shell">

  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand">
      <span class="brand-mark"><i class="bi bi-easel"></i></span>
      <span class="brand-text">
        <strong>E-Library Guru</strong>
        <span>Desa Pamulihan</span>
      </span>
    </div>

    <nav class="admin-nav">
      <div class="admin-nav-label">Menu</div>
      <?php foreach ($guruMenu as [$href, $icon, $label]): ?>
        <a href="<?= BASE_URL ?>/guru/<?= $href ?>" class="<?= $current === $href ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i> <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-foot">
      <a href="<?= BASE_URL ?>/index.php"><i class="bi bi-box-arrow-left"></i> Ke Situs Utama</a>
      <a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-power"></i> Keluar</a>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3 min-w-0">
        <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Buka menu">
          <i class="bi bi-list fs-5"></i>
        </button>
        <div class="min-w-0">
          <div class="crumb">Guru / <?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></div>
          <h1 class="text-truncate"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard Guru' ?></h1>
        </div>
      </div>

      <div class="admin-user">
        <div class="who">
          <strong><?= htmlspecialchars($user['name']) ?></strong>
          <span>Guru</span>
        </div>
        <div class="avatar"><?= htmlspecialchars($initial) ?></div>
      </div>
    </div>

    <div class="admin-content">
