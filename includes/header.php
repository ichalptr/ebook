<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#1F4D3A">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Pamulihan E-Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg pl-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
      <span class="brand-mark"><i class="bi bi-book-half"></i></span>
      Pamulihan E-Library
    </a>
    <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" style="filter:invert(1);">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' ? 'active-link' : '' ?>" href="<?= BASE_URL ?>/index.php">Beranda</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'katalog.php' ? 'active-link' : '' ?>" href="<?= BASE_URL ?>/katalog.php">Katalog Buku</a></li>
        <?php if ($user): ?>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'rak_saya.php' ? 'active-link' : '' ?>" href="<?= BASE_URL ?>/rak_saya.php">Rak Saya</a></li>
        <?php endif; ?>
      </ul>
      <form class="d-flex me-3 desktop-search" role="search" action="<?= BASE_URL ?>/katalog.php" method="get">
        <input class="form-control form-control-sm" type="search" name="q" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="min-width:220px;">
      </form>
      <ul class="navbar-nav align-items-lg-center">
        <?php if ($user): ?>
          <?php if ($user['role'] === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <?php elseif ($user['role'] === 'teacher'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/guru/dashboard.php"><i class="bi bi-easel"></i> Dashboard Guru</a></li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= htmlspecialchars($user['name']) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/rak_saya.php">Rak Saya</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Keluar</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login.php">Masuk</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-turmeric btn-sm" href="<?= BASE_URL ?>/register.php">Daftar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!--
  Pembungkus <main> ini yang membuat footer selalu menempel di bawah
  viewport (sticky footer), termasuk di halaman pendek seperti
  login.php/register.php. Body diset flex-column min-height:100vh di
  style.css; <main> ini flex:1 sehingga otomatis mengisi sisa ruang
  dan mendorong footer ke bawah. JANGAN hapus tag ini — footer.php
  menutup tag ini sebelum elemen <footer> dibuka.
-->
<main class="site-main">
