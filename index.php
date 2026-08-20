<?php
$page_title = 'Beranda';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cover_helper.php';
require_once __DIR__ . '/includes/book_card_helper.php';

$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalSchools = (int)$pdo->query("SELECT COUNT(DISTINCT school) FROM users WHERE school IS NOT NULL AND school <> ''")->fetchColumn();

$stmt = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                      LEFT JOIN categories c ON c.id = b.category_id
                      ORDER BY b.created_at DESC LIMIT 8");
$newBooks = $stmt->fetchAll();

$stmt = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                      LEFT JOIN categories c ON c.id = b.category_id
                      ORDER BY b.views DESC LIMIT 8");
$popularBooks = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$todayBook = $pdo->query("SELECT b.*, c.name AS category_name FROM books b
                           LEFT JOIN categories c ON c.id = b.category_id
                           ORDER BY RAND() LIMIT 1")->fetch();
?>

<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="hero-eyebrow">Perpustakaan Digital Desa Pamulihan</div>
        <h1>Buka Gadget, <em>Buka Buku.</em></h1>
        <p class="lead mt-3">Temukan buku paket, cerita, dan pengetahuan langsung dari HP kamu —
          gratis untuk siswa dan guru di Desa Pamulihan, Kecamatan Pamulihan, Kabupaten Sumedang.</p>
        <div class="hero-actions">
          <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-turmeric btn-lg"><i class="bi bi-search"></i> Jelajahi Katalog</a>
          <?php if (!$user): ?>
          <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-light btn-lg" style="border-radius:999px;">Daftar Gratis</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div class="hero-visual">
          <div class="glow"></div>
          <svg viewBox="0 0 320 260" xmlns="http://www.w3.org/2000/svg">
            <!-- meja/alas -->
            <ellipse cx="160" cy="222" rx="118" ry="14" fill="#0D1A13" opacity=".25"/>
            <!-- halaman kiri -->
            <path d="M160 70 L48 90 Q40 92 40 100 L40 190 Q40 198 48 196 L160 176 Z" fill="#FAF6EC"/>
            <path d="M160 70 L48 90 Q40 92 40 100 L40 190 Q40 198 48 196 L160 176 Z" fill="none" stroke="#E4D6B8" stroke-width="1.5"/>
            <line x1="54" y1="112" x2="146" y2="98" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <line x1="54" y1="130" x2="146" y2="116" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <line x1="54" y1="148" x2="130" y2="136" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <!-- halaman kanan -->
            <path d="M160 70 L272 90 Q280 92 280 100 L280 190 Q280 198 272 196 L160 176 Z" fill="#FFFDF7"/>
            <path d="M160 70 L272 90 Q280 92 280 100 L280 190 Q280 198 272 196 L160 176 Z" fill="none" stroke="#E4D6B8" stroke-width="1.5"/>
            <line x1="174" y1="98" x2="266" y2="112" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <line x1="174" y1="116" x2="266" y2="130" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <line x1="174" y1="134" x2="250" y2="146" stroke="#E4D6B8" stroke-width="3" stroke-linecap="round"/>
            <!-- tulang buku tengah -->
            <path d="M160 70 L160 176" stroke="#C9B98F" stroke-width="4" stroke-linecap="round"/>
            <!-- sampul belakang hijau -->
            <path d="M40 100 L160 78 L280 100 L280 108 L160 86 L40 108 Z" fill="#1F4D3A"/>
            <!-- aksen bookmark kunyit -->
            <path d="M196 70 L196 34 L212 46 L228 34 L228 70 Z" fill="#E3A93E"/>
            <!-- sparkle -->
            <g fill="#C1512E">
              <path d="M262 56 l4 10 10 4 -10 4 -4 10 -4 -10 -10 -4 10 -4 Z"/>
            </g>
            <g fill="#E3A93E" opacity=".8">
              <path d="M56 60 l3 7 7 3 -7 3 -3 7 -3 -7 -7 -3 7 -3 Z"/>
            </g>
          </svg>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container">
  <div class="row g-0 stat-strip">
    <div class="col-6 col-md-3 stat-item"><div class="number"><?= $totalBooks ?>+</div><div class="label">Buku</div></div>
    <div class="col-6 col-md-3 stat-item"><div class="number"><?= $totalCategories ?></div><div class="label">Kategori</div></div>
    <div class="col-6 col-md-3 stat-item"><div class="number"><?= $totalSchools ?></div><div class="label">Sekolah</div></div>
    <div class="col-6 col-md-3 stat-item"><div class="number"><?= $totalStudents ?></div><div class="label">Siswa</div></div>
  </div>
</div>

<div class="container mt-5">

  <?php if ($todayBook): ?>
  <div class="today-read-card p-4 mb-5 reveal">
    <span class="ribbon">HARI INI</span>
    <div class="row align-items-center">
      <div class="col-md-2 col-4">
        <div class="rounded shadow-sm overflow-hidden" style="aspect-ratio:2/3;">
          <?= book_cover_html($todayBook) ?>
        </div>
      </div>
      <div class="col-md-7 col-8">
        <h5 class="mb-1"><i class="bi bi-stars" style="color:var(--turmeric-500)"></i> Baca Hari Ini</h5>
        <p class="mb-1 fw-semibold"><?= htmlspecialchars($todayBook['title']) ?></p>
        <p class="text-muted small mb-0">Pilih buku ini dan luangkan 15 menit untuk membaca hari ini.</p>
      </div>
      <div class="col-md-3 mt-3 mt-md-0 text-md-end">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$todayBook['id'] ?>" class="btn btn-forest">Mulai Baca</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="section-label mb-2">Jelajahi</div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Kategori</h4>
  </div>
  <div class="d-flex flex-wrap gap-2 mb-5">
    <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/katalog.php?category=<?= (int)$cat['id'] ?>" class="chip">
        <?= htmlspecialchars($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="woven-divider mb-5 rounded"></div>

  <div class="section-label mb-2">Baru Ditambahkan</div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buku Terbaru</h4>
    <a href="<?= BASE_URL ?>/katalog.php?sort=newest" class="small fw-semibold">Lihat semua &rarr;</a>
  </div>
  <div class="row g-3 mb-5">
    <?php if ($newBooks): foreach ($newBooks as $b): render_book_card($b, 'col-6 col-md-3', true); endforeach; else: ?>
      <div class="col-12">
        <div class="text-center text-muted py-5">
          <i class="bi bi-inbox display-4"></i>
          <p class="mt-2 mb-0">Belum ada buku. Tambahkan lewat Dashboard Admin.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="section-label mb-2">Paling Diminati</div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buku Populer</h4>
    <a href="<?= BASE_URL ?>/katalog.php?sort=popular" class="small fw-semibold">Lihat semua &rarr;</a>
  </div>
  <div class="row g-3 mb-5">
    <?php if ($popularBooks): foreach ($popularBooks as $b): render_book_card($b, 'col-6 col-md-3', true); endforeach; else: ?>
      <div class="col-12">
        <div class="text-center text-muted py-5">
          <i class="bi bi-bar-chart display-4"></i>
          <p class="mt-2 mb-0">Belum ada data pembacaan.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
