<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_role('admin');
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);

/** Struktur menu admin, dikelompokkan per bagian agar sidebar mudah dipindai. */
$adminMenu = [
    'Utama' => [
        ['dashboard.php', 'bi-speedometer2', 'Dashboard'],
    ],
    'Konten' => [
        ['books.php', 'bi-journal-bookmark', 'Semua Buku'],
        ['book_form.php', 'bi-plus-circle', 'Tambah Buku'],
        ['categories.php', 'bi-tags', 'Kategori'],
    ],
    'Import' => [
        ['bookmarklet.php', 'bi-bookmark-star', 'Bookmarklet SIBI'],
        ['import_official.php', 'bi-bank', 'Resmi Kemendikdasmen'],
        ['import_google_books.php', 'bi-cloud-arrow-down', 'Google Books'],
        ['import_csv.php', 'bi-filetype-csv', 'Import CSV'],
    ],
    'Pengguna' => [
        ['users.php', 'bi-people', 'Pengguna'],
    ],
];

function admin_page_title(string $current): string {
    $map = [
        'dashboard.php' => 'Dashboard', 'books.php' => 'Semua Buku', 'book_form.php' => 'Form Buku',
        'categories.php' => 'Kategori', 'users.php' => 'Pengguna', 'bookmarklet.php' => 'Bookmarklet Import',
        'import_official.php' => 'Import Resmi Kemendikdasmen', 'import_google_books.php' => 'Google Books',
        'import_csv.php' => 'Import CSV', 'quick_add.php' => 'Quick Add',
    ];
    return $map[$current] ?? 'Admin';
}
$initial = mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>Admin Pamulihan E-Library</title>
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
      <span class="brand-mark"><i class="bi bi-book-half"></i></span>
      <span class="brand-text">
        <strong>E-Library Admin</strong>
        <span>Desa Pamulihan</span>
      </span>
    </div>

    <nav class="admin-nav">
      <?php foreach ($adminMenu as $section => $items): ?>
        <div class="admin-nav-label"><?= htmlspecialchars($section) ?></div>
        <?php foreach ($items as [$href, $icon, $label]): ?>
          <a href="<?= BASE_URL ?>/admin/<?= $href ?>" class="<?= $current === $href ? 'active' : '' ?>">
            <i class="bi <?= $icon ?>"></i> <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
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
          <div class="crumb">Admin / <?= htmlspecialchars(admin_page_title($current)) ?></div>
          <h1 class="text-truncate"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h1>
        </div>
      </div>

      <form action="<?= BASE_URL ?>/admin/books.php" method="get" class="admin-topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" name="q" placeholder="Cari buku di katalog admin...">
      </form>

      <div class="admin-user">
        <div class="who">
          <strong><?= htmlspecialchars($user['name']) ?></strong>
          <span><?= htmlspecialchars($user['role']) ?></span>
        </div>
        <div class="avatar"><?= htmlspecialchars($initial) ?></div>
      </div>
    </div>

    <div class="admin-content">
