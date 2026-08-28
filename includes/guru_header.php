<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_role('teacher');
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);
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
<style>
  .admin-wrap { display:flex; min-height:100vh; }
  .admin-sidebar { width:230px; background:#1c1c1c; color:#fff; flex-shrink:0; }
  .admin-sidebar a { color:#ccc; display:block; padding:.6rem 1.2rem; text-decoration:none; }
  .admin-sidebar a.active, .admin-sidebar a:hover { background:#1f7a3d; color:#fff; }
  .admin-sidebar .brand { padding:1.2rem; font-weight:700; border-bottom:1px solid #333; }
  .admin-main { flex:1; padding:1.5rem 2rem; background:#f4f6f5; }
</style>
</head>
<body>
<div class="admin-wrap">
  <div class="admin-sidebar">
    <div class="brand"><i class="bi bi-easel"></i> E-Library Guru</div>
    <a href="<?= BASE_URL ?>/guru/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="<?= BASE_URL ?>/guru/rekomendasi.php" class="<?= $current === 'rekomendasi.php' ? 'active' : '' ?>"><i class="bi bi-send-check"></i> Rekomendasi Buku</a>
    <hr style="border-color:#333">
    <a href="<?= BASE_URL ?>/index.php"><i class="bi bi-box-arrow-left"></i> Ke Situs Utama</a>
    <a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-power"></i> Keluar</a>
  </div>
  <div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard Guru' ?></h4>
      <span class="text-muted small">Masuk sebagai <?= htmlspecialchars($user['name']) ?></span>
    </div>
