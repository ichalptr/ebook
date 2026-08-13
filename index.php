<?php
$page_title = 'Beranda';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/terrace_divider.php';
require_once __DIR__ . '/includes/cover_helper.php';
require_once __DIR__ . '/includes/book_card_helper.php'; // <-- NEW

// Statistik ringkas
$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalSchools = (int)$pdo->query("SELECT COUNT(DISTINCT school) FROM users WHERE school IS NOT NULL AND school <> ''")->fetchColumn();

// Buku terbaru
$stmt = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                      LEFT JOIN categories c ON c.id = b.category_id
                      ORDER BY b.created_at DESC LIMIT 8");
$newBooks = $stmt->fetchAll();

// Buku populer (berdasarkan views)
$stmt = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                      LEFT JOIN categories c ON c.id = b.category_id
                      ORDER BY b.views DESC LIMIT 8");
$popularBooks = $stmt->fetchAll();

// Semua kategori
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Rekomendasi "Baca Hari Ini" — pilih 1 buku acak
$todayBook = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                           LEFT JOIN categories c ON c.id = b.category_id
                           ORDER BY RAND() LIMIT 1")->fetch();
?>

<section class="hero-section text-center">
  <div class="container">
    <span class="eyebrow">Desa Pamulihan · Kec. Pamulihan</span>
    <h1>Buka Gadget, Buka Buku.</h1>
    <p class="lead mt-3">Temukan cerita, ilmu, dan pengetahuan dalam satu perpustakaan digital untuk siswa Desa Pamulihan — cukup pindai QR code di sekolahmu.</p>
    <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-light btn-lg mt-3"><i class="bi bi-search"></i> Jelajahi Katalog</a>
  </div>
  <?php render_terrace_divider(); ?>
</section>

<div class="container">
  <div class="row g-0 stat-box">
    <div class="col-6 col-md-3">
      <div class="number"><?= $totalBooks ?>+</div>
      <div class="text-muted small">Buku</div>
    </div>
    <div class="col-6 col-md-3">
      <div class="number"><?= $totalCategories ?></div>
      <div class="text-muted small">Kategori</div>
    </div>
    <div class="col-6 col-md-3">
      <div class="number"><?= $totalSchools ?></div>
      <div class="text-muted small">Sekolah</div>
    </div>
    <div class="col-6 col-md-3">
      <div class="number"><?= $totalStudents ?></div>
      <div class="text-muted small">Siswa</div>
    </div>
  </div>
</div>

<div class="container mt-5">

  <?php if ($todayBook): ?>
  <div class="today-read-card p-4 mb-5">
    <div class="row align-items-center">
      <div class="col-md-2 col-4">
        <div class="book-cover-wrap rounded shadow-sm" style="aspect-ratio:2/3;">
          <?= book_cover_html($todayBook) ?>
        </div>
      </div>
      <div class="col-md-7 col-8">
        <h5 class="mb-1"><i class="bi bi-stars text-warning"></i> Baca Hari Ini</h5>
        <p class="mb-1 fw-semibold"><?= htmlspecialchars($todayBook['title']) ?></p>
        <p class="text-muted small mb-0">Pilih buku ini dan luangkan 15 menit untuk membaca hari ini.</p>
      </div>
      <div class="col-md-3 mt-3 mt-md-0 text-md-end">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$todayBook['id'] ?>" class="btn btn-brand">Mulai Baca</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="section-heading">
    <span class="kicker">Jelajahi</span>
    <h4 class="mb-0"><i class="bi bi-collection"></i> Kategori</h4>
  </div>
  <div class="d-flex flex-wrap gap-2 mb-5">
    <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/katalog.php?category=<?= (int)$cat['id'] ?>" class="category-pill">
        <?= htmlspecialchars($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-heading mb-0">
      <span class="kicker">Baru masuk</span>
      <h4 class="mb-0"><i class="bi bi-clock-history"></i> Buku Terbaru</h4>
    </div>
    <a href="<?= BASE_URL ?>/katalog.php?sort=newest" class="small">Lihat semua &rarr;</a>
  </div>
  <?php if ($newBooks): ?>
    <div class="scroll-row mb-5">
      <?php foreach ($newBooks as $b): render_book_card($b, 'scroll-item'); endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state mb-5">
      <i class="bi bi-inbox"></i>
      <p class="mt-2 mb-0">Belum ada buku. Tambahkan lewat Dashboard Admin.</p>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-heading mb-0">
      <span class="kicker">Favorit siswa</span>
      <h4 class="mb-0"><i class="bi bi-fire"></i> Buku Populer</h4>
    </div>
    <a href="<?= BASE_URL ?>/katalog.php?sort=popular" class="small">Lihat semua &rarr;</a>
  </div>
  <?php if ($popularBooks): ?>
    <div class="scroll-row mb-5">
      <?php foreach ($popularBooks as $b): render_book_card($b, 'scroll-item'); endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state mb-5">
      <i class="bi bi-inbox"></i>
      <p class="mt-2 mb-0">Belum ada data.</p>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>