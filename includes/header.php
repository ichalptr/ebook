<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Pamulihan E-Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
      <i class="bi bi-book-half"></i> Pamulihan E-Library
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/katalog.php">Katalog Buku</a></li>
        <?php if ($user): ?>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/rak_saya.php">Rak Saya</a></li>
        <?php endif; ?>
      </ul>
      <form class="d-flex me-3" role="search" action="<?= BASE_URL ?>/katalog.php" method="get">
        <input class="form-control form-control-sm" type="search" name="q" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      </form>
      <ul class="navbar-nav">
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
          <li class="nav-item"><a class="btn btn-light btn-sm ms-2" href="<?= BASE_URL ?>/register.php">Daftar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
