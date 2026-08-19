<?php
$page_title = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';

$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$grade      = $_GET['grade'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';

$sql = "SELECT b.*, c.name AS category_name FROM books b
        LEFT JOIN categories c ON c.id = b.category_id
        WHERE 1=1";
$params = [];

if ($q !== '') { $sql .= " AND (b.title LIKE :q OR b.author LIKE :q)"; $params[':q'] = '%' . $q . '%'; }
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

function book_cover_src(array $book): string {
    if (!empty($book['cover_image'])) {
        if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) return htmlspecialchars($book['cover_image']);
        return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
    }
    return 'https://via.placeholder.com/300x450/1F4D3A/FAF6EC?text=' . urlencode($book['title']);
}
?>

<div class="container py-4">
  <div class="section-label mb-2">Temukan Bacaan</div>
  <h3 class="mb-4">Katalog Buku</h3>

  <form method="get" class="row g-2 mb-4 sticky-top py-2" style="top:70px; background:var(--sand-100); z-index:10;">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari judul atau penulis..." value="<?= htmlspecialchars($q) ?>">
      </div>
    </div>
    <div class="col-md-3">
      <select name="category" class="form-select">
        <option value="0">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
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
      <button class="btn btn-forest w-100"><i class="bi bi-funnel"></i></button>
    </div>
  </form>

  <p class="text-muted"><?= count($books) ?> buku ditemukan</p>

  <div class="row g-3">
    <?php foreach ($books as $b): ?>
      <div class="col-6 col-md-3 col-lg-2">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$b['id'] ?>" class="text-decoration-none text-dark">
          <div class="card book-card reveal">
            <div class="book-cover-wrap">
              <img src="<?= book_cover_src($b) ?>" alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy">
              <i class="bi bi-bookmark-fill fold-icon"></i>
            </div>
            <div class="card-body p-2">
              <span class="badge badge-grade mb-1"><?= htmlspecialchars($b['grade_level']) ?></span>
              <h6 class="mb-0 text-truncate"><?= htmlspecialchars($b['title']) ?></h6>
              <small class="text-muted text-truncate d-block"><?= htmlspecialchars($b['author'] ?? '-') ?></small>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>

    <?php if (!$books): ?>
      <div class="col-12 text-center text-muted py-5">
        <i class="bi bi-inbox display-4"></i>
        <p class="mt-2">Tidak ada buku yang cocok dengan pencarian kamu.</p>
        <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-outline-forest btn-sm">Reset Filter</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
