<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_role('admin');
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);
$importPages = ['bookmarklet.php', 'import_official.php', 'import_books.php', 'bulk_import.php', 'import_csv.php'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Admin Pamulihan E-Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrap">
  <div class="admin-sidebar">
    <div class="brand"><i class="bi bi-book-half"></i> E-Library Admin</div>

    <div class="nav-group-label">Ringkasan</div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>

    <div class="nav-group-label">Konten</div>
    <a href="<?= BASE_URL ?>/admin/books.php" class="<?= $current === 'books.php' ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> Semua Buku</a>
    <a href="<?= BASE_URL ?>/admin/book_form.php" class="<?= $current === 'book_form.php' ? 'active' : '' ?>"><i class="bi bi-plus-circle"></i> Tambah Buku</a>
    <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $current === 'categories.php' ? 'active' : '' ?>"><i class="bi bi-tags"></i> Kategori</a>

    <div class="nav-group-label">Impor Data</div>
    <a href="<?= BASE_URL ?>/admin/import_official.php" class="<?= $current === 'import_official.php' ? 'active' : '' ?>"><i class="bi bi-bank"></i> Resmi (Kemendikdasmen)</a>
    <a href="<?= BASE_URL ?>/admin/bookmarklet.php" class="<?= $current === 'bookmarklet.php' ? 'active' : '' ?>"><i class="bi bi-bookmark-star"></i> Bookmarklet (Clip 1-Klik)</a>
    <a href="<?= BASE_URL ?>/admin/bulk_import.php" class="<?= $current === 'bulk_import.php' ? 'active' : '' ?>"><i class="bi bi-cloud-arrow-down"></i> Bulk Import (Google Books)</a>
    <a href="<?= BASE_URL ?>/admin/import_books.php" class="<?= $current === 'import_books.php' ? 'active' : '' ?>"><i class="bi bi-cloud-download"></i> Satu Buku (Google Books)</a>
    <a href="<?= BASE_URL ?>/admin/import_csv.php" class="<?= $current === 'import_csv.php' ? 'active' : '' ?>"><i class="bi bi-filetype-csv"></i> Import CSV</a>

    <div class="nav-group-label">Pengguna</div>
    <a href="<?= BASE_URL ?>/admin/users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Pengguna</a>

    <hr>
    <a href="<?= BASE_URL ?>/index.php"><i class="bi bi-box-arrow-left"></i> Ke Situs Utama</a>
    <a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-power"></i> Keluar</a>
  </div>
  <div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h4>
      <span class="text-muted small">Masuk sebagai <?= htmlspecialchars($user['name']) ?></span>
    </div>
