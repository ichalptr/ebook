<?php
$page_title = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/book_card_helper.php';

$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$grade      = $_GET['grade'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';

$sql = "SELECT b.*, c.name AS category_name FROM books b
        LEFT JOIN categories c ON c.id = b.category_id
        WHERE 1=1";
$params = [];

if ($q !== '') { $sql .= " AND (b.title LIKE :q1 OR b.author LIKE :q2)"; $params[':q1'] = '%' . $q . '%'; $params[':q2'] = '%' . $q . '%'; }
if ($categoryId > 0) { $sql .= " AND b.category_id = :cat"; $params[':cat'] = $categoryId; }
if ($grade !== '') { $sql .= " AND b.grade_level = :grade"; $params[':grade'] = $grade; }

$sql .= match ($sort) {
    'popular' => " ORDER BY b.views DESC",
    'title'   => " ORDER BY b.title ASC",
    default   => " ORDER BY b.created_at DESC",
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$activeFilterCount = ($q !== '' ? 1 : 0) + ($categoryId > 0 ? 1 : 0) + ($grade !== '' ? 1 : 0);

$activeCategoryName = '';
if ($categoryId > 0) {
    foreach ($categories as $cat) {
        if ((int)$cat['id'] === $categoryId) { $activeCategoryName = $cat['name']; break; }
    }
}
?>

<div class="container py-4">
  <div class="section-label mb-2">Temukan Bacaan</div>
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
      <h3 class="mb-1">Katalog Buku</h3>
      <p class="text-muted mb-0"><?= count($books) ?> buku ditemukan<?= $activeCategoryName ? ' dalam kategori "' . htmlspecialchars($activeCategoryName) . '"' : '' ?></p>
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
