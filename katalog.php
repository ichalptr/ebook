<?php
$page_title = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cover_helper.php';
require_once __DIR__ . '/includes/book_card_helper.php'; // <-- NEW

$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$grade      = $_GET['grade'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';
$page       = (int)($_GET['page'] ?? 1);
$perPage    = 24;
$offset     = ($page - 1) * $perPage;

$sql = "SELECT b.*, c.name AS category_name FROM books b
        LEFT JOIN categories c ON c.id = b.category_id
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (b.title LIKE :q OR b.author LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $sql .= " AND b.category_id = :cat";
    $params[':cat'] = $categoryId;
}
if ($grade !== '') {
    $sql .= " AND b.grade_level = :grade";
    $params[':grade'] = $grade;
}

$sql .= match ($sort) {
    'popular' => " ORDER BY b.views DESC",
    'title'   => " ORDER BY b.title ASC",
    default   => " ORDER BY b.created_at DESC",
};

// ---- PAGINATION ----
$countSql = str_replace("SELECT b.*, c.name AS category_name", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalBooks = $countStmt->fetchColumn();
$totalPages = ceil($totalBooks / $perPage);

$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $perPage;
$params[':offset'] = $offset;
// ---- END PAGINATION ----

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val);
    }
}
$stmt->execute();
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="container py-4">
  <div class="section-heading">
    <span class="kicker">Cari &amp; jelajahi</span>
  </div>
  <h3 class="mb-4"><i class="bi bi-search"></i> Katalog Buku</h3>

  <form method="get" class="row g-2 mb-4">
    <div class="col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Cari judul atau penulis..." value="<?= htmlspecialchars($q) ?>">
    </div>
    <div class="col-md-3">
      <select name="category" class="form-select">
        <option value="0">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="grade" class="form-select">
        <option value="">Semua Jenjang</option>
        <?php foreach (['SD','SMP','SMA/SMK','Umum'] as $g): ?>
          <option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="sort" class="form-select">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Terpopuler</option>
        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Judul A-Z</option>
      </select>
    </div>
    <div class="col-md-1">
      <button class="btn btn-brand w-100"><i class="bi bi-funnel"></i></button>
    </div>
  </form>

  <p class="text-muted"><?= $totalBooks ?> buku ditemukan<?= $q !== '' ? ' untuk "' . htmlspecialchars($q) . '"' : '' ?></p>

  <div class="row g-3">
    <?php foreach ($books as $b): render_book_card($b, 'col-6 col-md-3 col-lg-2'); endforeach; ?>

    <?php if (!$books): ?>
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p class="mt-2 mb-1 fw-semibold">Tidak ada buku yang cocok.</p>
          <p class="small mb-0">Coba ubah kata kunci, kategori, atau jenjang pencarianmu.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav aria-label="Navigasi halaman" class="mt-4">
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" aria-label="Sebelumnya">«</a>
        </li>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" aria-label="Selanjutnya">»</a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>