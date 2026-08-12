<?php
$page_title = 'Semua Buku';
require_once __DIR__ . '/../includes/admin_header.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT b.*, c.name AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id";
$params = [];
if ($q !== '') {
    $sql .= " WHERE b.title LIKE :q OR b.author LIKE :q";
    $params[':q'] = '%' . $q . '%';
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

if (isset($_GET['deleted'])) echo '<div class="alert alert-success">Buku berhasil dihapus.</div>';
if (isset($_GET['saved'])) echo '<div class="alert alert-success">Buku berhasil disimpan.</div>';
?>

<div class="card p-3">
  <form method="get" class="d-flex gap-2 mb-3">
    <input type="text" name="q" class="form-control" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($q) ?>" style="max-width:300px">
    <button class="btn btn-outline-success">Cari</button>
    <a href="<?= BASE_URL ?>/admin/book_form.php" class="btn btn-success ms-auto"><i class="bi bi-plus-circle"></i> Tambah Buku</a>
  </form>

  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr><th>Judul</th><th>Penulis</th><th>Kategori</th><th>Jenjang</th><th>Sumber</th><th>Views</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($books as $b): ?>
      <tr>
        <td><?= htmlspecialchars($b['title']) ?></td>
        <td><?= htmlspecialchars($b['author'] ?? '-') ?></td>
        <td><?= htmlspecialchars($b['category_name'] ?? '-') ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($b['grade_level']) ?></span></td>
        <td><span class="badge bg-info-subtle text-info-emphasis"><?= htmlspecialchars($b['source']) ?></span></td>
        <td><?= (int)$b['views'] ?></td>
        <td>
          <a href="<?= BASE_URL ?>/admin/book_form.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <a href="<?= BASE_URL ?>/admin/book_delete.php?id=<?= $b['id'] ?>"
             class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Yakin hapus buku ini?')"><i class="bi bi-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$books): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada buku.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
