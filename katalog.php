<?php
$page_title = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/book_card_helper.php';

$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$grade      = $_GET['grade'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';

// Kondisi WHERE dibangun sekali, dipakai ulang utuh utuh untuk query utama
// DAN query hitung total (pagination) — supaya keduanya selalu konsisten dan
// placeholder-nya tidak pernah dobel dipakai (lihat catatan :q1/:q2 di bawah).
$conditions = [];
$params = [];
// PENTING: MariaDB dengan PDO::ATTR_EMULATE_PREPARES => false menolak nama
// placeholder yang sama dipakai 2x dalam satu query ("SQLSTATE[HY093]:
// Invalid parameter number"). Makanya title/author pakai :q1 dan :q2 terpisah
// walau nilainya sama — JANGAN disatukan lagi jadi :q.
if ($q !== '') {
    $conditions[] = "(b.title LIKE :q1 OR b.author LIKE :q2)";
    $params[':q1'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}
if ($categoryId > 0) { $conditions[] = "b.category_id = :cat"; $params[':cat'] = $categoryId; }
if ($grade !== '') { $conditions[] = "b.grade_level = :grade"; $params[':grade'] = $grade; }

$whereSql = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM books b" . $whereSql);
$countStmt->execute($params);
$totalBooks = (int)$countStmt->fetchColumn();

$perPage = 12;
$totalPages = max(1, (int)ceil($totalBooks / $perPage));
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$orderSql = match ($sort) {
    'popular' => ' ORDER BY b.views DESC',
    'title'   => ' ORDER BY b.title ASC',
    default   => ' ORDER BY b.created_at DESC',
};

$sql = "SELECT b.*, c.name AS category_name FROM books b
        LEFT JOIN categories c ON c.id = b.category_id"
     . $whereSql . $orderSql . " LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$activeFilterCount = ($q !== '' ? 1 : 0) + ($categoryId > 0 ? 1 : 0) + ($grade !== '' ? 1 : 0);

$activeCategoryName = '';
if ($categoryId > 0) {
    foreach ($categories as $cat) {
        if ((int)$cat['id'] === $categoryId) { $activeCategoryName = $cat['name']; break; }
    }
}

/** Bangun URL query string, timpa parameter tertentu (dipakai buat link pagination). */
function katalog_query_url(array $override): string {
    $query = array_merge($_GET, $override);
    return BASE_URL . '/katalog.php?' . http_build_query($query);
}
?>

<div class="container py-4">
  <div class="section-label mb-2">Temukan Bacaan</div>
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
      <h3 class="mb-1">Katalog Buku</h3>
      <p class="text-muted mb-0">
        <?= $totalBooks ?> buku ditemukan<?= $activeCategoryName ? ' dalam kategori "' . htmlspecialchars($activeCategoryName) . '"' : '' ?><?= $totalPages > 1 ? " — halaman {$page} dari {$totalPages}" : '' ?>
      </p>
    </div>
  </div>

  <form method="get" class="filter-toolbar sticky-top mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" class="form-control border-start-0" placeholder="Cari judul atau penulis..." value="<?= htmlspecialchars($q) ?>">
        </div>
      </div>
      <div class="col-md-3 col-6">
        <select name="category" class="form-select">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-6">
        <select name="grade" class="form-select">
          <option value="">Semua Jenjang</option>
          <?php foreach (['SD','SMP','SMA/SMK','Umum'] as $g): ?>
            <option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-8">
        <select name="sort" class="form-select">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
          <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Terpopuler</option>
          <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Judul A-Z</option>
        </select>
      </div>
      <div class="col-md-1 col-4">
        <button class="btn btn-forest w-100"><i class="bi bi-funnel"></i></button>
      </div>
    </div>
    <?php if ($activeFilterCount): ?>
      <div class="mt-2">
        <a href="<?= BASE_URL ?>/katalog.php" class="small text-decoration-none"><i class="bi bi-x-circle"></i> Reset <?= $activeFilterCount ?> filter aktif</a>
      </div>
    <?php endif; ?>
  </form>

  <div class="row g-3">
    <?php if ($books): foreach ($books as $b): render_book_card($b, 'col-6 col-md-3 col-lg-2', true); endforeach; else: ?>
      <div class="col-12 text-center text-muted py-5">
        <i class="bi bi-inbox display-4"></i>
        <p class="mt-2">Tidak ada buku yang cocok dengan pencarian kamu.</p>
        <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-outline-forest btn-sm">Reset Filter</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-4" aria-label="Navigasi halaman katalog">
    <ul class="pagination justify-content-center">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= katalog_query_url(['page' => $page - 1]) ?>">&laquo; Sebelumnya</a>
      </li>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= katalog_query_url(['page' => $p]) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= katalog_query_url(['page' => $page + 1]) ?>">Berikutnya &raquo;</a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
