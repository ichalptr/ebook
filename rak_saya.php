<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$page_title = 'Rak Saya';
require_once __DIR__ . '/includes/header.php';

$favStmt = $pdo->prepare("SELECT b.*, c.name AS category_name FROM favorites f
                           JOIN books b ON b.id = f.book_id
                           LEFT JOIN categories c ON c.id = b.category_id
                           WHERE f.user_id = :u ORDER BY f.created_at DESC");
$favStmt->execute([':u' => $user['id']]);
$favorites = $favStmt->fetchAll();

$histStmt = $pdo->prepare("SELECT b.*, rh.current_page, rh.last_read_at FROM reading_history rh
                            JOIN books b ON b.id = rh.book_id
                            WHERE rh.user_id = :u ORDER BY rh.last_read_at DESC");
$histStmt->execute([':u' => $user['id']]);
$history = $histStmt->fetchAll();

function book_cover_src(array $book): string {
    if (!empty($book['cover_image'])) {
        if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) return htmlspecialchars($book['cover_image']);
        return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
    }
    return 'https://via.placeholder.com/300x450/1f7a3d/ffffff?text=' . urlencode($book['title']);
}
?>
<div class="container py-4">
  <h3 class="mb-4"><i class="bi bi-bookshelf"></i> Rak Saya</h3>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-lanjut">Lanjutkan Membaca</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-favorit">Favorit</a></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-lanjut">
      <?php if (!$history): ?>
        <p class="text-muted">Kamu belum mulai membaca buku apa pun. <a href="<?= BASE_URL ?>/katalog.php">Jelajahi katalog</a>.</p>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($history as $h): ?>
          <div class="col-md-6">
            <div class="card p-3 d-flex flex-row gap-3 align-items-center">
              <img src="<?= book_cover_src($h) ?>" style="width:60px;height:90px;object-fit:cover;border-radius:6px;">
              <div class="flex-grow-1">
                <h6 class="mb-1"><?= htmlspecialchars($h['title']) ?></h6>
                <small class="text-muted">Halaman <?= (int)$h['current_page'] ?><?= $h['page_count'] ? ' / ' . (int)$h['page_count'] : '' ?></small>
                <div class="mt-2">
                  <a href="<?= BASE_URL ?>/baca.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-success">Lanjutkan</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="tab-favorit">
      <?php if (!$favorites): ?>
        <p class="text-muted">Belum ada buku favorit.</p>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($favorites as $b): ?>
          <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>/detail.php?id=<?= $b['id'] ?>" class="text-decoration-none text-dark">
              <div class="card book-card">
                <div class="book-cover-wrap"><img src="<?= book_cover_src($b) ?>" alt=""></div>
                <div class="card-body p-2">
                  <h6 class="mb-0 text-truncate"><?= htmlspecialchars($b['title']) ?></h6>
                  <small class="text-muted"><?= htmlspecialchars($b['author'] ?? '-') ?></small>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
