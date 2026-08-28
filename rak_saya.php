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

$recStmt = $pdo->prepare("SELECT b.*, c.name AS category_name, r.note, r.created_at AS rec_created_at, u.name AS teacher_name
                           FROM recommendations r
                           JOIN books b ON b.id = r.book_id
                           LEFT JOIN categories c ON c.id = b.category_id
                           JOIN users u ON u.id = r.teacher_id
                           WHERE r.student_id = :u ORDER BY r.created_at DESC");
$recStmt->execute([':u' => $user['id']]);
$recommendations = $recStmt->fetchAll();

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
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-rekomendasi">Rekomendasi Guru <?php if ($recommendations): ?><span class="badge bg-success rounded-pill ms-1"><?= count($recommendations) ?></span><?php endif; ?></a></li>
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

    <div class="tab-pane fade" id="tab-rekomendasi">
      <?php if (!$recommendations): ?>
        <p class="text-muted">Belum ada rekomendasi buku dari guru.</p>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($recommendations as $r): ?>
          <div class="col-md-6">
            <div class="card p-3 d-flex flex-row gap-3 align-items-center">
              <img src="<?= book_cover_src($r) ?>" style="width:60px;height:90px;object-fit:cover;border-radius:6px;">
              <div class="flex-grow-1">
                <h6 class="mb-1"><?= htmlspecialchars($r['title']) ?></h6>
                <small class="text-muted d-block">Direkomendasikan oleh <?= htmlspecialchars($r['teacher_name']) ?></small>
                <?php if ($r['note']): ?>
                  <small class="text-muted fst-italic d-block mt-1">"<?= htmlspecialchars($r['note']) ?>"</small>
                <?php endif; ?>
                <div class="mt-2">
                  <a href="<?= BASE_URL ?>/detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Lihat Buku</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
