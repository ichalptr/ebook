<?php
$page_title = 'Semua Buku';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/cover_helper.php';

$q = trim($_GET['q'] ?? '');
$missingOnly = ($_GET['missing_file'] ?? '') === '1';

$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = "(b.title LIKE :q1 OR b.author LIKE :q2)";
    $params[':q1'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}
if ($missingOnly) {
    $conditions[] = "(b.file_path IS NULL OR b.file_path = '')";
}

$sql = "SELECT b.*, c.name AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id";
if ($conditions) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Dipakai buat badge angka di chip filter, dihitung terpisah dari hasil di atas
// supaya angkanya tetap tampil walau filter "Belum Lengkap" sedang tidak aktif.
$missingFileTotal = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE file_path IS NULL OR file_path = ''")->fetchColumn();

$sourceLabels = [
    'manual' => ['Manual', 'pill-forest'],
    'google_books' => ['Google Books', 'pill-turmeric'],
    'open_library' => ['Open Library', 'pill-ink'],
    'csv_import' => ['CSV', 'pill-clay'],
    'kemendikdasmen' => ['Kemendikdasmen', 'pill-ink'],
];
?>

<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle"></i> Buku berhasil dihapus.</div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle"></i> Buku berhasil disimpan.</div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-pad pb-0">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-3" id="bookFilterForm">
      <div class="input-group" style="max-width:320px;">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($q) ?>">
      </div>
      <button class="btn btn-outline-forest">Cari</button>

      <label class="btn btn-sm <?= $missingOnly ? 'btn-clay' : 'btn-outline-forest' ?> mb-0 d-flex align-items-center gap-1" style="cursor:pointer;">
        <input type="checkbox" name="missing_file" value="1" <?= $missingOnly ? 'checked' : '' ?>
               onchange="document.getElementById('bookFilterForm').submit()" class="d-none">
        <i class="bi bi-exclamation-circle"></i> Belum Lengkap<?= $missingFileTotal > 0 ? " ($missingFileTotal)" : '' ?>
      </label>

      <?php if ($q || $missingOnly): ?><a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-link text-decoration-none">Reset</a><?php endif; ?>
      <a href="<?= BASE_URL ?>/admin/book_form.php" class="btn btn-forest ms-auto"><i class="bi bi-plus-circle"></i> Tambah Buku</a>
    </form>
    <div class="small text-muted mb-2">
      <?= count($books) ?> buku ditemukan<?= $q ? ' untuk "' . htmlspecialchars($q) . '"' : '' ?><?= $missingOnly ? ' — belum punya file PDF' : '' ?>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Buku</th><th>Kategori</th><th>Jenjang</th><th>File</th><th>Sumber</th><th>Views</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($books as $b):
          $src = $sourceLabels[$b['source']] ?? [$b['source'], 'pill-ink'];
        ?>
        <tr>
          <td>
            <div class="row-title-cell">
              <?= book_cover_html($b, 'row-thumb') ?>
              <div class="title-text">
                <strong><?= htmlspecialchars($b['title']) ?></strong>
                <small><?= htmlspecialchars($b['author'] ?? '-') ?></small>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($b['category_name'] ?? '-') ?></td>
          <td><span class="pill pill-ink"><?= htmlspecialchars($b['grade_level']) ?></span></td>
          <td>
            <?php if ($b['file_path']): ?>
              <span class="pill pill-forest"><i class="bi bi-file-earmark-pdf"></i> Ada</span>
            <?php else: ?>
              <span class="pill" style="background:var(--clay-100); color:var(--clay-600);"><i class="bi bi-exclamation-circle"></i> Belum</span>
            <?php endif; ?>
          </td>
          <td><span class="pill <?= $src[1] ?>"><?= htmlspecialchars($src[0]) ?></span></td>
          <td class="text-muted"><i class="bi bi-eye"></i> <?= (int)$b['views'] ?></td>
          <td>
            <div class="row-actions">
              <a href="<?= BASE_URL ?>/admin/book_form.php?id=<?= $b['id'] ?>" class="btn btn-outline-forest" title="Edit"><i class="bi bi-pencil"></i></a>
              <a href="<?= BASE_URL ?>/admin/book_delete.php?id=<?= $b['id'] ?>"
                 class="btn btn-outline-danger" title="Hapus"
                 onclick="return confirm('Yakin hapus buku ini?')"><i class="bi bi-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$books): ?>
          <tr><td colspan="7">
            <div class="admin-empty">
              <?php if ($missingOnly): ?>
                <i class="bi bi-check-circle" style="color:var(--forest-500);"></i>
                <p>Semua buku sudah punya file PDF. 🎉</p>
                <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-outline-forest btn-sm">Lihat Semua Buku</a>
              <?php else: ?>
                <i class="bi bi-inbox"></i>
                <p>Belum ada buku yang cocok. Tambahkan buku baru atau reset pencarian.</p>
                <a href="<?= BASE_URL ?>/admin/book_form.php" class="btn btn-forest btn-sm"><i class="bi bi-plus-circle"></i> Tambah Buku</a>
              <?php endif; ?>
            </div>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
