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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

function book_cover_src(array $book): string {
    if (!empty($book['cover_image'])) {
        if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) {
            return htmlspecialchars($book['cover_image']);
        }
        return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
    }
    return 'https://via.placeholder.com/300x450/1f7a3d/ffffff?text=' . urlencode($book['title']);
}
?>

<div class="container py-4">
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
      <button class="btn btn-success w-100"><i class="bi bi-funnel"></i></button>
    </div>
  </form>

  <p class="text-muted"><?= count($books) ?> buku ditemukan</p>

  <div class="row g-3">
    <?php foreach ($books as $b): ?>
      <div class="col-6 col-md-3 col-lg-2">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$b['id'] ?>" class="text-decoration-none text-dark">
          <div class="card book-card">
            <div class="book-cover-wrap">
              <img src="<?= book_cover_src($b) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
            </div>
            <div class="card-body p-2">
              <span class="badge bg-success-subtle text-success-emphasis badge-grade mb-1"><?= htmlspecialchars($b['grade_level']) ?></span>
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
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
