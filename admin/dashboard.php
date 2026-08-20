<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$totalBooks      = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$missingFiles    = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE file_path IS NULL OR file_path = ''")->fetchColumn();

$mostRead    = $pdo->query("SELECT id, title, author, views FROM books ORDER BY views DESC LIMIT 5")->fetchAll();
$recentBooks = $pdo->query("SELECT id, title, author, created_at, source FROM books ORDER BY created_at DESC LIMIT 5")->fetchAll();

$sourceLabels = [
    'manual' => ['Manual', 'pill-forest'],
    'google_books' => ['Google Books', 'pill-turmeric'],
    'open_library' => ['Open Library', 'pill-ink'],
];
?>

<!-- Stat cards -->
<div class="stat-grid mb-4">
  <div class="admin-stat">
    <span class="icon-chip chip-forest"><i class="bi bi-journal-bookmark"></i></span>
    <div class="body">
      <div class="number"><?= $totalBooks ?></div>
      <div class="label">Total Buku</div>
    </div>
  </div>
  <div class="admin-stat">
    <span class="icon-chip chip-turmeric"><i class="bi bi-tags"></i></span>
    <div class="body">
      <div class="number"><?= $totalCategories ?></div>
      <div class="label">Kategori</div>
    </div>
  </div>
  <div class="admin-stat">
    <span class="icon-chip chip-clay"><i class="bi bi-mortarboard"></i></span>
    <div class="body">
      <div class="number"><?= $totalStudents ?></div>
      <div class="label">Siswa Terdaftar</div>
    </div>
  </div>
  <div class="admin-stat">
    <span class="icon-chip chip-ink"><i class="bi bi-easel"></i></span>
    <div class="body">
      <div class="number"><?= $totalTeachers ?></div>
      <div class="label">Guru Terdaftar</div>
    </div>
  </div>
</div>

<?php if ($missingFiles > 0): ?>
<div class="admin-card admin-card-pad mb-4 d-flex align-items-center gap-3" style="border-color:var(--turmeric-400); background:var(--turmeric-100);">
  <i class="bi bi-exclamation-triangle-fill fs-4" style="color:var(--turmeric-500);"></i>
  <div class="flex-grow-1">
    <strong><?= $missingFiles ?> buku</strong> belum punya file PDF (metadata saja). Lengkapi lewat
    <a href="<?= BASE_URL ?>/admin/books.php">Semua Buku &rarr; Edit</a>, tempel link SIBI atau unggah file.
  </div>
</div>
<?php endif; ?>

<!-- Quick actions -->
<div class="admin-section-title mb-3"><i class="bi bi-lightning-charge" style="color:var(--turmeric-500);"></i> Tambah Konten Cepat</div>
<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/book_form.php" class="admin-card admin-card-pad d-block text-decoration-none h-100">
      <i class="bi bi-plus-circle fs-4" style="color:var(--forest-600);"></i>
      <div class="fw-semibold mt-2" style="color:var(--ink-900);">Buku Manual</div>
      <div class="small text-muted">Isi satu per satu</div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/import_official.php" class="admin-card admin-card-pad d-block text-decoration-none h-100">
      <i class="bi bi-bank fs-4" style="color:var(--forest-600);"></i>
      <div class="fw-semibold mt-2" style="color:var(--ink-900);">Impor Resmi BTP</div>
      <div class="small text-muted">Data Kemendikdasmen</div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/bulk_import.php" class="admin-card admin-card-pad d-block text-decoration-none h-100">
      <i class="bi bi-cloud-arrow-down fs-4" style="color:var(--forest-600);"></i>
      <div class="fw-semibold mt-2" style="color:var(--ink-900);">Bulk Import</div>
      <div class="small text-muted">Google Books, banyak topik</div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/import_csv.php" class="admin-card admin-card-pad d-block text-decoration-none h-100">
      <i class="bi bi-filetype-csv fs-4" style="color:var(--forest-600);"></i>
      <div class="fw-semibold mt-2" style="color:var(--ink-900);">Import CSV</div>
      <div class="small text-muted">Efisien untuk buku paket</div>
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-pad pb-2">
        <div class="admin-section-title"><i class="bi bi-fire" style="color:var(--clay-500);"></i> Paling Banyak Dibaca</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <?php if ($mostRead): foreach ($mostRead as $b): ?>
            <tr>
              <td class="row-title-cell">
                <span class="fw-semibold"><?= htmlspecialchars($b['title']) ?></span>
              </td>
              <td class="text-end text-muted"><i class="bi bi-eye"></i> <?= (int)$b['views'] ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td class="text-muted py-3">Belum ada data pembacaan.</td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-pad pb-2">
        <div class="admin-section-title"><i class="bi bi-clock-history" style="color:var(--forest-600);"></i> Buku Terbaru Ditambahkan</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <?php if ($recentBooks): foreach ($recentBooks as $b):
            $src = $sourceLabels[$b['source']] ?? [$b['source'], 'pill-ink'];
          ?>
            <tr>
              <td>
                <span class="fw-semibold d-block"><?= htmlspecialchars($b['title']) ?></span>
                <span class="pill <?= $src[1] ?> mt-1 d-inline-block"><?= htmlspecialchars($src[0]) ?></span>
              </td>
              <td class="text-end text-muted small"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td class="text-muted py-3">Belum ada buku.</td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
