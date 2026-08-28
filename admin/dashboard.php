<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$totalBooks     = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$totalReads     = (int)$pdo->query("SELECT COUNT(*) FROM reading_history")->fetchColumn();

$mostRead = $pdo->query("SELECT title, views FROM books ORDER BY views DESC LIMIT 5")->fetchAll();
$recentBooks = $pdo->query("SELECT title, author, created_at FROM books ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Total Buku</div><div class="fs-3 fw-bold text-success"><?= $totalBooks ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Kategori</div><div class="fs-3 fw-bold text-success"><?= $totalCategories ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Siswa</div><div class="fs-3 fw-bold text-success"><?= $totalStudents ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Guru</div><div class="fs-3 fw-bold text-success"><?= $totalTeachers ?></div></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card p-3">
      <h6><i class="bi bi-fire text-danger"></i> Buku Paling Banyak Dibaca</h6>
      <table class="table table-sm mb-0">
        <?php foreach ($mostRead as $b): ?>
          <tr><td><?= htmlspecialchars($b['title']) ?></td><td class="text-end"><?= (int)$b['views'] ?> views</td></tr>
        <?php endforeach; ?>
        <?php if (!$mostRead): ?><tr><td class="text-muted">Belum ada data.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <h6><i class="bi bi-clock-history text-success"></i> Buku Terbaru Ditambahkan</h6>
      <table class="table table-sm mb-0">
        <?php foreach ($recentBooks as $b): ?>
          <tr><td><?= htmlspecialchars($b['title']) ?></td><td class="text-muted small text-end"><?= date('d M Y', strtotime($b['created_at'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$recentBooks): ?><tr><td class="text-muted">Belum ada buku.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>

<div class="mt-4">
  <a href="<?= BASE_URL ?>/admin/book_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Buku Manual</a>
  <a href="<?= BASE_URL ?>/admin/import_official.php" class="btn btn-success"><i class="bi bi-bank"></i> Import Resmi Kemendikdasmen (BTP)</a>
  <a href="<?= BASE_URL ?>/admin/import_books.php" class="btn btn-outline-success"><i class="bi bi-cloud-download"></i> Import Satu Buku (Google Books)</a>
  <a href="<?= BASE_URL ?>/admin/bulk_import.php" class="btn btn-outline-success"><i class="bi bi-cloud-arrow-down"></i> Bulk Import (Google Books)</a>
  <a href="<?= BASE_URL ?>/admin/import_csv.php" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Import CSV (Efisien untuk Buku Paket/SIBI)</a>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
