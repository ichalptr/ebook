<?php
$page_title = 'Beranda';
require_once __DIR__ . '/includes/header.php';

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

function book_cover_src(array $book): string {
    if (!empty($book['cover_image'])) {
        if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) {
            return htmlspecialchars($book['cover_image']);
        }
        return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
    }
    return 'https://via.placeholder.com/300x450/1f7a3d/ffffff?text=' . urlencode($book['title']);
}

function render_book_card(array $b): void {
    ?>
    <div class="col-6 col-md-3">
      <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$b['id'] ?>" class="text-decoration-none text-dark">
        <div class="card book-card">
          <div class="book-cover-wrap">
            <img src="<?= book_cover_src($b) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
          </div>
          <div class="card-body p-2">
            <span class="badge bg-success-subtle text-success-emphasis badge-grade mb-1"><?= htmlspecialchars($b['grade_level']) ?></span>
            <h6 class="mb-0 text-truncate"><?= htmlspecialchars($b['title']) ?></h6>
            <small class="text-muted text-truncate d-block"><?= htmlspecialchars($b['author'] ?? '-') ?></small>
          </div>
        </div>
      </a>
    </div>
    <?php
}
?>

<section class="hero-section text-center">
  <div class="container">
    <h1 class="display-5">Buka Gadget, Buka Buku.</h1>
    <p class="lead">Temukan cerita, ilmu, dan pengetahuan dalam satu perpustakaan digital untuk siswa Desa Pamulihan.</p>
    <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-light btn-lg mt-2"><i class="bi bi-search"></i> Jelajahi Katalog</a>
  </div>
</section>

<div class="container">
  <div class="row g-3 stat-box">
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
        <img src="<?= book_cover_src($todayBook) ?>" class="img-fluid rounded shadow-sm" alt="">
      </div>
      <div class="col-md-7 col-8">
        <h5 class="mb-1"><i class="bi bi-stars text-warning"></i> Baca Hari Ini</h5>
        <p class="mb-1 fw-semibold"><?= htmlspecialchars($todayBook['title']) ?></p>
        <p class="text-muted small mb-0">Pilih buku ini dan luangkan 15 menit untuk membaca hari ini.</p>
      </div>
      <div class="col-md-3 mt-3 mt-md-0 text-md-end">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$todayBook['id'] ?>" class="btn btn-success">Mulai Baca</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-collection"></i> Kategori</h4>
  </div>
  <div class="d-flex flex-wrap gap-2 mb-5">
    <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/katalog.php?category=<?= (int)$cat['id'] ?>" class="btn btn-outline-success btn-sm rounded-pill">
        <?= htmlspecialchars($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Buku Terbaru</h4>
    <a href="<?= BASE_URL ?>/katalog.php?sort=newest" class="small">Lihat semua &rarr;</a>
  </div>
  <div class="row g-3 mb-5">
    <?php if ($newBooks): foreach ($newBooks as $b): render_book_card($b); endforeach; else: ?>
      <p class="text-muted">Belum ada buku. Tambahkan lewat Dashboard Admin.</p>
    <?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-fire"></i> Buku Populer</h4>
    <a href="<?= BASE_URL ?>/katalog.php?sort=popular" class="small">Lihat semua &rarr;</a>
  </div>
  <div class="row g-3 mb-5">
    <?php if ($popularBooks): foreach ($popularBooks as $b): render_book_card($b); endforeach; else: ?>
      <p class="text-muted">Belum ada data.</p>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
