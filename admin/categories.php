<?php
$page_title = 'Kategori';
require_once __DIR__ . '/../includes/admin_header.php';

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (:n, :s)");
        $stmt->execute([':n' => $name, ':s' => slugify($name)]);
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

if (($_GET['delete'] ?? null)) {
    $pdo->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => (int)$_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    csrf_check();
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $pdo->prepare("UPDATE categories SET name=:n, slug=:s WHERE id=:id")
            ->execute([':n' => $name, ':s' => slugify($name), ':id' => $id]);
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id = c.id) AS book_count
                            FROM categories c ORDER BY c.name")->fetchAll();
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-card admin-card-pad">
      <div class="admin-section-title"><i class="bi bi-plus-circle" style="color:var(--forest-600);"></i> Kategori Baru</div>
      <p class="text-muted small mb-3">Kategori membantu siswa menyaring buku di katalog.</p>
      <form method="post" class="d-flex gap-2">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="text" name="name" class="form-control" placeholder="Nama kategori" required>
        <button class="btn btn-forest"><i class="bi bi-plus"></i></button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>Jumlah Buku</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <form method="post" class="d-flex gap-1" style="max-width:280px;">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="form-control form-control-sm">
                  <button class="btn btn-sm btn-outline-forest" title="Simpan"><i class="bi bi-check"></i></button>
                </form>
              </td>
              <td><span class="pill pill-forest"><?= (int)$cat['book_count'] ?> buku</span></td>
              <td class="text-end">
                <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus kategori <?= htmlspecialchars($cat['name']) ?>?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
              <tr><td colspan="3">
                <div class="admin-empty"><i class="bi bi-tags"></i><p>Belum ada kategori.</p></div>
              </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
